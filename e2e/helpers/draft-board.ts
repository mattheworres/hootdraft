import { expect, Page } from '@playwright/test';
import { draftIndexRoute, DraftRoute } from './draft-screen';

export const boardRoute: DraftRoute = (draft) => `${draftIndexRoute(draft)}/board`;

/** The Pick cells of the Draft Board, in Board order. */
export const boardPicks = (page: Page) => page.getByTestId('board-pick');

export const pickTimer = (page: Page) => page.getByTestId('pick-timer');
export const pickTimerDisabled = (page: Page) => page.getByTestId('pick-timer-disabled');

/** The seconds on the flip clock. The library renders each digit as a list item, and one item of each digit shows. */
export async function flipClockSeconds(page: Page): Promise<number> {
  const digits = await pickTimer(page).locator('.flip-clock-active').allInnerTexts();
  // Each digit shows twice, once for each half of the flipping card
  const [m1, m2, s1, s2] = digits.map((digit) => Number(digit.trim()[0]));

  return (m1 * 10 + m2) * 60 + s1 * 10 + s2;
}

/** The API measures the Pick Timer on the database clock, so a test can only expect a range of seconds. */
export async function expectFlipClockBetween(page: Page, min: number, max: number): Promise<void> {
  await expect
    .poll(async () => (await flipClockSeconds(page)) >= min && (await flipClockSeconds(page)) <= max)
    .toBe(true);
}
