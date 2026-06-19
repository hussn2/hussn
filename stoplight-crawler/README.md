# stoplight-pdf

Crawl a [Stoplight.io](https://stoplight.io) **hosted documentation site** and
export the whole project to a single PDF — table of contents included.

## Why this exists

Stoplight docs are a JavaScript single-page app sitting behind an edge/CDN that
**403s plain HTTP clients** (`curl`, `wget`, most scrapers, headless requests
without a real browser fingerprint). "Normal crawling is blocked."

This tool gets around that the same way a person's browser does:

1. **Real headless Chrome** (via `puppeteer` + a stealth plugin) — it presents a
   genuine browser TLS fingerprint, User-Agent and JS runtime, so the edge
   serves it like any visitor instead of returning `403`.
2. **Stoplight's own discovery APIs** — once a page is loaded in that browser,
   we read the rendered sidebar (which is the published table of contents in
   reading order), and additionally query Stoplight's
   `/api/v1/projects/{id}/table-of-contents` endpoint and `sitemap.xml` from
   *inside* the browser context (so those requests inherit the same trusted
   session). The three sources are unioned and de-duplicated.
3. Every discovered page is rendered and printed to PDF, then merged into one
   document with a generated contents page.

## Install

```bash
cd stoplight-crawler
npm install        # downloads a matching Chromium for Puppeteer
```

Requires Node.js ≥ 18.

## Usage

```bash
# Point it at ANY page of the docs; the rest of the project is discovered.
node src/cli.js https://docs.example.com/docs/api -o api-docs.pdf

# See what would be crawled, without rendering:
node src/cli.js https://docs.example.com/docs/api --list-only -v

# Just the article bodies, nav rails hidden:
node src/cli.js https://docs.example.com/docs/api --content-only

# Private / SSO-gated docs (also reads $STOPLIGHT_TOKEN):
node src/cli.js https://docs.example.com/docs/api --token "$SL_TOKEN"
```

After `npm install` you can also use the bin name: `npx stoplight-pdf <url>`.

## Options

| Option | Default | Description |
| --- | --- | --- |
| `-o, --output <file>` | `<host>.pdf` | Output PDF path |
| `--token <token>` | `$STOPLIGHT_TOKEN` | Bearer token for private docs |
| `--base-path <path>` | first URL segment (e.g. `/docs`) | Restrict crawl scope |
| `--include <regex>` | – | Only crawl matching URLs |
| `--exclude <regex>` | – | Skip matching URLs |
| `--max-pages <n>` | `1000` | Safety cap |
| `--concurrency <n>` | `3` | Parallel page renders |
| `--delay <ms>` | `250` | Politeness delay per page |
| `--format <size>` | `A4` | `A4` / `Letter` / `Legal` / `A3` |
| `--content-only` | off | Hide nav/sidebars, print article body only |
| `--no-stealth` | on | Disable the anti-bot stealth plugin |
| `--headful` | off | Show the browser (debugging) |
| `--timeout <ms>` | `60000` | Per-navigation timeout |
| `--settle <ms>` | `600` | Extra wait after load for late JS |
| `--keep-temp` | off | Also keep one PDF per page in `<output>.pages/` |
| `--list-only` | off | Print discovered URLs and exit |
| `-v, --verbose` | off | Verbose logging |

## How discovery decides page order

The rendered left-hand navigation is the source of truth for order (Stoplight
ships the full sidebar on every page, so a single load yields the entire,
correctly-ordered TOC). The TOC API and `sitemap.xml` are folded in afterwards
purely to catch anything the sidebar omitted. Use `--include` / `--exclude` to
fine-tune, and `--list-only` to preview the plan before a long render.

## Notes & limits

- The merged PDF's contents page is plain text (page titles + numbers), not
  clickable bookmarks.
- `--content-only` selectors are best-effort across Stoplight layout versions;
  if it hides too much, drop the flag and print full pages.
- Respect the target site's terms of service and only export docs you're
  permitted to.
