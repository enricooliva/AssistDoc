import { expect, test } from '@playwright/test';
import {
  createConversations,
  setLandscapeViewport,
  setMobileViewport,
  signInAsViewer,
} from './support/responsive-layout';

test('mobile shell opens the lateral menu as an overlay', async ({ page }) => {
  await setMobileViewport(page);
  await signInAsViewer(page);

  await page.getByRole('button', { name: 'Apri menu laterale' }).click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();
  await expect(dialog.getByRole('button', { name: 'Chiudi menu' })).toBeVisible();

  await dialog.getByRole('button', { name: 'Chiudi menu' }).click();
  await expect(dialog).toHaveCount(0);
  await expect(page.getByText('Assistente documentale privato')).toBeVisible();
});

test('chat history stays contained and scrollable on mobile screens', async ({ page }) => {
  await setMobileViewport(page);
  await signInAsViewer(page);
  await createConversations(page, 10);

  const history = page.locator('.chat-layout__history');
  await expect(history).toBeVisible();

  const hasInternalOverflow = await history.evaluate((element) => element.scrollHeight > element.clientHeight);
  const hasPageOverflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);

  expect(hasInternalOverflow).toBeTruthy();
  expect(hasPageOverflow).toBeFalsy();
});

test('mobile layout remains readable after rotating to landscape', async ({ page }) => {
  await setMobileViewport(page);
  await signInAsViewer(page);

  await page.getByRole('button', { name: 'Apri menu laterale' }).click();
  await expect(page.getByRole('dialog')).toBeVisible();

  await setLandscapeViewport(page);

  await expect(page.getByText('Assistente documentale privato')).toBeVisible();
  await expect(page.getByRole('dialog')).toBeVisible();

  const hasPageOverflow = await page.evaluate(
    () => document.documentElement.scrollWidth > document.documentElement.clientWidth,
  );

  expect(hasPageOverflow).toBeFalsy();
});
