import { expect, test } from '@playwright/test';

test('tenant administration page is available to super-admins', async ({ page }) => {
  await page.goto('/sign-in');
  await page.getByLabel('Email').fill('admin@assistdoc.local');
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: 'Entra' }).click();
  await page.getByRole('link', { name: 'Tenant' }).click();

  await expect(page.getByText('Tenant')).toBeVisible();
  await expect(page.getByText('Nuovo tenant')).toBeVisible();
  await expect(page.getByText('Aggiungi utente')).toBeVisible();
});
