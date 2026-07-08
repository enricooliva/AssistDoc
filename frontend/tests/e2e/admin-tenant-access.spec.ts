import { test, expect } from '@playwright/test';
import { signInAsTenantAdmin } from './support/admin-tenant-user-edit';

test('tenant-admin can open chat and documents', async ({ page }) => {
  await signInAsTenantAdmin(page);

  await expect(page.getByText('Citazioni')).toBeVisible();

  await page.getByRole('link', { name: 'Documenti' }).click();

  await expect(page.getByText('Carica documento')).toBeVisible();
  await expect(page.getByText('Documenti del tenant')).toBeVisible();
});
