// Core crawl: launch a real (stealth) headless browser, discover the docs
// pages, render each to PDF, and merge into one document with a generated
// table of contents.

import { promises as fs } from 'node:fs';
import path from 'node:path';
import { PDFDocument, StandardFonts, rgb } from 'pdf-lib';
import puppeteerExtra from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';

import { DEFAULT_UA, log, mapPool, sleep, withRetry, sanitizeFilename } from './util.js';
import {
  combine,
  deriveBasePath,
  discoverFromSidebar,
  discoverFromSitemap,
  discoverFromStoplightToc,
} from './discover.js';

// CSS that hides the chrome around the content so PDFs aren't dominated by the
// nav rails. Only applied in --content-only mode; selectors cover the common
// Stoplight Elements / hosted-docs layouts and are best-effort.
const CONTENT_ONLY_CSS = `
  [data-testid="toc"], nav[aria-label], .sl-elements-api aside,
  aside, header[role="banner"], .Toolbar, .sl-flex-1 > aside,
  div[class*="Sidebar"], div[class*="sidebar"] { display: none !important; }
  main, [role="main"], .sl-overflow-y-auto { width: 100% !important; max-width: 100% !important; }
`;

async function launchBrowser({ stealth, headful, token }) {
  if (stealth) puppeteerExtra.use(StealthPlugin());
  const browser = await puppeteerExtra.launch({
    headless: headful ? false : 'new',
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-blink-features=AutomationControlled',
    ],
  });
  return browser;
}

async function newPage(browser, { token, format, contentOnly }) {
  const page = await browser.newPage();
  await page.setUserAgent(DEFAULT_UA);
  await page.setViewport({ width: 1280, height: 1696, deviceScaleFactor: 1 });
  if (token) {
    await page.setExtraHTTPHeaders({ Authorization: `Bearer ${token}` });
  }
  await page.emulateMediaType('screen');
  return page;
}

async function renderPage(page, url, { timeout, settle, contentOnly, format }) {
  await page.goto(url, { waitUntil: 'networkidle2', timeout });
  // Stoplight is a SPA; give late XHR/render a moment and try to wait for the
  // main content region to be present.
  await page
    .waitForSelector('main, [role="main"], .sl-elements, article', { timeout: 8000 })
    .catch(() => {});
  if (settle) await sleep(settle);

  if (contentOnly) {
    await page.addStyleTag({ content: CONTENT_ONLY_CSS }).catch(() => {});
  }

  const title = (await page.title().catch(() => '')) || url;

  const pdf = await page.pdf({
    printBackground: true,
    format: format || 'A4',
    margin: { top: '12mm', bottom: '14mm', left: '10mm', right: '10mm' },
    displayHeaderFooter: false,
  });
  return { pdf: Buffer.from(pdf), title };
}

// Build the merged PDF and prepend a clickable-less text TOC page.
async function mergePdfs(rendered, { title }) {
  const out = await PDFDocument.create();
  const font = await out.embedFont(StandardFonts.Helvetica);
  const bold = await out.embedFont(StandardFonts.HelveticaBold);

  // Reserve a TOC page up front; fill it after we know page offsets.
  const tocEntries = []; // { title, page }
  for (const item of rendered) {
    if (!item || !item.pdf) continue;
    let src;
    try {
      src = await PDFDocument.load(item.pdf);
    } catch (err) {
      log.warn(`skip (corrupt PDF) ${item.url}: ${err.message}`);
      continue;
    }
    const startPage = out.getPageCount(); // before adding (0-based, pre-TOC)
    const copied = await out.copyPages(src, src.getPageIndices());
    copied.forEach((p) => out.addPage(p));
    tocEntries.push({ title: item.title || item.url, page: startPage });
  }

  if (out.getPageCount() === 0) {
    throw new Error('Nothing was rendered — no pages to merge.');
  }

  // Insert the TOC as the first page. Content pages shift by +1, so displayed
  // page numbers are (startPage + 1 /*0->1 based*/ + 1 /*TOC page*/).
  const toc = out.insertPage(0, [595.28, 841.89]); // A4 in points
  const W = toc.getWidth();
  let y = toc.getHeight() - 60;
  toc.drawText('Contents', { x: 50, y, size: 20, font: bold, color: rgb(0, 0, 0) });
  y -= 14;
  toc.drawText(title, { x: 50, y, size: 9, font, color: rgb(0.45, 0.45, 0.45) });
  y -= 26;

  const lineH = 16;
  const size = 10;
  for (const e of tocEntries) {
    if (y < 60) break; // single TOC page; deep docs just omit overflow entries
    const num = String(e.page + 2); // +1 (0->1) +1 (TOC page)
    let label = e.title.replace(/\s+/g, ' ').trim();
    const maxChars = 78;
    if (label.length > maxChars) label = label.slice(0, maxChars - 1) + '…';
    toc.drawText(label, { x: 50, y, size, font, color: rgb(0.1, 0.1, 0.1) });
    const numWidth = font.widthOfTextAtSize(num, size);
    toc.drawText(num, { x: W - 50 - numWidth, y, size, font, color: rgb(0.1, 0.1, 0.1) });
    y -= lineH;
  }

  return out.save();
}

export async function crawlToPdf(entryUrl, opts) {
  const {
    output,
    token,
    include,
    exclude,
    maxPages,
    concurrency,
    delay,
    contentOnly,
    stealth,
    headful,
    timeout,
    settle,
    keepTemp,
    tempDir,
  } = opts;

  const origin = new URL(entryUrl).origin;
  const basePath = opts.basePath || deriveBasePath(entryUrl);
  log.info(`Entry: ${entryUrl}`);
  log.info(`Origin: ${origin}  Base path: ${basePath}`);

  const browser = await launchBrowser({ stealth, headful, token });
  try {
    // --- Discovery -------------------------------------------------------
    const discoveryPage = await newPage(browser, { token });
    log.step('Loading entry page…');
    await withRetry(
      () => discoveryPage.goto(entryUrl, { waitUntil: 'networkidle2', timeout }),
      {
        tries: 3,
        baseMs: 2000,
        onRetry: (e, a, w) => log.warn(`entry load failed (try ${a}): ${e.message}; retrying in ${w}ms`),
      }
    );
    await discoveryPage
      .waitForSelector('a[href]', { timeout: 8000 })
      .catch(() => {});
    if (settle) await sleep(settle);

    log.step('Discovering pages…');
    const [sidebar, tocApi, sitemap] = [
      await discoverFromSidebar(discoveryPage, entryUrl, basePath),
      await discoverFromStoplightToc(discoveryPage, entryUrl),
      await discoverFromSitemap(discoveryPage, entryUrl),
    ];
    await discoveryPage.close();

    // Prefer sidebar order; if sidebar is thin, fall back to TOC API order;
    // always union the sitemap for completeness.
    const orderedPrimary = sidebar.length >= 2 ? sidebar : tocApi.length ? tocApi : sidebar;
    let urls = combine(
      { ordered: orderedPrimary, extra: [...tocApi, ...sitemap] },
      { basePath, origin, include, exclude }
    );

    // Always include the entry URL itself, first.
    const entryNorm = (() => {
      const u = new URL(entryUrl);
      u.hash = '';
      return u.origin + u.pathname + u.search;
    })();
    urls = urls.filter((u) => u !== entryNorm);
    urls.unshift(entryNorm);

    if (urls.length > maxPages) {
      log.warn(`Discovered ${urls.length} pages; capping at --max-pages=${maxPages}`);
      urls = urls.slice(0, maxPages);
    }
    log.ok(`${urls.length} page(s) to render`);
    if (opts.listOnly) {
      urls.forEach((u, i) => console.log(`${String(i + 1).padStart(3)}  ${u}`));
      return { urls, output: null };
    }
    urls.forEach((u, i) => log.debug(`${i + 1}. ${u}`));

    // --- Render ----------------------------------------------------------
    log.step(`Rendering ${urls.length} page(s) with concurrency ${concurrency}…`);
    let done = 0;
    const rendered = await mapPool(urls, concurrency, async (url) => {
      const page = await newPage(browser, { token });
      try {
        const r = await withRetry(
          () => renderPage(page, url, { timeout, settle, contentOnly, format: opts.format }),
          {
            tries: 2,
            baseMs: 1500,
            onRetry: (e, a, w) => log.debug(`render retry ${url}: ${e.message}`),
          }
        );
        done++;
        log.info(`[${done}/${urls.length}] ${r.title}`);
        if (delay) await sleep(delay);
        return { url, ...r };
      } catch (err) {
        done++;
        log.warn(`[${done}/${urls.length}] FAILED ${url}: ${err.message}`);
        return null;
      } finally {
        await page.close().catch(() => {});
      }
    });

    if (keepTemp && tempDir) {
      await fs.mkdir(tempDir, { recursive: true });
      for (const r of rendered) {
        if (!r) continue;
        const name = sanitizeFilename(r.title || r.url) + '.pdf';
        await fs.writeFile(path.join(tempDir, name), r.pdf);
      }
      log.info(`Per-page PDFs written to ${tempDir}`);
    }

    // --- Merge -----------------------------------------------------------
    log.step('Merging into a single PDF…');
    const merged = await mergePdfs(
      rendered.map((r) => r).filter(Boolean),
      { title: entryUrl }
    );
    await fs.mkdir(path.dirname(path.resolve(output)), { recursive: true });
    await fs.writeFile(output, merged);
    const okCount = rendered.filter(Boolean).length;
    log.ok(`Wrote ${output} (${okCount}/${urls.length} pages)`);
    return { urls, output };
  } finally {
    await browser.close().catch(() => {});
  }
}
