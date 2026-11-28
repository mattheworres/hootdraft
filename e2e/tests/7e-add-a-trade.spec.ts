import { BrowserContext, Locator, Page } from '@playwright/test';
import { ApiSetup, Draft, LoggedInUser, unique } from '../helpers/api';
import { knownFailureOnAngularJs } from '../helpers/client';
import { boardPicks, boardRoute } from '../helpers/draft-board';
import {
  commishActions,
  draftIndexRoute,
  DraftRoute,
  expectDraftStatusRedirect,
  managersApiPath,
  openDraftScreen,
  pickRows,
  tradeAssets,
  tradeCards,
  tradesRoute,
} from '../helpers/draft-screen';
import { failApiRequest, waitForApiResponse } from '../helpers/intercept';
import { expectLoginRoute } from '../helpers/navigation';
import { expect, test } from '../helpers/test';
import { VISIBLE_TEXT } from '../helpers/text';
import { expectExpiredSessionLogout, expectToast, SERVER_ERROR_TOAST } from '../helpers/toast';

// Route group 7E of docs/flow-inventory.md. Each test name starts with the row ID.

const addTradeRoute: DraftRoute = (draft) => `/commish/draft/${draft.id}/trades/add`;
const assetsPath = (draft: Pick<Draft, 'id'>) => new RegExp(`^commish/draft/${draft.id}/manager/\\d+/assets$`);
const savePath = (draft: Pick<Draft, 'id'>) => `commish/draft/${draft.id}/trade`;

const CHOOSE_TWO = 'Choose two managers to enter a trade for:';
const CHOOSE_ASSET = 'Choose at least one asset to trade:';

const tradeHeading = (page: Page) => page.getByRole('heading', { name: 'Enter a new Trade' });

type TradeSide = 'first' | 'second';

/** The names of the two Managers of the Draft of `newInProgressDraft`. */
interface AnnAndBob {
  ann: string;
  bob: string;
}

/** The menu, its options, and the Picks below it of one Manager of the Trade. */
function managerSide(page: Page, side: TradeSide) {
  return {
    menu: page.getByTestId(`${side}-manager-menu`),
    options: page.getByTestId(`${side}-manager-option`),
    assets: page.getByTestId(`${side}-manager-assets`),
  };
}

const assetToggles = (assets: Locator) => assets.getByTestId('trade-asset-toggle');
const chosenMark = (toggle: Locator) => toggle.getByTestId('trade-asset-chosen');
const enterTradeButton = (page: Page) => page.getByRole('button', { name: 'Enter New Trade' });

/** In a Serpentine Draft of two Managers and two Rounds, Ann has Picks 1 and 4, and Bob has Picks 2 and 3. */
async function newInProgressDraft(
  context: BrowserContext,
  setup: ApiSetup,
): Promise<{ commissioner: LoggedInUser; draft: Draft; managers: AnnAndBob }> {
  const managers = { ann: unique('Ann'), bob: unique('Bob') };
  const commissioner = await setup.logInNewCommissioner(context);
  const draft = await setup.createDraft(commissioner, {
    status: 'In Progress',
    managers: [managers.ann, managers.bob],
  });

  return { commissioner, draft, managers };
}

async function openAddTrade(page: Page, draft: Draft) {
  const managersLoaded = waitForApiResponse(page, { method: 'GET', path: managersApiPath(draft) });

  await page.goto(addTradeRoute(draft));
  await managersLoaded;
  await expect(tradeHeading(page)).toBeVisible();
}

async function chooseManager(page: Page, side: TradeSide, name: string) {
  const { menu, options } = managerSide(page, side);

  await menu.click();
  await options.filter({ hasText: name }).click();
  await expect(menu).toContainText(name);
}

/** The state of ADDTRADE-04: Ann and Bob are chosen, with their two Picks each. */
async function chooseAnnAndBob(page: Page, { ann, bob }: AnnAndBob) {
  await chooseManager(page, 'first', ann);
  await chooseManager(page, 'second', bob);
  await expect(pickRows(managerSide(page, 'first').assets)).toHaveCount(2);
  await expect(pickRows(managerSide(page, 'second').assets)).toHaveCount(2);
}

async function chooseOneAssetOfEach(page: Page) {
  await assetToggles(managerSide(page, 'first').assets).first().click();
  await assetToggles(managerSide(page, 'second').assets).first().click();
}

test('ADDTRADE-01 the panel Enter a new Trade shows two empty menus and a disabled button', async ({
  page,
  context,
  setup,
}) => {
  const { draft } = await newInProgressDraft(context, setup);
  await openDraftScreen(page, draft);

  await commishActions(page).getByRole('link', { name: 'Enter Trade' }).click();

  await expect(page).toHaveURL(addTradeRoute(draft));
  await expect(tradeHeading(page)).toBeVisible();
  await expect(page.getByText(CHOOSE_TWO)).toBeVisible();
  await expect(managerSide(page, 'first').menu).toHaveText('Choose one manager', VISIBLE_TEXT);
  await expect(managerSide(page, 'second').menu).toHaveText('Choose another manager', VISIBLE_TEXT);
  await expect(enterTradeButton(page)).toBeDisabled();
});

test('ADDTRADE-02 the first Manager shows the Picks, and the second menu disables the same Manager', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-22');
  const {
    draft,
    managers: { ann, bob },
  } = await newInProgressDraft(context, setup);
  await openAddTrade(page, draft);
  const first = managerSide(page, 'first');
  const second = managerSide(page, 'second');

  await chooseManager(page, 'first', ann);
  await second.menu.click();

  await expect(pickRows(first.assets)).toHaveCount(2);
  await expect(assetToggles(first.assets)).toHaveCount(2);
  const annOption = second.options.filter({ hasText: ann });
  // The menu items are links, and Playwright reads no disabled state of a link, so the test checks the class
  await expect(annOption).toHaveClass(/\bdisabled\b/);
  await expect(second.options.filter({ hasText: bob })).not.toHaveClass(/\bdisabled\b/);
  await expect(annOption).toHaveText(ann, VISIBLE_TEXT);

  await annOption.click();

  await expect(second.menu).toHaveText('Choose another manager', VISIBLE_TEXT);
  await expect(pickRows(second.assets)).toHaveCount(0);
});

test('ADDTRADE-03 the second Manager shows the Picks, and the heading asks for an asset', async ({
  page,
  context,
  setup,
}) => {
  const {
    draft,
    managers: { ann, bob },
  } = await newInProgressDraft(context, setup);
  await openAddTrade(page, draft);
  await chooseManager(page, 'first', ann);

  await chooseManager(page, 'second', bob);

  await expect(pickRows(managerSide(page, 'second').assets)).toHaveCount(2);
  await expect(assetToggles(managerSide(page, 'second').assets)).toHaveCount(2);
  await expect(page.getByText(CHOOSE_ASSET)).toBeVisible();
  await expect(page.getByText(CHOOSE_TWO)).toBeHidden();
});

test('ADDTRADE-04 Enter New Trade needs a Pick of each Manager, and a second click removes a choice', async ({
  page,
  context,
  setup,
}) => {
  const { draft, managers } = await newInProgressDraft(context, setup);
  await openAddTrade(page, draft);
  await chooseAnnAndBob(page, managers);
  const annToggle = assetToggles(managerSide(page, 'first').assets).first();
  const bobToggle = assetToggles(managerSide(page, 'second').assets).first();

  await annToggle.click();

  await expect(chosenMark(annToggle)).toBeVisible();
  await expect(enterTradeButton(page)).toBeDisabled();

  await bobToggle.click();

  await expect(chosenMark(bobToggle)).toBeVisible();
  await expect(enterTradeButton(page)).toBeEnabled();

  await bobToggle.click();

  await expect(chosenMark(bobToggle)).toBeHidden();
  await expect(enterTradeButton(page)).toBeDisabled();
});

test('ADDTRADE-05 Enter New Trade saves the Trade, shows it on the Trades screen, and moves the Picks', async ({
  page,
  context,
  setup,
}) => {
  const { draft, managers } = await newInProgressDraft(context, setup);
  const { ann, bob } = managers;
  await openAddTrade(page, draft);
  await chooseAnnAndBob(page, managers);
  await chooseOneAssetOfEach(page);

  await enterTradeButton(page).click();

  await expectToast(page, { message: 'Trade entered' });
  await expect(page).toHaveURL(tradesRoute(draft));
  await expect(tradeCards(page)).toHaveCount(1);
  await expect(tradeCards(page)).toContainText(
    new RegExp(`Trade between ${ann} and ${bob}:[\\s\\S]*${ann} gets:[\\s\\S]*${bob} gets:`),
    VISIBLE_TEXT,
  );
  // Ann gets Bob's Pick 2, and Bob gets Ann's Pick 1
  await expect(tradeAssets(tradeCards(page))).toHaveText(
    ['1st Round Pick (2nd overall)', '1st Round Pick (1st overall)'],
    VISIBLE_TEXT,
  );

  // Picks 1 and 2 swap Managers, and Picks 3 and 4 keep theirs. Round 2 of a Serpentine Draft shows Pick 4 first.
  await page.goto(boardRoute(draft));
  await expect(boardPicks(page)).toHaveCount(4);
  await expect(boardPicks(page)).toContainText([bob, ann, ann, bob], VISIBLE_TEXT);
});

for (const { row, status, message } of [
  {
    row: 'ADDTRADE-06',
    status: 'Setting Up',
    message: 'Unable to add trade for draft: draft has not been started yet.',
  },
  { row: 'ADDTRADE-07', status: 'Complete', message: 'Unable to add trade for draft: draft is already completed' },
] as const) {
  test(`${row} a ${status} Draft shows a warning toast and goes to the previous route`, async ({
    page,
    context,
    setup,
  }) => {
    knownFailureOnAngularJs('W-06');

    await expectDraftStatusRedirect(page, setup, { route: addTradeRoute, status, message, loggedInContext: context });
  });
}

test('ADDTRADE-08 an API error shows a toast for the Managers, the Picks, and the save', async ({
  page,
  context,
  setup,
}) => {
  const { draft, managers } = await newInProgressDraft(context, setup);
  const { ann, bob } = managers;

  await failApiRequest(page, { method: 'GET', path: managersApiPath(draft) });
  await page.goto(addTradeRoute(draft));
  await expectToast(page, { message: 'Unable to load managers for trade' });

  const assetsPage = await context.newPage();
  await failApiRequest(assetsPage, { method: 'GET', path: assetsPath(draft) });
  await openAddTrade(assetsPage, draft);
  await chooseManager(assetsPage, 'first', ann);
  await expectToast(assetsPage, { message: 'Unable to load assets for manager' });

  const savePage = await context.newPage();
  await failApiRequest(savePage, { method: 'POST', path: savePath(draft) });
  await openAddTrade(savePage, draft);
  await chooseAnnAndBob(savePage, managers);
  await chooseOneAssetOfEach(savePage);
  await enterTradeButton(savePage).click();

  await expectToast(savePage, { title: 'Unable to enter trade', message: SERVER_ERROR_TOAST });
  await expect(savePage).toHaveURL(addTradeRoute(draft));
});

test('ADDTRADE-08 an expired session logs the viewer out with the one shared toast', async ({
  page,
  context,
  setup,
}) => {
  knownFailureOnAngularJs('W-33');
  const { commissioner, draft, managers } = await newInProgressDraft(context, setup);
  await openAddTrade(page, draft);
  await chooseAnnAndBob(page, managers);
  await chooseOneAssetOfEach(page);
  await commissioner.api.post('logout');

  await enterTradeButton(page).click();

  await expectExpiredSessionLogout(page);
});

test('ADDTRADE-09 an anonymous viewer goes to the login screen', async ({ page, setup }) => {
  knownFailureOnAngularJs('W-01');
  const commissioner = await setup.registerCommissioner();
  const draft = await setup.createDraft(commissioner, { status: 'In Progress' });

  await page.goto(addTradeRoute(draft));

  await expectLoginRoute(page);
});

test('ADDTRADE-10 another Commissioner goes to the Draft index', async ({ page, context, setup }) => {
  knownFailureOnAngularJs('W-02');
  const owner = await setup.registerCommissioner();
  const draft = await setup.createDraft(owner, { status: 'In Progress' });
  await setup.logInNewCommissioner(context);

  await page.goto(addTradeRoute(draft));

  await expect(page).toHaveURL(draftIndexRoute(draft));
});
