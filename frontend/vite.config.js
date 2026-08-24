import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { existsSync, mkdirSync, renameSync, rmdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath, URL } from 'node:url';

// Rollup mirrors the HTML entry's path relative to `root` in its build
// output (dist/{mode}/entries/{mode}/index.html), but a deployable build
// wants a flat index.html at the top of dist/{mode}/. Vite writes the HTML
// file itself (separately from the JS/CSS bundle, which already lands flat
// via the default assetFileNames/chunkFileNames), so move it on disk once
// the build has fully finished writing (closeBundle), then remove the now-
// empty entries/{mode}/ and entries/ directories it leaves behind.
function flattenHtmlOutput(mode, outDir) {
  const nestedHtml = join(outDir, 'entries', mode, 'index.html');
  const flatHtml = join(outDir, 'index.html');
  return {
    name: 'flatten-html-output',
    closeBundle() {
      if (!existsSync(nestedHtml)) {
        return;
      }
      mkdirSync(dirname(flatHtml), { recursive: true });
      renameSync(nestedHtml, flatHtml);
      rmdirSync(join(outDir, 'entries', mode));
      rmdirSync(join(outDir, 'entries'));
    },
  };
}

// Each app is deployed as its own SPA at its own subdomain root in
// production (see docs/specifications/010_SystemArchitecture.md), so its
// internal vue-router paths (e.g. `/login`, `/dashboard`) are top-level with
// no per-app prefix. Locally there's no root index.html for Vite's default
// dev-server SPA fallback to find (there are only nested entries/{mode}/
// entries), so route any GET navigation request that isn't a real file or a
// Vite-internal path to this mode's entry HTML instead - the standard
// history-API-fallback pattern for a single-page app.
function spaFallback(mode) {
  const entryPath = `/entries/${mode}/index.html`;
  return {
    name: 'spa-fallback',
    configureServer(server) {
      server.middlewares.use((req, res, next) => {
        if (req.method === 'GET' && !req.url.includes('.') && !req.url.startsWith('/@') && !req.url.startsWith('/node_modules')) {
          req.url = entryPath;
        }
        next();
      });
    },
  };
}

// Each app under src/apps/{mode}/ is its own Vite entry, built and deployed
// independently (see docs/specifications/010_SystemArchitecture.md) - run
// with an explicit --mode selecting which app's entries/{mode}/index.html
// to serve/build, e.g. `npm run dev -- --mode operator` or
// `npm run build -- --mode admin`. See package.json for per-app scripts.
//
// `root` stays at the project root (not entries/{mode}/) so that each entry
// HTML's absolute `/src/...` script reference - and dev-server requests for
// it - resolve the same way Vite resolves any other project file, since
// src/ sits alongside entries/, not inside it.
export default defineConfig(({ mode }) => {
  const entryHtml = fileURLToPath(new URL(`./entries/${mode}/index.html`, import.meta.url));

  if (!existsSync(entryHtml)) {
    throw new Error(
      `No entries/${mode}/index.html found. Specify --mode with an existing app ` +
        '(e.g. `npm run dev -- --mode operator` or `npm run build -- --mode admin`).',
    );
  }

  const outDir = fileURLToPath(new URL(`./dist/${mode}`, import.meta.url));

  return {
    plugins: [vue(), spaFallback(mode), flattenHtmlOutput(mode, outDir)],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      port: 5174,
      open: true,
    },
    build: {
      rollupOptions: {
        input: entryHtml,
      },
      outDir,
      emptyOutDir: true,
    },
  };
});
