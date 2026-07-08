import { expect, Page } from '@playwright/test';

export const mobileViewport = { width: 390, height: 844 };
export const landscapeViewport = { width: 844, height: 390 };

export async function setMobileViewport(page: Page): Promise<void> {
  await page.setViewportSize(mobileViewport);
}

export async function setLandscapeViewport(page: Page): Promise<void> {
  await page.setViewportSize(landscapeViewport);
}

export async function signInAsViewer(page: Page): Promise<void> {
  await page.goto('/sign-in');
  await page.getByLabel('Email').fill('viewer@assistdoc.local');
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: 'Entra' }).click();

  await expect(page.getByText('Assistente documentale privato')).toBeVisible();
}

export async function createConversations(page: Page, count: number): Promise<void> {
  const newChatButton = page.getByRole('button', { name: 'Nuova chat' });
  const startingCount = await page.locator('.chat-layout__history-row').count();

  for (let index = 0; index < count; index += 1) {
    await newChatButton.click();
    await expect(page.locator('.chat-layout__history-row')).toHaveCount(startingCount + index + 1);
  }
}
