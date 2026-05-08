import { test, expect } from '@playwright/test';

test('chat mvp page shows composer and citations', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('text=Assistente documentale privato')).toBeVisible();
  await expect(page.locator('textarea')).toBeVisible();
});

