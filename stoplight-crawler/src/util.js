// Small shared helpers: logging, sleeping, filename sanitising.

let VERBOSE = false;

export function setVerbose(v) {
  VERBOSE = Boolean(v);
}

export const log = {
  info: (...a) => console.error('•', ...a),
  step: (...a) => console.error('→', ...a),
  warn: (...a) => console.error('!', ...a),
  error: (...a) => console.error('✗', ...a),
  ok: (...a) => console.error('✓', ...a),
  debug: (...a) => {
    if (VERBOSE) console.error('  ·', ...a);
  },
};

export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Realistic desktop Chrome UA. Stoplight's edge/CDN sometimes 403s obviously
// non-browser clients, so we present as a normal browser everywhere.
export const DEFAULT_UA =
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ' +
  '(KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

export function sanitizeFilename(name, fallback = 'page') {
  const cleaned = String(name || '')
    .trim()
    .replace(/[\s/\\?%*:|"<>]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 120);
  return cleaned || fallback;
}

// Run an async worker over items with bounded concurrency, preserving the
// input order of results.
export async function mapPool(items, concurrency, worker) {
  const results = new Array(items.length);
  let next = 0;
  const runners = Array.from({ length: Math.max(1, concurrency) }, async () => {
    while (true) {
      const i = next++;
      if (i >= items.length) return;
      results[i] = await worker(items[i], i);
    }
  });
  await Promise.all(runners);
  return results;
}

export async function withRetry(fn, { tries = 3, baseMs = 1000, onRetry } = {}) {
  let lastErr;
  for (let attempt = 1; attempt <= tries; attempt++) {
    try {
      return await fn(attempt);
    } catch (err) {
      lastErr = err;
      if (attempt < tries) {
        const wait = baseMs * 2 ** (attempt - 1);
        if (onRetry) onRetry(err, attempt, wait);
        await sleep(wait);
      }
    }
  }
  throw lastErr;
}
