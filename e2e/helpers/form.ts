import { expect, Locator, Page } from '@playwright/test';

export function emailField(page: Page) {
  return page.getByRole('textbox', { name: 'Email' });
}

export async function expectEmailValidationMessages(page: Page): Promise<void> {
  await emailField(page).focus();
  await emailField(page).blur();
  await expect(page.getByText('Email is missing')).toBeVisible();

  await emailField(page).fill('abc');
  await emailField(page).blur();
  await expect(page.getByText('Email is invalid')).toBeVisible();
  await expect(page.getByText('Email is missing')).toBeHidden();
}

export async function expectPasswordValidationMessages(page: Page, passwordField: Locator): Promise<void> {
  await passwordField.focus();
  await passwordField.blur();
  await expect(page.getByText('Password is missing', { exact: true })).toBeVisible();

  await passwordField.fill('1234567');
  await passwordField.blur();
  await expect(page.getByText('Password is too short - 8 characters minimum', { exact: true })).toBeVisible();
  await expect(page.getByText('Password is missing', { exact: true })).toBeHidden();
}

/** Fill the Password field first: the mismatch message compares against it. */
export async function expectConfirmValidationMessages(page: Page, confirmField: Locator): Promise<void> {
  await confirmField.focus();
  await confirmField.blur();
  await expect(page.getByText('You must confirm your password')).toBeVisible();

  await confirmField.fill('another-value');
  await confirmField.blur();
  await expect(page.getByText('Passwords do not match.')).toBeVisible();
  await expect(page.getByText('You must confirm your password')).toBeHidden();
}
