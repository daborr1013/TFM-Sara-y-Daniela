import { cpSync, existsSync, mkdirSync, readFileSync, readdirSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { dirname, join, normalize, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const dist = join(root, 'dist');

rmSync(dist, { recursive: true, force: true });
mkdirSync(dist, { recursive: true });

for (const entry of ['index.html', 'login.html', 'register.html', 'mapa_personajes.html', 'content', 'css', 'js', 'media']) {
  const source = join(root, entry);
  if (existsSync(source)) {
    cpSync(source, join(dist, entry), { recursive: true });
  }
}

const apiUrl = process.env.VITE_API_URL || process.env.LITTERALLY_API_URL || '/api';
const appPath = join(dist, 'js', 'app.js');
const appSource = readFileSync(appPath, 'utf8').replaceAll('__LITTERALLY_API_URL_VALUE__', apiUrl);
writeFileSync(appPath, appSource);

const brokenLinks = findBrokenInternalLinks(dist);
if (brokenLinks.length > 0) {
  const report = brokenLinks
    .slice(0, 20)
    .map((item) => `- ${item.file}: ${item.url}`)
    .join('\n');
  const suffix = brokenLinks.length > 20 ? `\n...and ${brokenLinks.length - 20} more` : '';
  throw new Error(`Broken internal links found:\n${report}${suffix}`);
}

console.log(`Built static frontend into ${dist}`);

function findBrokenInternalLinks(rootDir) {
  const htmlFiles = listHtmlFiles(rootDir);
  const brokenLinks = [];
  const attributePattern = /\b(?:href|src)=["']([^"']+)["']/gi;

  for (const filePath of htmlFiles) {
    const html = readFileSync(filePath, 'utf8');
    let match;

    while ((match = attributePattern.exec(html)) !== null) {
      const url = match[1].trim();
      if (shouldSkipUrl(url)) continue;

      const [pathOnly] = url.split(/[?#]/);
      const targetPath = pathOnly.startsWith('/')
        ? join(rootDir, pathOnly)
        : join(dirname(filePath), pathOnly);
      const normalizedTarget = normalize(targetPath);
      const targetRelativePath = relative(rootDir, normalizedTarget);

      if (targetRelativePath.startsWith('..') || targetRelativePath.startsWith('/')) continue;
      if (!existsSync(normalizedTarget)) {
        brokenLinks.push({
          file: relative(rootDir, filePath),
          url,
        });
      }
    }
  }

  return brokenLinks;
}

function listHtmlFiles(rootDir) {
  const htmlFiles = [];
  const pending = [rootDir];

  while (pending.length > 0) {
    const directory = pending.pop();

    for (const entry of readdirSync(directory)) {
      const entryPath = join(directory, entry);
      const stats = statSync(entryPath);

      if (stats.isDirectory()) {
        pending.push(entryPath);
      } else if (entryPath.endsWith('.html')) {
        htmlFiles.push(entryPath);
      }
    }
  }

  return htmlFiles;
}

function shouldSkipUrl(url) {
  return !url
    || url.startsWith('#')
    || url.startsWith('/api')
    || url.startsWith('mailto:')
    || url.startsWith('tel:')
    || url.startsWith('javascript:')
    || /^[a-z][a-z0-9+.-]*:\/\//i.test(url);
}
