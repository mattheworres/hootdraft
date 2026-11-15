import { expect } from '@playwright/test';
import { MAILPIT_URL } from './stack';

export interface EmailedKey {
  /** The Email Verification Key or the Reset Key. */
  key: string;
  /** The emailed link as the user receives it, for example `http://localhost:8100/verify/(email)/(key)`. */
  link: string;
}

interface MessageSummary {
  ID: string;
}

interface Message {
  Text: string;
}

async function mailpit(pathAndQuery: string, init?: RequestInit): Promise<string> {
  const response = await fetch(new URL(pathAndQuery, MAILPIT_URL), init);

  if (!response.ok) {
    throw new Error(`Mailpit answered ${response.status} for ${pathAndQuery}`);
  }

  return response.text();
}

async function mailpitJson<T>(pathAndQuery: string): Promise<T> {
  return JSON.parse(await mailpit(pathAndQuery)) as T;
}

/** Finds the newest email to the address that holds a link to one of the client routes. Waits for the email. */
async function newestLink(email: string, routes: string[]): Promise<EmailedKey> {
  const linkPattern = new RegExp(`https?://\\S+/(?:${routes.join('|')})/\\S+/([\\w%]+)`);
  let found: EmailedKey | undefined;

  await expect(async () => {
    const query = encodeURIComponent(`to:"${email}"`);
    const { messages } = await mailpitJson<{ messages: MessageSummary[] }>(`search?query=${query}`);

    // Mailpit lists the newest email first
    for (const summary of messages) {
      const message = await mailpitJson<Message>(`message/${summary.ID}`);
      const match = linkPattern.exec(message.Text);

      if (match) {
        found = { key: decodeURIComponent(match[1]), link: match[0] };
        return;
      }
    }

    throw new Error(`Mailpit holds no email to ${email} with a /${routes.join(' or /')} link`);
  }).toPass({ timeout: 10_000 });

  return found!;
}

/** Reads the Email Verification Key from the newest email with an Email Verification Key link to the address. */
export function readEmailVerificationKey(email: string): Promise<EmailedKey> {
  return newestLink(email, ['verify']);
}

/** Reads the Reset Key from the newest lost password email or invite email to the address. */
export function readResetKey(email: string): Promise<EmailedKey> {
  return newestLink(email, ['resetPassword', 'inviteSetup']);
}

export async function deleteAllEmails(): Promise<void> {
  await mailpit('messages', { method: 'DELETE' });
}
