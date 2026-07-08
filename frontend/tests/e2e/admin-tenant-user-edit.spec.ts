import { test, expect } from '@playwright/test';
import { signInAsAdmin } from './support/admin-tenant-user-edit';

test('super-admin can edit an enterprise user and see the updated values', async ({ page }) => {
  await signInAsAdmin(page);

  await page.getByRole('link', { name: 'Utenti' }).click();
  await expect(page.getByText('Utenti enterprise')).toBeVisible();

  await page.getByRole('textbox', { name: 'Cerca per nome o email' }).fill('viewer@assistdoc.local');
  await page.getByRole('button', { name: 'Cerca' }).click();

  const userRow = page.locator('.users-page__item').filter({ hasText: 'viewer@assistdoc.local' });
  await expect(userRow).toBeVisible();

  await userRow.getByRole('button', { name: 'Modifica' }).click();

  const modal = page.locator('.modal-dialog');
  await expect(modal.getByText('Modifica utente')).toBeVisible();

  await modal.getByLabel('Nome completo').fill('Viewer E2E');
  await modal.getByLabel('Email').fill('viewer.e2e@example.test');
  await modal.getByLabel('Tenant ID').fill('1');
  await modal.getByRole('button', { name: 'Salva modifiche' }).click();

  await expect(page.getByText('Utente aggiornato correttamente.')).toBeVisible();
  await expect(page.getByText('Viewer E2E')).toBeVisible();
  await expect(page.getByText('viewer.e2e@example.test')).toBeVisible();
});
