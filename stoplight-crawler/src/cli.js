#!/usr/bin/env node
// stoplight-pdf — crawl a Stoplight.io hosted docs site into a single PDF.

import { parseArgs } from 'node:util';
import path from 'node:path';
import { crawlToPdf } from './crawl.js';
import { log, setVerbose } from './util.js';

const HELP = `
stoplight-pdf — crawl a Stoplight.io docs site into one PDF

USAGE
  stoplight-pdf <docs-url> [options]

  <docs-url>  Any page of the published Stoplight docs you want to capture,
              e.g. https://docs.example.com/docs/platform/0e6a3688-quickstart
              The whole project's table of contents is discovered from it.

OPTIONS
  -o, --output <file>     Output PDF path            (default: <host>.pdf)
      --token <token>     Bearer token for private/SSO-gated docs
      --base-path <path>  Restrict crawl to URLs under this path
                          (default: first segment of <docs-url>, e.g. /docs)
      --include <regex>   Only crawl URLs matching this regex
      --exclude <regex>   Skip URLs matching this regex
      --max-pages <n>     Safety cap on pages         (default: 1000)
      --concurrency <n>   Parallel page renders       (default: 3)
      --delay <ms>        Politeness delay per page   (default: 250)
      --format <size>     A4 | Letter | Legal | A3    (default: A4)
      --content-only      Hide nav/sidebars; print just the article body
      --no-stealth        Disable the anti-bot stealth plugin
      --headful           Run a visible browser (debugging)
      --timeout <ms>      Per-navigation timeout      (default: 60000)
      --settle <ms>       Extra wait after load        (default: 600)
      --keep-temp         Also keep one PDF per page (in <output>.pages/)
      --list-only         Just print the discovered URL list, don't render
  -v, --verbose           Verbose logging
  -h, --help              Show this help

EXAMPLES
  stoplight-pdf https://docs.example.com/docs/api -o api-docs.pdf
  stoplight-pdf https://docs.example.com/docs/api --content-only -v
  stoplight-pdf https://docs.example.com/docs/api --token "$SL_TOKEN"
  stoplight-pdf https://docs.example.com/docs/api --list-only
`;

function toRegex(v, flag) {
  if (!v) return null;
  try {
    return new RegExp(v);
  } catch (e) {
    log.error(`Invalid regex for ${flag}: ${e.message}`);
    process.exit(2);
  }
}

async function main() {
  let parsed;
  try {
    parsed = parseArgs({
      allowPositionals: true,
      options: {
        output: { type: 'string', short: 'o' },
        token: { type: 'string' },
        'base-path': { type: 'string' },
        include: { type: 'string' },
        exclude: { type: 'string' },
        'max-pages': { type: 'string' },
        concurrency: { type: 'string' },
        delay: { type: 'string' },
        format: { type: 'string' },
        'content-only': { type: 'boolean' },
        stealth: { type: 'boolean', default: true },
        headful: { type: 'boolean' },
        timeout: { type: 'string' },
        settle: { type: 'string' },
        'keep-temp': { type: 'boolean' },
        'list-only': { type: 'boolean' },
        verbose: { type: 'boolean', short: 'v' },
        help: { type: 'boolean', short: 'h' },
      },
    });
  } catch (e) {
    log.error(e.message);
    console.error(HELP);
    process.exit(2);
  }

  const { values, positionals } = parsed;
  if (values.help || positionals.length === 0) {
    console.log(HELP);
    process.exit(values.help ? 0 : 1);
  }
  setVerbose(values.verbose);

  const entryUrl = positionals[0];
  let parsedUrl;
  try {
    parsedUrl = new URL(entryUrl);
    if (!/^https?:$/.test(parsedUrl.protocol)) throw new Error('must be http(s)');
  } catch (e) {
    log.error(`Invalid <docs-url>: ${entryUrl} (${e.message})`);
    process.exit(2);
  }

  const intOr = (v, d) => (v == null ? d : Number.parseInt(v, 10));
  const output =
    values.output || `${parsedUrl.hostname.replace(/[^a-z0-9.-]/gi, '_')}.pdf`;

  const opts = {
    output,
    token: values.token || process.env.STOPLIGHT_TOKEN || null,
    basePath: values['base-path'] || null,
    include: toRegex(values.include, '--include'),
    exclude: toRegex(values.exclude, '--exclude'),
    maxPages: intOr(values['max-pages'], 1000),
    concurrency: Math.max(1, intOr(values.concurrency, 3)),
    delay: intOr(values.delay, 250),
    format: values.format || 'A4',
    contentOnly: Boolean(values['content-only']),
    stealth: values.stealth !== false,
    headful: Boolean(values.headful),
    timeout: intOr(values.timeout, 60000),
    settle: intOr(values.settle, 600),
    keepTemp: Boolean(values['keep-temp']),
    tempDir: `${output}.pages`,
    listOnly: Boolean(values['list-only']),
  };

  try {
    await crawlToPdf(entryUrl, opts);
  } catch (e) {
    log.error(e.stack || e.message);
    process.exit(1);
  }
}

main();
