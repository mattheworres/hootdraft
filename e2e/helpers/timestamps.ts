import mysql from 'mysql2/promise';
import { DATABASE } from './stack';

/**
 * Server time for tests. The Playwright clock moves client timers only, so a test that needs the server to see a
 * different time moves a stored timestamp into the past. This file is the only code that writes test state to the E2E database:
 * everything else goes through the API. Add a function here before you write a timestamp from anywhere else.
 */

// stack/e2e.env sets CACHE_SECONDS=1: the API can answer from a cached Draft row for this long after the write
const DRAFT_CACHE_MS = 2_000;

/** How long before now, for example `{ minutes: 11 }`. */
export interface Age {
  seconds?: number;
  minutes?: number;
  hours?: number;
}

/** A timestamp column and the column that finds its row. */
interface TimestampColumn {
  table: string;
  column: string;
  keyColumn: string;
}

const DRAFT_START: TimestampColumn = { table: 'draft', column: 'draft_start_time', keyColumn: 'draft_id' };
const DRAFT_END: TimestampColumn = { table: 'draft', column: 'draft_end_time', keyColumn: 'draft_id' };
const PICK_TIME: TimestampColumn = { table: 'players', column: 'pick_time', keyColumn: 'player_id' };
const EMAIL_VERIFICATION_KEY_CREATED: TimestampColumn = {
  table: 'users',
  column: 'emailVerificationKeyCreated',
  keyColumn: 'email',
};
const RESET_KEY_CREATED: TimestampColumn = { table: 'users', column: 'resetKeyCreated', keyColumn: 'email' };

/** Sets when the Draft went In Progress. The Pick Timer of the first Pick counts from here. */
export async function setDraftStartTime(draftId: number, ago: Age): Promise<void> {
  await writeTimestamp(DRAFT_START, draftId, ago);
  await waitForDraftCacheToExpire();
}

/** Sets when the Draft became Complete. The API permits Depth Chart changes for ten minutes after this. */
export async function setDraftEndTime(draftId: number, ago: Age): Promise<void> {
  await writeTimestamp(DRAFT_END, draftId, ago);
  await waitForDraftCacheToExpire();
}

/** Sets when the Pick was selected. The Pick Timer of the next Pick counts from here. */
export async function setPickTime(pickId: number, ago: Age): Promise<void> {
  await writeTimestamp(PICK_TIME, pickId, ago);
}

/** Sets when the API issued the Email Verification Key of the user. The key expires 48 hours later. */
export async function setEmailVerificationKeyTime(email: string, ago: Age): Promise<void> {
  await writeTimestamp(EMAIL_VERIFICATION_KEY_CREATED, email.toLowerCase(), ago);
}

/** Sets when the API issued the Reset Key of the user. The key expires one hour later. */
export async function setResetKeyTime(email: string, ago: Age): Promise<void> {
  await writeTimestamp(RESET_KEY_CREATED, email.toLowerCase(), ago);
}

// Table and column names come from the constants above only, never from a test
async function writeTimestamp(
  { table, column, keyColumn }: TimestampColumn,
  key: number | string,
  ago: Age,
): Promise<void> {
  const connection = await mysql.createConnection({
    host: DATABASE.host,
    port: DATABASE.port,
    database: DATABASE.database,
    user: DATABASE.user,
    password: DATABASE.password,
  });

  try {
    const seconds = (ago.seconds ?? 0) + (ago.minutes ?? 0) * 60 + (ago.hours ?? 0) * 3600;
    // The database clock sets the time, as it does for the API. The clock of the machine that runs the tests
    // can differ from it: the Docker VM clock falls behind when a laptop sleeps.
    const [result] = await connection.execute<mysql.ResultSetHeader>(
      `UPDATE \`${table}\` SET \`${column}\` = UTC_TIMESTAMP() - INTERVAL ? SECOND WHERE \`${keyColumn}\` = ?`,
      [seconds, key],
    );

    if (result.affectedRows !== 1) {
      throw new Error(`No row in ${table} has ${keyColumn} = ${key}, so ${column} was not set`);
    }
  } finally {
    await connection.end();
  }
}

function waitForDraftCacheToExpire(): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, DRAFT_CACHE_MS));
}
