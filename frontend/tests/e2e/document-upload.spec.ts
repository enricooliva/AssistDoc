import { test, expect } from '@playwright/test';

test('document upload feature placeholder is represented in repo', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('text=Documenti')).toHaveCount(1);
});

