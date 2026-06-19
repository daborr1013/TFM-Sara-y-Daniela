import { createServer } from 'node:http';
import handler from './api/index.js';

const port = Number(process.env.PORT || 3001);

createServer((request, response) => {
  handler(request, response);
}).listen(port, () => {
  console.log(`Litterally API listening on http://localhost:${port}`);
});
