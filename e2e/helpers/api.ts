import { randomUUID } from 'node:crypto';
import fs from 'node:fs';
import { APIRequestContext, APIResponse, BrowserContext, request } from '@playwright/test';
import { readEmailVerificationKey } from './mailpit';
import { ADMIN_ROLES, API_URL, FIRST_ADMIN } from './stack';

export interface Credentials {
  name: string;
  email: string;
  password: string;
}

/** A user with a login session. `api` sends requests as this user. */
export interface LoggedInUser extends Credentials {
  api: APIRequestContext;
}

export type DraftStatus = 'Setting Up' | 'In Progress' | 'Complete';

// Unlike DraftStatus, these hold the values that the API sends and accepts
export type League = 'NFL' | 'NFLE' | 'MLB' | 'NBA' | 'NHL' | 'S15';
export type DraftStyle = 'serpentine' | 'standard';

export interface DraftOptions {
  /** Default: Setting Up. In Progress and Complete need two or more Managers. */
  status?: DraftStatus;
  name?: string;
  league?: League;
  style?: DraftStyle;
  rounds?: number;
  /** The Draft Password. Default: none. */
  password?: string;
  /** Manager names in Draft Order. Default: two Managers, or none for a Setting Up Draft. */
  managers?: string[];
  /** Depth Chart positions. Default: the Draft has no Depth Chart. */
  depthChartPositions?: { position: string; slots: number }[];
}

export interface Manager {
  id: number;
  name: string;
}

export interface Draft {
  id: number;
  name: string;
  password: string | null;
  managers: Manager[];
}

/** One Round Time. Leave out `round` for one time that holds for each round. */
export interface RoundTime {
  seconds: number;
  round?: number;
}

/** What the Commissioner enters for a Pick. `team` and `position` must be values of the Draft's League. */
export interface PickEntry {
  firstName: string;
  lastName: string;
  team: string;
  position: string;
}

export interface SelectedPick extends PickEntry {
  id: number;
  round: number;
  /** The Pick number in the whole Draft, from 1. */
  number: number;
  managerId: number;
}

export interface TradeEntry {
  manager1Id: number;
  manager2Id: number;
  /** The IDs of the Picks that change Manager. Each Pick must belong to one of the two Managers. */
  pickIds: number[];
}

// The API keeps the old word for Setting Up
const STATUS_VALUES: Record<Exclude<DraftStatus, 'Complete'>, string> = {
  'Setting Up': 'undrafted',
  'In Progress': 'in_progress',
};

// A team and position that are valid in each League would not exist, so the default entry is for the NFL
const DEFAULT_PICK = { team: 'GNB', position: 'WR' };

/** A name that no other test uses, so tests can run at the same time on one database. */
export function unique(label: string): string {
  return `${label} ${randomUUID().slice(0, 8)}`;
}

/** The email address that the helpers give a user, made from the name. */
export function emailFor(user: Pick<Credentials, 'name'>): string {
  return `${user.name.toLowerCase().replaceAll(' ', '-')}@e2e.example`;
}

const DEADLOCK_RETRIES = 3;

/**
 * Sends a setup request and returns the JSON answer. An answer that is not a success is an error.
 *
 * The API can answer a write with a database deadlock when tests write at the same time, for example when two
 * Drafts go In Progress together. The database rolls the write back, so the request is safe to send again.
 */
async function send<T>(sendRequest: () => Promise<APIResponse>, action: string): Promise<T> {
  for (let attempt = 0; ; attempt++) {
    const response = await sendRequest();

    if (response.ok()) {
      return (await response.json()) as T;
    }

    const body = await response.text();

    if (attempt === DEADLOCK_RETRIES || !body.includes('Deadlock found')) {
      throw new Error(`API setup could not ${action}: ${response.status()} ${body}`);
    }
  }
}

/**
 * Creates test state through the API, the same way the client does. One instance belongs to one test:
 * the `setup` fixture in helpers/test.ts makes it and closes its sessions after the test.
 */
export class ApiSetup {
  private readonly sessions: APIRequestContext[] = [];

  /** Registers a new user and follows the emailed link, so the user is Enabled, Email Verified, and logged in. */
  async registerCommissioner(overrides: Partial<Credentials> = {}): Promise<LoggedInUser> {
    const { credentials, api } = await this.register(overrides);
    const { key } = await readEmailVerificationKey(credentials.email);

    await send(
      () => api.post('emailVerification', { data: { _email: credentials.email, _emailVerificationKey: key } }),
      'follow the Email Verification Key link',
    );

    return { ...credentials, api };
  }

  /** Registers a new user and stops there: the user is Email Unverified, and the registration email is in Mailpit. */
  async registerEmailUnverifiedUser(overrides: Partial<Credentials> = {}): Promise<Credentials> {
    const { credentials } = await this.register(overrides);

    return credentials;
  }

  /** Registers a new user and gives them the Admin role. */
  async registerAdmin(overrides: Partial<Credentials> = {}): Promise<LoggedInUser> {
    const commissioner = await this.registerCommissioner({ name: unique('Admin'), ...overrides });
    const firstAdmin = await this.logIn(FIRST_ADMIN);

    const { users } = await send<{ users: { id: number; email: string }[] }>(
      () => firstAdmin.api.get('admin/users'),
      'list the users',
    );
    const user = users.find((candidate) => candidate.email === commissioner.email.toLowerCase());

    if (!user) {
      throw new Error(`API setup could not find the user ${commissioner.email} to make them an Admin`);
    }

    await send(
      () =>
        firstAdmin.api.put(`admin/user/${user.id}`, {
          data: { name: commissioner.name, email: commissioner.email, roles: ADMIN_ROLES, enabled: true },
        }),
      'give the Admin role',
    );

    // The login session of the new user ended with the role change, so the Admin needs a new one
    return this.logIn(commissioner);
  }

  /** Enables or disables a user. Needs an Admin. A Disabled user cannot log in, and the login session ends. */
  async setUserEnabled(admin: LoggedInUser, user: Credentials, enabled: boolean): Promise<void> {
    const { users } = await send<{ users: { id: number; email: string; roles: string | string[] }[] }>(
      () => admin.api.get('admin/users'),
      'list the users',
    );
    const stored = users.find((candidate) => candidate.email === user.email.toLowerCase());

    if (!stored) {
      throw new Error(`API setup could not find the user ${user.email} to ${enabled ? 'enable' : 'disable'}`);
    }

    await send(
      () =>
        admin.api.put(`admin/user/${stored.id}`, {
          data: { name: user.name, email: user.email, roles: stored.roles, enabled },
        }),
      `${enabled ? 'enable' : 'disable'} the user`,
    );
  }

  async logIn(credentials: Credentials): Promise<LoggedInUser> {
    const api = await this.newSession();

    await send(
      () => api.post('login', { data: { _email: credentials.email, _password: credentials.password } }),
      `log in ${credentials.email}`,
    );

    return { ...credentials, api };
  }

  /**
   * Creates a Draft in the status that `options.status` names.
   * A Complete Draft gets a Pick for each Manager in each round, which is the only way a Draft completes.
   */
  async createDraft(commissioner: LoggedInUser, options: DraftOptions = {}): Promise<Draft> {
    const status = options.status ?? 'Setting Up';
    const name = options.name ?? unique('Draft');
    const password = options.password ?? null;
    const managerNames = options.managers ?? (status === 'Setting Up' ? [] : [unique('Manager'), unique('Manager')]);

    const { draft: created } = await send<{ draft: { draft_id: number } }>(
      () =>
        commissioner.api.post('commish/draft', {
          data: {
            name,
            sport: options.league ?? 'NFL',
            style: options.style ?? 'serpentine',
            rounds: options.rounds ?? 2,
            password,
            using_depth_charts: options.depthChartPositions !== undefined,
            depthChartPositions: options.depthChartPositions ?? [],
          },
        }),
      'create a Draft',
    );
    const draft: Draft = { id: Number(created.draft_id), name, password, managers: [] };

    if (managerNames.length > 0) {
      draft.managers = await this.addManagers(commissioner, draft.id, managerNames);
    }

    if (status !== 'Setting Up') {
      await this.setDraftStatus(commissioner, draft.id, 'In Progress');
    }

    if (status === 'Complete') {
      const picks = (options.rounds ?? 2) * draft.managers.length;

      for (let pick = 1; pick <= picks; pick++) {
        await this.addPick(commissioner, draft.id);
      }
    }

    return draft;
  }

  /** Moves a Draft to Setting Up or In Progress. A Draft becomes Complete only with its last Pick. */
  async setDraftStatus(
    commissioner: LoggedInUser,
    draftId: number,
    status: Exclude<DraftStatus, 'Complete'>,
  ): Promise<void> {
    await send(
      () => commissioner.api.put(`commish/draft/${draftId}/status`, { data: { status: STATUS_VALUES[status] } }),
      `move the Draft to ${status}`,
    );
  }

  /** Adds Managers after the existing ones. Returns each Manager of the Draft in Draft Order. */
  async addManagers(commissioner: LoggedInUser, draftId: number, names: string[]): Promise<Manager[]> {
    await send(
      () =>
        commissioner.api.post(`commish/draft/${draftId}/managers`, {
          data: { managers: names.map((name) => ({ manager_name: name })) },
        }),
      'add Managers',
    );

    const managers = await send<{ manager_id: number; manager_name: string }[]>(
      () => commissioner.api.get(`commish/draft/${draftId}/managers`),
      'list the Managers',
    );

    return managers.map((manager) => ({ id: Number(manager.manager_id), name: manager.manager_name }));
  }

  /** Replaces the Round Times of a Draft. An empty list turns the Pick Timer off. */
  async setRoundTimes(commissioner: LoggedInUser, draftId: number, roundTimes: RoundTime[]): Promise<void> {
    await send(
      () =>
        commissioner.api.post(`commish/draft/${draftId}/timers`, {
          data: {
            isRoundTimesEnabled: roundTimes.length > 0,
            roundTimes: roundTimes.map((roundTime) => ({
              // The API compares this field with the string "true"
              is_static_time: roundTime.round === undefined ? 'true' : 'false',
              draft_round: roundTime.round ?? null,
              round_time_seconds: roundTime.seconds,
            })),
          },
        }),
      'set the Round Times',
    );
  }

  /** Enters the current Pick of an In Progress Draft. Without `entry`, the Pick gets a name that no other Pick has. */
  async addPick(commissioner: LoggedInUser, draftId: number, entry: Partial<PickEntry> = {}): Promise<SelectedPick> {
    const { pick: current } = await send<{
      pick: { player_id: number; player_round: number; player_pick: number; manager_id: number };
    }>(() => commissioner.api.get(`commish/draft/${draftId}/pick/current`), 'load the current Pick');

    const filled: PickEntry = {
      firstName: entry.firstName ?? 'Pick',
      lastName: entry.lastName ?? unique('Entry'),
      team: entry.team ?? DEFAULT_PICK.team,
      position: entry.position ?? DEFAULT_PICK.position,
    };

    await send(
      () =>
        commissioner.api.post(`commish/draft/${draftId}/pick/${current.player_id}`, {
          data: {
            first_name: filled.firstName,
            last_name: filled.lastName,
            team: filled.team,
            position: filled.position,
          },
        }),
      'add a Pick',
    );

    return {
      ...filled,
      id: Number(current.player_id),
      round: Number(current.player_round),
      number: Number(current.player_pick),
      managerId: Number(current.manager_id),
    };
  }

  /** Enters a Trade between two Managers of an In Progress Draft. */
  async addTrade(commissioner: LoggedInUser, draftId: number, trade: TradeEntry): Promise<void> {
    await send(
      () =>
        commissioner.api.post(`commish/draft/${draftId}/trade`, {
          data: { manager1_id: trade.manager1Id, manager2_id: trade.manager2Id, trade_assets: trade.pickIds },
        }),
      'add a Trade',
    );
  }

  /** Replaces the Pro Player list of one League with a CSV file. Needs an Admin. */
  async uploadProPlayers(admin: LoggedInUser, league: League, csvPath: string): Promise<void> {
    await send(
      () =>
        admin.api.post('admin/proplayers', {
          multipart: {
            league,
            file: { name: 'pro-players.csv', mimeType: 'text/csv', buffer: fs.readFileSync(csvPath) },
          },
          timeout: 120_000,
        }),
      `upload the ${league} Pro Players`,
    );
  }

  /** Gives a browser context the login session of the user, so a test starts logged in without the login screen. */
  async logInBrowser(context: BrowserContext, user: LoggedInUser): Promise<void> {
    const { cookies } = await user.api.storageState();

    await context.addCookies(cookies);
  }

  async dispose(): Promise<void> {
    await Promise.all(this.sessions.map((session) => session.dispose()));
    this.sessions.length = 0;
  }

  private async register(
    overrides: Partial<Credentials>,
  ): Promise<{ credentials: Credentials; api: APIRequestContext }> {
    const name = overrides.name ?? unique('Commissioner');
    const credentials: Credentials = {
      name,
      email: overrides.email ?? emailFor({ name }),
      password: overrides.password ?? 'commissioner-password',
    };
    const api = await this.newSession();

    await send(
      () =>
        api.post('register', {
          data: {
            _name: credentials.name,
            _email: credentials.email,
            _password: credentials.password,
            _confirmPassword: credentials.password,
            // Google's test secret accepts each response value
            _recaptcha: 'e2e',
          },
        }),
      'register a user',
    );

    return { credentials, api };
  }

  private async newSession(): Promise<APIRequestContext> {
    const session = await request.newContext({
      baseURL: API_URL,
      // The API refuses a request that changes state without this header (CsrfHeaderListener)
      extraHTTPHeaders: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    this.sessions.push(session);

    return session;
  }
}
