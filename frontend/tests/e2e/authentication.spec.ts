import { expect, test } from '@playwright/test';

test('successful sign-in reaches the authenticated shell', async ({ page }) => {
  await page.goto('/sign-in');
  await page.getByRole('textbox', { name: 'Email' }).fill('viewer@assistdoc.local');
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: 'Entra' }).click();

  await expect(page.getByText('Assistente documentale privato')).toBeVisible();
});

test('unauthenticated users are redirected to sign-in', async ({ page }) => {
  await page.goto('/audit');

  await expect(page.getByText('Accedi ad AssistDoc')).toBeVisible();
});
