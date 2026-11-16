import { expect, Page } from '@playwright/test';

// The reCAPTCHA box is a Google iframe, which takes no data-testid. Its title is its accessible name.
export function recaptchaCheckbox(page: Page) {
  return page
    .getByTitle('reCAPTCHA', { exact: true })
    .contentFrame()
    .getByRole('checkbox', { name: "I'm not a robot" });
}

/** The E2E stack uses Google's public test keys, so the checkbox passes without a challenge. */
export async function completeRecaptcha(page: Page): Promise<void> {
  await recaptchaCheckbox(page).click();
  await expect(recaptchaCheckbox(page)).toBeChecked();
}
