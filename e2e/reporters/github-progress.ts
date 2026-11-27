import type { FullConfig, FullResult, Reporter, Suite, TestCase, TestResult } from '@playwright/test/reporter';

// The job credential allows 1,000 API requests an hour per repository, shared by every job of every run
const UPDATE_INTERVAL_MS = 15_000;

const CONCLUSIONS: Record<FullResult['status'], string> = {
  passed: 'success',
  failed: 'failure',
  timedout: 'timed_out',
  interrupted: 'cancelled',
};

/** Counts the tests of a run. A test counts once, after its last attempt. */
export class ProgressCount {
  done = 0;
  failed = 0;

  constructor(readonly total: number) {}

  record(test: Pick<TestCase, 'outcome' | 'retries'>, result: Pick<TestResult, 'retry'>): void {
    const outcome = test.outcome();

    if (outcome === 'unexpected' && result.retry < test.retries) {
      return;
    }

    this.done++;
    if (outcome === 'unexpected') {
      this.failed++;
    }
  }

  get title(): string {
    const failed = this.failed > 0 ? ` · ${this.failed} failed` : '';

    return `${this.done}/${this.total}${failed}`;
  }
}

interface CheckSettings {
  credential: string;
  /** The URL of the check-runs endpoint of the repository. */
  checkRunsUrl: string;
  name: string;
  sha: string;
}

const REQUIRED_ENV = ['GITHUB_TOKEN', 'GITHUB_REPOSITORY', 'E2E_PROGRESS_CHECK', 'E2E_PROGRESS_SHA'] as const;

function settingsFromEnv(): CheckSettings | null {
  const values = REQUIRED_ENV.map((key) => process.env[key] ?? '');

  if (values.includes('')) {
    return null;
  }

  const [credential, repository, name, sha] = values;
  const api = process.env.GITHUB_API_URL ?? 'https://api.github.com';

  return { credential, checkRunsUrl: `${api}/repos/${repository}/check-runs`, name, sha };
}

/**
 * Shows the progress of the run as a GitHub check, for example "120/205 · 2 failed". The check of the job itself
 * cannot change its text, so this adds a second check. It does nothing outside CI, and it never fails the run.
 */
export default class GitHubProgressReporter implements Reporter {
  private readonly settings = settingsFromEnv();
  private count = new ProgressCount(0);
  private checkRunUrl: Promise<string | null> = Promise.resolve(null);
  private lastUpdate = 0;
  private update: Promise<void> | null = null;

  printsToStdio(): boolean {
    return false;
  }

  onBegin(_config: FullConfig, suite: Suite): void {
    this.count = new ProgressCount(suite.allTests().length);
    this.lastUpdate = Date.now();

    if (this.settings) {
      this.checkRunUrl = this.createCheckRun(this.settings);
    }
  }

  onTestEnd(test: TestCase, result: TestResult): void {
    this.count.record(test, result);

    if (this.update === null && Date.now() - this.lastUpdate >= UPDATE_INTERVAL_MS) {
      this.lastUpdate = Date.now();
      this.update = this.sendUpdate({ status: 'in_progress' }).finally(() => (this.update = null));
    }
  }

  async onEnd(result: FullResult): Promise<void> {
    await this.update;
    await this.sendUpdate({
      status: 'completed',
      conclusion: CONCLUSIONS[result.status],
      completed_at: new Date().toISOString(),
    });
  }

  private async createCheckRun(settings: CheckSettings): Promise<string | null> {
    try {
      const checkRun = await this.request('POST', settings.checkRunsUrl, {
        name: settings.name,
        head_sha: settings.sha,
        status: 'in_progress',
        started_at: new Date().toISOString(),
        output: this.output(),
      });

      return checkRun.url;
    } catch (error) {
      // A pull request from a fork gets a read-only credential, which cannot add a check
      console.warn(`The progress check is off: ${error}`);

      return null;
    }
  }

  private async sendUpdate(fields: Record<string, string>): Promise<void> {
    const url = await this.checkRunUrl;

    if (url === null) {
      return;
    }

    try {
      await this.request('PATCH', url, { ...fields, output: this.output() });
    } catch (error) {
      console.warn(`The progress check did not update: ${error}`);
    }
  }

  private output() {
    const { done, failed, total, title } = this.count;

    return { title, summary: `${done} of ${total} tests done. ${failed} failed.` };
  }

  private async request(method: 'POST' | 'PATCH', url: string, body: object): Promise<{ url: string }> {
    const response = await fetch(url, {
      method,
      headers: {
        Authorization: `Bearer ${this.settings!.credential}`,
        Accept: 'application/vnd.github+json',
        'X-GitHub-Api-Version': '2022-11-28',
      },
      body: JSON.stringify(body),
    });

    if (!response.ok) {
      throw new Error(`${method} ${url} answered ${response.status}: ${await response.text()}`);
    }

    return response.json();
  }
}
