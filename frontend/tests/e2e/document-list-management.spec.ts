import { test, expect } from '@playwright/test';

test('document list exposes pagination and delete confirmation', async ({ page }) => {
  await page.goto('/sign-in');
  await page.getByLabel('Email').fill('operator@assistdoc.local');
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: 'Entra' }).click();
  await page.getByRole('link', { name: 'Documenti' }).click();

  await expect(page.getByText('Documenti del tenant')).toBeVisible();
  await expect(page.getByText('Pagina')).toBeVisible();
  await expect(page.getByRole('button', { name: 'Elimina' }).first()).toBeVisible();

  await page.getByRole('button', { name: 'Elimina' }).first().click();
  await expect(page.getByText('La rimozione sarà una soft delete')).toBeVisible();
});
