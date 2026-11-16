import path from 'node:path';
import { ApiSetup, League } from './helpers/api';
import { deleteAllEmails } from './helpers/mailpit';
import { ADMIN_ROLES, API_URL, DATABASE, FIRST_ADMIN, STACK_URL, execInStack } from './helpers/stack';

const RESOURCES = path.join(__dirname, '..', 'resources');

const PRO_PLAYER_FILES: Record<League, string> = {
  NFL: 'ProPlayers_NFLStandard_2021.csv',
  NFLE: 'ProPlayers_NFLExtended_2021.csv',
  MLB: 'ProPlayers_MLB_2019.csv',
  NBA: 'ProPlayers_NBA_2019-2020.csv',
  NHL: 'ProPlayers_NHL_2019-2020.csv',
  S15: 'ProPlayers_SuperRugby_2021.csv',
};

function sql(statement: string): void {
  execInStack('db', ['mariadb', '-uroot', `-p${DATABASE.rootPassword}`, '-e', statement]);
}

async function assertStackIsUp(): Promise<void> {
  try {
    await fetch(new URL('draftOptions', API_URL));
  } catch {
    throw new Error(`The E2E stack does not answer on ${STACK_URL}. Start it with: npm run stack:up`);
  }
}

/** Runs once before each run: an empty database with the first Admin and the Pro Player list. */
export default async function globalSetup(): Promise<void> {
  await assertStackIsUp();

  sql(`DROP DATABASE IF EXISTS ${DATABASE.database}; CREATE DATABASE ${DATABASE.database}`);
  // The stack runs the API without debug, so its container cache does not follow code changes by itself
  execInStack('php', ['sh', '-c', 'rm -rf var/cache/* && vendor/bin/phinx migrate -e production']);
  await deleteAllEmails();

  const setup = new ApiSetup();

  try {
    await setup.registerCommissioner(FIRST_ADMIN);
    // The API has no route that makes the first Admin, so the reset gives the role itself
    sql(`UPDATE ${DATABASE.database}.users SET roles = '${ADMIN_ROLES}' WHERE email = '${FIRST_ADMIN.email}'`);

    const firstAdmin = await setup.logIn(FIRST_ADMIN);

    for (const [league, file] of Object.entries(PRO_PLAYER_FILES)) {
      await setup.uploadProPlayers(firstAdmin, league as League, path.join(RESOURCES, file));
    }
  } finally {
    await setup.dispose();
  }
}
