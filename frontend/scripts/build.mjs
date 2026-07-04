import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
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

console.log(`Built static frontend into ${dist}`);
