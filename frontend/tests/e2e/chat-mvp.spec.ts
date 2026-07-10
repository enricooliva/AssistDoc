import { test, expect } from '@playwright/test';

test('chat mvp page shows composer and citations', async ({ page }) => {
  await page.goto('/sign-in');
  await page.getByLabel('Email').fill('viewer@assistdoc.local');
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: 'Entra' }).click();

  await expect(page.getByText('Assistente documentale privato')).toBeVisible();
  await expect(page.locator('textarea')).toBeVisible();
  await expect(page.getByText('Citazioni')).toBeVisible();
});

test('chat mvp page validates empty questions before submission', async ({ page }) => {
  await page.goto('/sign-in');
  await page.getByLabel('Email').fill('viewer@assistdoc.local');
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: 'Entra' }).click();

  await page.getByRole('button', { name: 'Invia' }).click();

  await expect(page.getByText('Inserisci una domanda prima di inviare il messaggio.')).toBeVisible();
});
