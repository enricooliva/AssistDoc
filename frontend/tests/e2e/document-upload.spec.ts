import { test, expect } from '@playwright/test';

test('document upload page exposes the ingestion UI shell', async ({ page }) => {
  await page.goto('/sign-in');
  await page.getByLabel('Email').fill('operator@assistdoc.local');
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: 'Entra' }).click();
  await page.getByRole('link', { name: 'Documenti' }).click();

  await expect(page.getByText('Carica documento')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Carica file' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Incolla testo' })).toBeVisible();
  await page.getByRole('button', { name: 'Incolla testo' }).click();
  await expect(page.getByText('Titolo contenuto')).toBeVisible();
  await expect(page.getByText('Testo')).toBeVisible();
  await expect(page.getByText('Preparazione RAG')).toBeVisible();
  await expect(page.getByText('Documenti del tenant')).toBeVisible();
});
