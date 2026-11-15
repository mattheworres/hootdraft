import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { parseEnv } from 'node:util';

const STACK_DIR = path.join(__dirname, '..', 'stack');
const STACK_ENV = parseEnv(readFileSync(path.join(STACK_DIR, 'e2e.env'), 'utf8'));

function stackEnv(name: string): string {
  const value = STACK_ENV[name];

  if (value === undefined) {
    throw new Error(`stack/e2e.env does not set ${name}`);
  }

  return value;
}

// Addresses and credentials of the E2E stack. The host ports match stack/docker-compose.yml.
export const STACK_URL = stackEnv('APP_BASE_URL');
export const API_URL = `${stackEnv('API_BASE_URL')}/`;
export const MAILPIT_URL = 'http://localhost:8125/api/v1/';

export const DATABASE = {
  host: '127.0.0.1',
  port: 3407,
  database: stackEnv('MARIADB_DATABASE'),
  user: stackEnv('MARIADB_USER'),
  password: stackEnv('MARIADB_PASSWORD'),
  rootPassword: stackEnv('MARIADB_ROOT_PASSWORD'),
} as const;

/** The value of users.roles for an Admin, as the API stores it. */
export const ADMIN_ROLES = 'ROLE_COMMISH,ROLE_ADMIN';

/** The Admin that the database reset creates. The API has no route that makes the first Admin. */
export const FIRST_ADMIN = {
  name: 'First Admin',
  email: 'first-admin@e2e.example',
  password: 'first-admin-password',
} as const;

const COMPOSE_FILE = path.join(STACK_DIR, 'docker-compose.yml');

export function execInStack(service: 'php' | 'db', command: string[]): string {
  return execFileSync('docker', ['compose', '-f', COMPOSE_FILE, 'exec', '-T', service, ...command], {
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}
