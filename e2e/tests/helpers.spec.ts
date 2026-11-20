import { LoggedInUser } from '../helpers/api';
import { clientUnderTest, knownFailureOnAngularJs } from '../helpers/client';
import { failApiRequest } from '../helpers/intercept';
import { readEmailVerificationKey, readResetKey } from '../helpers/mailpit';
import { API_URL, STACK_URL } from '../helpers/stack';
import { expect, test } from '../helpers/test';
import {
  setDraftCreateTime,
  setDraftEndTime,
  setDraftStartTime,
  setPickTime,
  setResetKeyTime,
} from '../helpers/timestamps';

// These tests check the helpers against the real API. They are not inventory rows.

async function getJson(user: LoggedInUser, path: string) {
  return (await user.api.get(path)).json();
}

test.describe('API setup helpers', () => {
  test('registerCommissioner makes a logged-in Commissioner who is not an Admin', async ({ setup }) => {
    const commissioner = await setup.registerCommissioner();

    const me = await getJson(commissioner, 'me');

    expect(me).toMatchObject({ name: commissioner.name, is_admin: false });
  });

  test('registerAdmin makes a user who logs in as an Admin', async ({ setup }) => {
    const admin = await setup.registerAdmin();

    const me = await getJson(await setup.logIn(admin), 'me');

    expect(me).toMatchObject({ name: admin.name, is_admin: true });
  });

  test('registerEmailUnverifiedUser makes a user who cannot log in until the link is followed', async ({
    setup,
    request,
  }) => {
    const user = await setup.registerEmailUnverifiedUser();
    const login = () => setup.logIn(user);

    await expect(login()).rejects.toThrow('still unverified');

    const { key } = await readEmailVerificationKey(user.email);
    await request.post(`${API_URL}emailVerification`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      data: { _email: user.email, _emailVerificationKey: key },
    });
    await expect(login()).resolves.toMatchObject({ email: user.email });
  });

  test('setUserEnabled disables a user, so the login fails and the login session ends', async ({ setup }) => {
    const admin = await setup.registerAdmin();
    const commissioner = await setup.registerCommissioner();

    await setup.setUserEnabled(admin, commissioner, false);

    expect((await commissioner.api.get('me')).status()).toBe(401);
    await expect(setup.logIn(commissioner)).rejects.toThrow('Invalid credentials');

    await setup.setUserEnabled(admin, commissioner, true);

    await expect(setup.logIn(commissioner)).resolves.toMatchObject({ email: commissioner.email });
  });

  for (const [status, apiValue] of [
    ['Setting Up', 'undrafted'],
    ['In Progress', 'in_progress'],
    ['Complete', 'complete'],
  ] as const) {
    test(`createDraft makes a ${status} Draft`, async ({ setup }) => {
      const commissioner = await setup.registerCommissioner();

      const draft = await setup.createDraft(commissioner, { status });

      const loaded = await getJson(commissioner, `draft/${draft.id}`);
      expect(loaded).toMatchObject({ draft_name: draft.name, draft_status: apiValue });
      expect(draft.managers).toHaveLength(status === 'Setting Up' ? 0 : 2);
    });
  }

  test('createDraft sets the Draft Password', async ({ setup, request }) => {
    const commissioner = await setup.registerCommissioner();

    const draft = await setup.createDraft(commissioner, { status: 'In Progress', password: 'open-sesame' });

    const withoutPassword = await request.get(`${API_URL}draft/${draft.id}/picks`);
    const withPassword = await request.get(`${API_URL}draft/${draft.id}/picks`, {
      headers: { 'X-PhpDraft-DraftPassword': 'open-sesame' },
    });
    expect(withoutPassword.ok()).toBe(false);
    expect(withPassword.ok()).toBe(true);
  });

  test('addManagers returns the Managers in Draft Order', async ({ setup }) => {
    const commissioner = await setup.registerCommissioner();
    const draft = await setup.createDraft(commissioner, { managers: ['First'] });

    const managers = await setup.addManagers(commissioner, draft, ['Second', 'Third']);

    expect(managers.map((manager) => manager.name)).toEqual(['First', 'Second', 'Third']);
  });

  test('addPick enters the current Pick and addTrade moves it to the other Manager', async ({ setup }) => {
    const commissioner = await setup.registerCommissioner();
    const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
    const [first, second] = draft.managers;

    const pick = await setup.addPick(commissioner, draft, { firstName: 'Davante', lastName: 'Adams' });
    await setup.addTrade(commissioner, draft, { manager1Id: first.id, manager2Id: second.id, pickIds: [pick.id] });

    expect(pick).toMatchObject({ round: 1, number: 1, managerId: first.id });
    const picks = await getJson(commissioner, `draft/${draft.id}/manager/${second.id}/picks/selected`);
    expect(JSON.stringify(picks)).toContain('Adams');
    const trades = await getJson(commissioner, `draft/${draft.id}/trades`);
    expect(JSON.stringify(trades)).toContain(first.name);
  });

  test('logInBrowser opens the client as the user', async ({ setup, context, page }) => {
    const commissioner = await setup.registerCommissioner();

    await setup.logInBrowser(context, commissioner);
    await page.goto('/home');

    await expect(page.getByRole('button', { name: 'Log Out' })).toBeVisible();
  });
});

test.describe('Round Times and the timestamp helper', () => {
  test('the Pick Timer counts from the Draft start time, then from the last Pick', async ({ setup }) => {
    const commissioner = await setup.registerCommissioner();
    const draft = await setup.createDraft(commissioner, { status: 'In Progress' });
    await setup.setRoundTimes(commissioner, draft, [{ seconds: 600 }]);
    const secondsRemaining = async () =>
      (await getJson(commissioner, `draft/${draft.id}/timer/remaining`)).seconds_remaining;

    await setDraftStartTime(draft.id, { minutes: 4 });
    const afterStart = await secondsRemaining();

    const pick = await setup.addPick(commissioner, draft);
    await setPickTime(pick.id, { minutes: 9 });
    const afterPick = await secondsRemaining();

    expect(afterStart).toBeGreaterThan(330);
    expect(afterStart).toBeLessThanOrEqual(360);
    expect(afterPick).toBeGreaterThan(30);
    expect(afterPick).toBeLessThanOrEqual(60);
  });

  test('setDraftEndTime moves the time a Draft became Complete', async ({ setup }) => {
    const commissioner = await setup.registerCommissioner();
    const draft = await setup.createDraft(commissioner, { status: 'Complete', rounds: 1 });

    const endTime = async () => Date.parse((await getJson(commissioner, `draft/${draft.id}`)).draft_end_time);
    const before = await endTime();

    await setDraftEndTime(draft.id, { minutes: 11 });

    const movedBySeconds = (before - (await endTime())) / 1000;
    expect(movedBySeconds).toBeGreaterThan(650);
    expect(movedBySeconds).toBeLessThanOrEqual(660);
  });

  test('setDraftCreateTime moves the time a Draft was created', async ({ setup }) => {
    const commissioner = await setup.registerCommissioner();
    const draft = await setup.createDraft(commissioner, { rounds: 1 });

    const createTime = async () => {
      const drafts: { draft_id: number; draft_create_time: string }[] = await getJson(commissioner, 'drafts');
      return Date.parse(drafts.find((listed) => listed.draft_id === draft.id)!.draft_create_time);
    };
    const before = await createTime();

    await setDraftCreateTime(draft.id, { hours: 2 });

    const movedBySeconds = (before - (await createTime())) / 1000;
    expect(movedBySeconds).toBeGreaterThan(7190);
    expect(movedBySeconds).toBeLessThanOrEqual(7200);
  });

  test('a timestamp for a row that does not exist is an error', async () => {
    await expect(setPickTime(987654321, { seconds: 1 })).rejects.toThrow('No row in players');
  });
});

test.describe('Mailpit helper', () => {
  test('readResetKey reads a Reset Key that the API accepts until it expires', async ({ setup }) => {
    const commissioner = await setup.registerCommissioner();
    await commissioner.api.post('lostPassword', { data: { _email: commissioner.email, _recaptcha: 'e2e' } });
    const checkResetKey = (key: string) =>
      commissioner.api.get('checkResetKey', { params: { _email: commissioner.email, _resetKey: key } });

    const { key, link } = await readResetKey(commissioner.email);

    expect(link).toContain(`${STACK_URL}/resetPassword/`);
    expect((await checkResetKey(key)).ok()).toBe(true);

    await setResetKeyTime(commissioner.email, { hours: 2 });

    expect((await checkResetKey(key)).ok()).toBe(false);
  });
});

test.describe('Request intercept helper', () => {
  test('failApiRequest answers the route with an error and leaves other routes alone', async ({ page }) => {
    await failApiRequest(page, { method: 'GET', path: 'drafts', status: 503 });

    const drafts = page.waitForResponse((response) => response.url() === `${API_URL}drafts`);
    const style = page.waitForResponse((response) => response.url() === `${API_URL}style`);
    await page.goto('/home');

    expect((await drafts).status()).toBe(503);
    expect((await style).status()).toBe(200);
  });
});

test.describe('Known failure mark', () => {
  // On the new client the mark does nothing, so this failing test would fail the run
  test.skip(clientUnderTest !== 'angularjs', 'The mark only acts on the AngularJS client');

  test('knownFailureOnAngularJs lets a failing test run without failing the suite', async () => {
    knownFailureOnAngularJs('W-00');

    expect('the AngularJS result').toBe('the correct result');
  });
});
