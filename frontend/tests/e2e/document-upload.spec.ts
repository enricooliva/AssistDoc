import { test, expect } from '@playwright/test';

test('document upload page exposes the ingestion UI shell', async ({ page }) => {
  await page.goto('/sign-in');
  await page.getByLabel('Email').fill('operator@assistdoc.local');
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: 'Entra' }).click();
  await page.getByRole('link', { name: 'Documenti' }).click();

  await expect(page.getByText('Carica documento')).toBeVisible();
  await expect(page.getByText('Documenti del tenant')).toBeVisible();
});
