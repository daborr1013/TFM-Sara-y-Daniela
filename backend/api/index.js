import bcrypt from 'bcryptjs';
import { parse as parseCookie, serialize as serializeCookie } from 'cookie';
import jwt from 'jsonwebtoken';
import pg from 'pg';

const { Pool } = pg;

const COOKIE_NAME = 'litterally_session';
const FALLBACK_ACTIVITIES = {
  1: { type: 'Tests', level: 'General', description: 'Prueba general de comprension' },
  2: { type: 'Rellenar', level: 'General', description: 'Actividad para completar espacios' },
  3: { type: 'Desarrollar', level: 'General', description: 'Actividad de respuesta abierta' },
  4: { type: 'Flashcards', level: 'General', description: 'Repaso con tarjetas' },
  5: { type: 'Juegos', level: 'General', description: 'Actividad ludica sobre la obra' },
  6: { type: 'Juegos', level: 'General', description: 'Juego de memoria de personajes' },
  10: { type: 'Tests', level: 'Capitulos 1-4', description: 'Test sobre los capitulos 1 a 4' },
  11: { type: 'Tests', level: 'Capitulos 5-10', description: 'Test sobre los capitulos 5 a 10' },
  12: { type: 'Tests', level: 'Capitulos 11+', description: 'Test sobre los capitulos posteriores al 10' },
};

let pool;
const tableCache = new Map();

export default async function handler(request, response) {
  applyCors(request, response);

  if (request.method === 'OPTIONS') {
    response.writeHead(204).end();
    return;
  }

  try {
    const url = new URL(request.url, `http://${request.headers.host || 'localhost'}`);
    const pathname = url.pathname.replace(/\/$/, '') || '/';

    if (request.method === 'GET' && pathname === '/api/health') {
      return sendJson(response, { ok: true });
    }

    if (request.method === 'POST' && pathname === '/api/auth/register') {
      return register(request, response);
    }

    if (request.method === 'POST' && pathname === '/api/auth/login') {
      return login(request, response);
    }

    if (request.method === 'POST' && pathname === '/api/auth/logout') {
      return logout(response);
    }

    if (request.method === 'GET' && pathname === '/api/me') {
      const user = await requireUser(request);
      return sendJson(response, { user });
    }

    if (request.method === 'GET' && pathname === '/api/progress') {
      const user = await requireUser(request);
      return getProgress(response, user.id);
    }

    if (request.method === 'POST' && pathname === '/api/progress') {
      const user = await requireUser(request);
      return saveProgress(request, response, user.id);
    }

    if (request.method === 'POST' && pathname === '/api/chatbot') {
      await requireUser(request);
      return chatbot(request, response);
    }

    return sendJson(response, { error: 'Ruta no encontrada' }, 404);
  } catch (error) {
    const status = Number(error.statusCode || 500);
    return sendJson(response, { error: status >= 500 ? 'Error interno del servidor' : error.message }, status);
  }
}

function applyCors(request, response) {
  const origin = request.headers.origin;
  const allowedOrigins = (process.env.FRONTEND_ORIGIN || '')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);

  if (!origin) return;

  if (originAllowed(origin, allowedOrigins)) {
    response.setHeader('Access-Control-Allow-Origin', origin);
    response.setHeader('Vary', 'Origin');
    response.setHeader('Access-Control-Allow-Credentials', 'true');
    response.setHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');
    response.setHeader('Access-Control-Allow-Headers', 'Content-Type,Authorization');
  }
}

function originAllowed(origin, allowedOrigins) {
  if (allowedOrigins.length === 0) return true;

  return allowedOrigins.some((allowedOrigin) => {
    if (allowedOrigin === origin) return true;
    if (!allowedOrigin.includes('*')) return false;

    const escaped = allowedOrigin.replace(/[|\\{}()[\]^$+?.]/g, '\\$&').replace(/\*/g, '.*');
    return new RegExp(`^${escaped}$`).test(origin);
  });
}

function sendJson(response, payload, status = 200) {
  response.statusCode = status;
  response.setHeader('Content-Type', 'application/json; charset=utf-8');
  response.end(JSON.stringify(payload));
}

function httpError(message, statusCode) {
  const error = new Error(message);
  error.statusCode = statusCode;
  return error;
}

async function readJson(request) {
  if (request.body && typeof request.body === 'object') {
    return request.body;
  }

  let raw = '';
  for await (const chunk of request) {
    raw += chunk;
  }

  if (!raw.trim()) return {};

  try {
    return JSON.parse(raw);
  } catch {
    throw httpError('JSON invalido', 400);
  }
}

function getPool() {
  if (pool) return pool;

  const connectionString = process.env.DATABASE_URL || process.env.POSTGRES_URL;

  if (!connectionString) {
    throw httpError('DATABASE_URL o POSTGRES_URL no configurado', 500);
  }

  const ssl = process.env.DATABASE_SSL === 'false'
    ? false
    : { rejectUnauthorized: false };

  pool = new Pool({
    connectionString,
    max: 5,
    ssl,
    idleTimeoutMillis: 10_000,
  });

  return pool;
}

async function query(sql, params = []) {
  const { rows } = await getPool().query(sql, params);
  return rows;
}

async function tableExists(tableName) {
  if (tableCache.has(tableName)) return tableCache.get(tableName);
  const rows = await query(
    'SELECT 1 FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = $1 LIMIT 1',
    [tableName],
  );
  const exists = rows.length > 0;
  tableCache.set(tableName, exists);
  return exists;
}

async function register(request, response) {
  const body = await readJson(request);
  const name = String(body.name || '').trim();
  const email = String(body.email || '').trim().toLowerCase();
  const password = String(body.password || '');
  const passwordConfirmation = String(body.passwordConfirmation || '');

  if (!name || !email || !password || !passwordConfirmation) {
    throw httpError('Completa todos los campos.', 400);
  }

  if (name.length < 3) {
    throw httpError('El nombre debe tener al menos 3 caracteres.', 400);
  }

  if (password.length < 8) {
    throw httpError('La contrasena debe tener al menos 8 caracteres.', 400);
  }

  if (password !== passwordConfirmation) {
    throw httpError('Las contrasenas no coinciden.', 400);
  }

  const existing = await query('SELECT id FROM users WHERE email = $1 LIMIT 1', [email]);
  if (existing.length > 0) {
    throw httpError('Este correo ya esta registrado.', 409);
  }

  const hash = await bcrypt.hash(password, 12);
  await query('INSERT INTO users (nombre, email, "contraseña") VALUES ($1, $2, $3)', [name, email, hash]);

  sendJson(response, { ok: true }, 201);
}

async function login(request, response) {
  const body = await readJson(request);
  const email = String(body.email || '').trim().toLowerCase();
  const password = String(body.password || '');

  if (!email || !password) {
    throw httpError('Completa todos los campos.', 400);
  }

  const rows = await query(
    'SELECT id, nombre, email, "contraseña", fecha_registro FROM users WHERE email = $1 LIMIT 1',
    [email],
  );

  if (rows.length === 0) {
    throw httpError('El correo no esta registrado.', 401);
  }

  const userRow = rows[0];
  const storedPassword = String(userRow['contraseña'] || '');
  const bcryptHash = storedPassword.startsWith('$2a$') || storedPassword.startsWith('$2b$') || storedPassword.startsWith('$2y$');
  const validPassword = bcryptHash
    ? await bcrypt.compare(password, storedPassword)
    : password === storedPassword;

  if (!validPassword) {
    throw httpError('Contrasena incorrecta.', 401);
  }

  if (!bcryptHash) {
    const hash = await bcrypt.hash(password, 12);
    await query('UPDATE users SET "contraseña" = $1 WHERE id = $2', [hash, userRow.id]);
  }

  const user = normalizeUser(userRow);
  const token = jwt.sign({ sub: user.id }, jwtSecret(), { expiresIn: '7d' });
  response.setHeader('Set-Cookie', sessionCookie(token));

  sendJson(response, { user });
}

async function logout(response) {
  response.setHeader('Set-Cookie', serializeCookie(COOKIE_NAME, '', {
    httpOnly: true,
    maxAge: 0,
    path: '/',
    sameSite: cookieSameSite(),
    secure: cookieSecure(),
  }));
  sendJson(response, { ok: true });
}

async function requireUser(request) {
  const cookies = parseCookie(request.headers.cookie || '');
  const bearer = request.headers.authorization?.startsWith('Bearer ')
    ? request.headers.authorization.slice('Bearer '.length)
    : '';
  const token = cookies[COOKIE_NAME] || bearer;

  if (!token) {
    throw httpError('No autorizado', 401);
  }

  let decoded;
  try {
    decoded = jwt.verify(token, jwtSecret());
  } catch {
    throw httpError('Sesion caducada', 401);
  }

  const rows = await query('SELECT id, nombre, email, fecha_registro FROM users WHERE id = $1 LIMIT 1', [decoded.sub]);
  if (rows.length === 0) {
    throw httpError('Usuario no encontrado', 401);
  }

  return normalizeUser(rows[0]);
}

function normalizeUser(row) {
  const registeredAt = row.fecha_registro ? new Date(row.fecha_registro) : null;
  return {
    id: row.id,
    name: row.nombre,
    email: row.email,
    registeredAt: registeredAt ? registeredAt.toISOString() : null,
    registeredYear: registeredAt ? String(registeredAt.getFullYear()) : '',
  };
}

function jwtSecret() {
  return process.env.JWT_SECRET || 'dev-only-change-me';
}

function sessionCookie(token) {
  return serializeCookie(COOKIE_NAME, token, {
    httpOnly: true,
    maxAge: 60 * 60 * 24 * 7,
    path: '/',
    sameSite: cookieSameSite(),
    secure: cookieSecure(),
  });
}

function cookieSameSite() {
  return process.env.NODE_ENV === 'production' ? 'none' : 'lax';
}

function cookieSecure() {
  return process.env.NODE_ENV === 'production';
}

async function getProgress(response, userId) {
  const progress = await progressRows(userId);
  const stats = buildStats(progress);
  const categories = buildCategories(progress);
  sendJson(response, { progress, stats, categories });
}

async function saveProgress(request, response, userId) {
  const body = await readJson(request);
  const activityId = Number(body.activityId);
  const score = Math.max(0, Math.min(100, Number(body.score || 0)));
  const completed = body.completed === false ? 0 : 1;

  if (!Number.isInteger(activityId) || activityId <= 0) {
    throw httpError('activityId requerido', 400);
  }

  const existing = await query('SELECT id FROM user_progress WHERE user_id = $1 AND activity_id = $2 LIMIT 1', [userId, activityId]);

  if (existing.length > 0) {
    await query(
      'UPDATE user_progress SET puntuacion = $1, completado = $2, fecha = NOW() WHERE id = $3',
      [score, completed, existing[0].id],
    );
  } else {
    await query(
      'INSERT INTO user_progress (user_id, activity_id, puntuacion, completado) VALUES ($1, $2, $3, $4)',
      [userId, activityId, score, completed],
    );
  }

  sendJson(response, { ok: true });
}

async function progressRows(userId) {
  const hasActivities = await tableExists('activities');
  const hasWorks = hasActivities && await tableExists('works');

  let sql = 'SELECT up.id, up.activity_id AS "activityId", up.puntuacion AS score, up.completado AS completed, up.fecha AS date';

  if (hasActivities) {
    sql += ', a.tipo AS type, a.nivel AS level, a.descripcion AS description';
  }

  if (hasWorks) {
    sql += ', w.titulo AS "workName"';
  }

  sql += ' FROM user_progress up';

  if (hasActivities) {
    sql += ' LEFT JOIN activities a ON up.activity_id = a.id';
  }

  if (hasWorks) {
    sql += ' LEFT JOIN works w ON a.work_id = w.id';
  }

  sql += ' WHERE up.user_id = $1 ORDER BY up.fecha DESC';

  const rows = await query(sql, [userId]);

  return rows.map((row) => {
    const fallback = FALLBACK_ACTIVITIES[row.activityId] || { type: 'Actividad', level: 'General', description: 'Actividad de Jane Eyre' };
    return {
      id: row.id,
      activityId: row.activityId,
      score: Number(row.score || 0),
      completed: Boolean(row.completed),
      date: row.date,
      type: row.type || fallback.type,
      level: row.level || fallback.level,
      description: row.description || fallback.description,
      workName: row.workName || 'Jane Eyre',
    };
  });
}

function buildStats(progress) {
  const scores = progress.map((item) => Number(item.score || 0));
  const completed = progress.filter((item) => item.completed).length;
  const total = progress.length;
  const sum = scores.reduce((left, right) => left + right, 0);

  return {
    totalActivities: total,
    completed,
    averageScore: total > 0 ? sum / total : 0,
    bestScore: scores.length > 0 ? Math.max(...scores) : 0,
    worstScore: scores.length > 0 ? Math.min(...scores) : 0,
  };
}

function buildCategories(progress) {
  const grouped = new Map();

  for (const item of progress) {
    const current = grouped.get(item.type) || { type: item.type, total: 0, completed: 0, scoreSum: 0 };
    current.total += 1;
    current.completed += item.completed ? 1 : 0;
    current.scoreSum += item.score || 0;
    grouped.set(item.type, current);
  }

  return [...grouped.values()]
    .map((item) => ({ ...item, average: item.total > 0 ? item.scoreSum / item.total : 0, scoreSum: undefined }))
    .sort((left, right) => right.total - left.total);
}

async function chatbot(request, response) {
  const body = await readJson(request);
  const message = String(body.message || '').trim();

  if (!message) {
    throw httpError('Escribe una pregunta.', 400);
  }

  const normalized = normalizeText(message);

  if (/(hola|buenas|ayuda)/.test(normalized)) {
    return sendJson(response, {
      response: 'Hola. Puedo ayudarte con personajes, capitulos, temas, simbolos, glosario y contexto de Jane Eyre.',
    });
  }

  const result = await searchKnowledge(message, normalized);
  sendJson(response, {
    response: result || 'No he encontrado una respuesta concreta. Prueba preguntando por un personaje, un simbolo, un tema, un capitulo o el contexto historico.',
  });
}

async function searchKnowledge(message, normalized) {
  const terms = normalizeText(message)
    .split(' ')
    .filter((term) => term.length >= 4)
    .slice(0, 4);

  if (terms.length === 0) {
    return null;
  }

  if (/(personaje|jane|rochester|bertha|helen|bessie|reed|rivers|adele)/.test(normalized)) {
    const character = await firstLike('characters', ['nombre', 'descripcion', 'rol', 'relaciones'], terms);
    if (character) {
      return `${character.nombre}: ${cleanText(character.descripcion, 500)} Rol: ${cleanText(character.rol, 200)} Relaciones: ${cleanText(character.relaciones, 250)}`;
    }
  }

  if (/(glosario|termino|concepto|palabra)/.test(normalized)) {
    const glossary = await firstLike('glossary', ['concept', 'definition'], terms);
    if (glossary) {
      return `${glossary.concept}: ${cleanText(glossary.definition, 650)}`;
    }
  }

  if (/(tema|simbolo|fuego|hielo|naturaleza|roja|habitacion)/.test(normalized)) {
    const symbol = await firstLike('symbols', ['contenido'], terms);
    if (symbol) {
      return cleanText(symbol.contenido, 700);
    }

    const theme = await firstLike('themes', ['contenido'], terms);
    if (theme) {
      return cleanText(theme.contenido, 700);
    }
  }

  if (/(capitulo|resumen|obra|historia)/.test(normalized)) {
    const summary = await firstLike('summaries', ['chapter', 'tipo', 'contenido'], terms);
    if (summary) {
      return `${summary.chapter || 'Resumen'}: ${cleanText(summary.contenido, 750)}`;
    }
  }

  if (/(contexto|historico|bronte|charlotte|victoriana|inglaterra)/.test(normalized)) {
    const historical = await firstLike('work_historical_context', ['section', 'content'], terms);
    if (historical) {
      return `${historical.section || 'Contexto historico'}: ${cleanText(historical.content, 750)}`;
    }

    const context = await firstLike('work_context', ['content'], terms);
    if (context) {
      return cleanText(context.content, 750);
    }
  }

  const genericSearches = [
    ['characters', ['nombre', 'descripcion', 'rol', 'relaciones']],
    ['glossary', ['concept', 'definition']],
    ['summaries', ['chapter', 'contenido']],
    ['work_historical_context', ['section', 'content']],
    ['work_context', ['content']],
    ['symbols', ['contenido']],
    ['themes', ['contenido']],
  ];

  for (const [table, columns] of genericSearches) {
    const row = await firstLike(table, columns, terms);
    if (row) {
      return cleanText(Object.values(row).filter(Boolean).join(': '), 750);
    }
  }

  return null;
}

async function firstLike(table, columns, terms) {
  if (!await tableExists(table)) return null;

  const conditions = [];
  const params = [];

  for (const term of terms) {
    for (const column of columns) {
      conditions.push(`LOWER(${column}::text) LIKE $${params.length + 1}`);
      params.push(`%${term}%`);
    }
  }

  const rows = await query(
    `SELECT * FROM ${table} WHERE ${conditions.join(' OR ')} LIMIT 1`,
    params,
  );

  return rows[0] || null;
}

function normalizeText(value) {
  return String(value)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function cleanText(value, limit) {
  const text = String(value || '')
    .replace(/<[^>]*>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

  if (text.length <= limit) return text;
  return `${text.slice(0, limit).replace(/\s+\S*$/, '')}...`;
}
