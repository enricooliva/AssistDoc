import { test, expect } from '@playwright/test';

test('documents page starts document upload without exposing profile selection', async ({ page }) => {
  await page.goto('/documents');
  await expect(page.getByText('Carica documento')).toBeVisible();
  await expect(page.getByText('Documento')).toBeVisible();
  await expect(page.getByText('Profilo modello')).toHaveCount(0);
  await expect(page.getByText('Profilo segmentazione')).toHaveCount(0);
});
