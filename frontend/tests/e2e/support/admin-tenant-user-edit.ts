import { expect, Page } from '@playwright/test';

export async function signInAsTenantAdmin(page: Page): Promise<void> {
  await page.goto('/sign-in');
  await page.getByLabel('Email').fill('tenant-admin@assistdoc.local');
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: 'Entra' }).click();

  await expect(page.getByText('Assistente documentale privato')).toBeVisible();
}

export async function signInAsAdmin(page: Page): Promise<void> {
  await page.goto('/sign-in');
  await page.getByLabel('Email').fill('admin@assistdoc.local');
  await page.getByLabel('Password').fill('password123');
  await page.getByRole('button', { name: 'Entra' }).click();

  await expect(page.getByText('Assistente documentale privato')).toBeVisible();
}
