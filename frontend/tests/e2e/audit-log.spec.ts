import { test, expect } from '@playwright/test';

test('audit feature placeholder is represented in repo', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('text=Audit')).toHaveCount(1);
});
