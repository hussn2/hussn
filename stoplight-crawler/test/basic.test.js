import test from 'node:test';
import assert from 'node:assert/strict';

import { deriveBasePath, combine } from '../src/discover.js';
import { sanitizeFilename, mapPool } from '../src/util.js';

test('deriveBasePath uses the first path segment', () => {
  assert.equal(deriveBasePath('https://docs.x.com/docs/platform/abc'), '/docs');
  assert.equal(deriveBasePath('https://docs.x.com/'), '/');
  assert.equal(deriveBasePath('https://docs.x.com/api/v1-intro'), '/api');
});

test('combine filters by origin and base path, de-dupes, keeps order', () => {
  const origin = 'https://docs.x.com';
  const ordered = [
    'https://docs.x.com/docs/a',
    'https://docs.x.com/docs/b#frag',
    'https://docs.x.com/docs/a', // dup
    'https://evil.com/docs/c', // wrong origin
    'https://docs.x.com/marketing/d', // wrong base path
  ];
  const out = combine(
    { ordered, extra: ['https://docs.x.com/docs/e'] },
    { basePath: '/docs', origin, include: null, exclude: null }
  );
  assert.deepEqual(out, [
    'https://docs.x.com/docs/a',
    'https://docs.x.com/docs/b',
    'https://docs.x.com/docs/e',
  ]);
});

test('combine honours include / exclude regexes', () => {
  const origin = 'https://docs.x.com';
  const ordered = [
    'https://docs.x.com/docs/keep-1',
    'https://docs.x.com/docs/drop-2',
    'https://docs.x.com/docs/keep-3',
  ];
  const out = combine(
    { ordered, extra: [] },
    { basePath: '/docs', origin, include: /keep/, exclude: /3$/ }
  );
  assert.deepEqual(out, ['https://docs.x.com/docs/keep-1']);
});

test('sanitizeFilename strips unsafe chars', () => {
  assert.equal(sanitizeFilename('Hello / World: API?'), 'Hello-World-API');
  assert.equal(sanitizeFilename(''), 'page');
  assert.equal(sanitizeFilename('   '), 'page');
});

test('mapPool preserves order with bounded concurrency', async () => {
  const items = [1, 2, 3, 4, 5];
  const out = await mapPool(items, 2, async (n) => {
    await new Promise((r) => setTimeout(r, (6 - n) * 5));
    return n * 10;
  });
  assert.deepEqual(out, [10, 20, 30, 40, 50]);
});
