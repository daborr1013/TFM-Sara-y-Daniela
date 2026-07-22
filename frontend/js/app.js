const configuredApiBase = (window.LITTERALLY_API_URL || window.__LITTERALLY_API_URL__ || '__LITTERALLY_API_URL_VALUE__').trim();
const apiBaseCandidates = buildApiBaseCandidates(configuredApiBase);
let activeApiBase = apiBaseCandidates[0];
const authTokenKey = 'litterally_auth_token';

if (!configuredApiBase || configuredApiBase.includes('__LITTERALLY_')) {
  console.warn('VITE_API_URL no configurada; el frontend intentará /api y fallbacks conocidos.');
}

function normalizeApiBase(value) {
  if (!value || value.includes('__LITTERALLY_')) {
    return '/api';
  }

  return value.replace(/\/$/, '');
}

function buildApiBaseCandidates(value) {
  const candidates = [normalizeApiBase(value)];

  if (shouldUseKnownBackendFallback()) {
    candidates.push('https://back-tfm-sara-daniela.vercel.app');
  }

  return [...new Set(candidates.filter(Boolean))];
}

function shouldUseKnownBackendFallback() {
  return window.location.hostname.endsWith('.vercel.app');
}

function buildApiUrl(path, base = activeApiBase) {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;
  if (!base) {
    return normalizedPath;
  }

  const normalizedBase = base.replace(/\/$/, '');
  const apiPrefix = '/api';

  if (normalizedPath.startsWith(apiPrefix) && normalizedBase.endsWith(apiPrefix)) {
    return `${normalizedBase}${normalizedPath.slice(apiPrefix.length)}`;
  }

  return `${normalizedBase}${normalizedPath}`;
}

const appCss = document.createElement('link');
appCss.rel = 'stylesheet';
appCss.href = '/css/app.css';
document.head.append(appCss);

async function apiRequest(path, options = {}) {
  const token = getAuthToken();
  const headers = {
    ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...(options.headers || {}),
  };

  const basesToTry = [
    activeApiBase,
    ...apiBaseCandidates.filter((base) => base !== activeApiBase),
  ];
  const attemptedUrls = [];
  let lastError;

  for (let index = 0; index < basesToTry.length; index += 1) {
    const base = basesToTry[index];
    const attemptedUrl = buildApiUrl(path, base);
    attemptedUrls.push(attemptedUrl);

    let response;
    try {
      response = await fetch(attemptedUrl, {
        credentials: 'include',
        ...options,
        headers,
      });
    } catch (error) {
      console.error('Error de conexión a la API:', attemptedUrl, error);
      lastError = error;
      continue;
    }

    const payload = await response.json().catch(() => null);
    if (shouldTryNextApiBase(response, payload, basesToTry, index)) {
      lastError = new Error(apiHttpErrorMessage(response.status));
      lastError.status = response.status;
      continue;
    }

    if (!response.ok) {
      const message = payload?.error || payload?.message || apiHttpErrorMessage(response.status);
      const error = new Error(message);
      error.status = response.status;
      throw error;
    }

    activeApiBase = base;
    return payload;
  }

  const error = new Error(`No se ha podido conectar con la API. URLs probadas: ${attemptedUrls.join(', ')}. Revisa VITE_API_URL en el frontend y FRONTEND_ORIGIN en el backend.`);
  error.cause = lastError;
  throw error;
}

function shouldTryNextApiBase(response, _payload, basesToTry, index) {
  if (index >= basesToTry.length - 1) return false;

  return response.status === 404 || response.status === 405;
}

window.LitterallyApi = {
  get baseUrl() {
    return activeApiBase;
  },
  baseUrlCandidates: apiBaseCandidates,
  get: (path) => apiRequest(path),
  post: (path, body) => apiRequest(path, {
    method: 'POST',
    body: JSON.stringify(body),
  }),
};

let currentUser = null;

function setAuthState(user) {
  currentUser = user;
  document.body.classList.toggle('authenticated', Boolean(user));
  document.body.classList.toggle('unauthenticated', !user);
  document.dispatchEvent(new CustomEvent('litterally:auth', { detail: { user } }));
}

function setAuthShellState(isAuthenticated) {
  document.body.classList.toggle('authenticated', Boolean(isAuthenticated));
  document.body.classList.toggle('unauthenticated', !isAuthenticated);
}

function apiHttpErrorMessage(status) {
  if (status === 404) {
    return 'No se ha encontrado la API. Comprueba VITE_API_URL en Vercel; el frontend puede estar llamando al dominio equivocado.';
  }

  return `Error HTTP ${status}`;
}

function getAuthToken() {
  try {
    return localStorage.getItem(authTokenKey);
  } catch {
    return '';
  }
}

function setAuthToken(token) {
  try {
    if (token) {
      localStorage.setItem(authTokenKey, token);
    } else {
      localStorage.removeItem(authTokenKey);
    }
  } catch {
    // Cookies remain the primary session mechanism when local storage is unavailable.
  }
}

async function loadCurrentUser() {
  const token = getAuthToken();
  if (!token) {
    setAuthState(null);
    return;
  }

  setAuthShellState(true);

  try {
    const data = await apiRequest('/api/me');
    setAuthState(data.user);
  } catch (error) {
    if (error?.status === 401) {
      setAuthToken(null);
    }
    setAuthState(null);
  }
}

function setFormMessage(form, message, state = 'error') {
  const target = form.parentElement?.querySelector('[data-form-message]');
  if (!target) return;
  target.textContent = message;
  target.dataset.state = state;
}

function setupLogin() {
  const form = document.querySelector('[data-login-form]');
  if (!form) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Procesando...';

    try {
      const data = await apiRequest('/api/auth/login', {
        method: 'POST',
        body: JSON.stringify({
          email: form.email.value.trim(),
          password: form['contraseña'].value,
        }),
      });

      setAuthToken(data.token);
      setAuthState(data.user);
      setFormMessage(form, 'Sesión iniciada.', 'success');
      window.location.href = 'content/pUsuario.html';
    } catch (error) {
      setFormMessage(form, error.message);
    } finally {
      button.disabled = false;
      button.textContent = originalText;
    }
  });
}

function setupRegister() {
  const form = document.querySelector('[data-register-form]');
  if (!form) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Procesando...';

    try {
      await apiRequest('/api/auth/register', {
        method: 'POST',
        body: JSON.stringify({
          name: form.nombre.value.trim(),
          email: form.email.value.trim(),
          password: form['contraseña'].value,
          passwordConfirmation: form['confirmar_contraseña'].value,
        }),
      });

      setFormMessage(form, 'Cuenta creada. Ya puedes iniciar sesión.', 'success');
      form.reset();
    } catch (error) {
      setFormMessage(form, error.message);
    } finally {
      button.disabled = false;
      button.textContent = originalText;
    }
  });
}

function setupLogout() {
  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-logout]');
    if (!button) return;

    button.disabled = true;
    try {
      await apiRequest('/api/auth/logout', { method: 'POST' });
    } finally {
      setAuthToken(null);
      setAuthState(null);
      window.location.href = '/index.html';
    }
  });
}

function setupProgressButtons() {
  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-progress-activity-id]');
    if (!button) return;

    event.preventDefault();

    if (!currentUser) {
      window.location.href = '/login.html';
      return;
    }

    button.disabled = true;
    setProgressMessage(button, 'Guardando progreso…');
    try {
      await apiRequest('/api/progress', {
        method: 'POST',
        body: JSON.stringify({
          activityId: Number(button.dataset.progressActivityId),
          score: Number(button.dataset.progressScore || 0),
          completed: true,
        }),
      });
      button.textContent = 'Completada';
      setProgressMessage(button, 'Actividad completada y progreso guardado.', 'success');
    } catch (error) {
      button.disabled = false;
      setProgressMessage(button, error.message || 'No se pudo guardar el progreso.', 'error');
    }
  });
}

function setProgressMessage(button, message, state = 'info') {
  let messageNode = button.parentElement?.querySelector('[data-progress-message]');

  if (!messageNode) {
    messageNode = document.createElement('p');
    messageNode.className = 'progress-save-message';
    messageNode.dataset.progressMessage = '';
    messageNode.setAttribute('role', 'status');
    messageNode.setAttribute('aria-live', 'polite');
    button.insertAdjacentElement('afterend', messageNode);
  }

  messageNode.textContent = message;
  messageNode.dataset.state = state;
}

function renderProgressWidgets(user) {
  for (const node of document.querySelectorAll('[data-progress-widget]')) {
    if (user) {
      node.innerHTML = '<div class="widget-progreso"><p class="widget-progreso-msg">Tu progreso se guarda automáticamente.</p><a href="/content/pUsuario.html" class="link-perfil-progreso">Ver mi perfil</a></div>';
    } else {
      node.innerHTML = '<div class="widget-no-login"><p class="widget-no-login-msg">Inicia sesión para guardar tu progreso.</p><a href="/login.html" class="btn-login-progreso">Iniciar sesión</a></div>';
    }
  }
}

function setupProgressWidgets() {
  document.addEventListener('litterally:auth', (event) => {
    renderProgressWidgets(event.detail.user);
  });
}

function setupProfile() {
  const page = document.querySelector('[data-profile-page]');
  if (!page) return;

  document.addEventListener('litterally:auth', async (event) => {
    const user = event.detail.user;
    if (!user) {
      window.location.href = '/login.html';
      return;
    }

    page.querySelector('[data-user-name]').textContent = user.name;
    page.querySelector('[data-user-email]').textContent = user.email;
    page.querySelector('[data-user-id]').textContent = String(user.id).padStart(5, '0') + 'A';
    page.querySelector('[data-user-year]').textContent = user.registeredYear || '';
    renderSavedReflections(page);

    try {
      const data = await apiRequest('/api/progress');
      renderProfileProgress(page, data);
    } catch (error) {
      page.querySelector('[data-progress-history]').innerHTML = `<div class="profile-error">${error.message}</div>`;
    }
  });
}

function renderSavedReflections(page) {
  const target = page.querySelector('[data-reflections-history]');
  if (!target) return;

  let reflections = {};
  try {
    reflections = JSON.parse(localStorage.getItem('litterally_reflections_v1') || '{}');
  } catch {
    target.innerHTML = '<div class="profile-error">No se han podido leer tus respuestas guardadas.</div>';
    return;
  }

  const entries = Object.values(reflections)
    .filter((item) => item && item.answer)
    .sort((left, right) => String(right.updatedAt || '').localeCompare(String(left.updatedAt || '')));

  if (entries.length === 0) {
    target.innerHTML = '<div class="empty-history"><p>Aún no has guardado ninguna reflexión.</p></div>';
    return;
  }

  target.innerHTML = entries.map((item) => `
    <article class="reflection-item">
      <h4>${escapeHtml(item.title || 'Reflexión')}</h4>
      <p class="reflection-question">${escapeHtml(item.question || '')}</p>
      <p class="reflection-answer">${escapeHtml(item.answer)}</p>
      <a href="${escapeHtml(item.chapterUrl || '#')}">Ir al capítulo y editar</a>
    </article>
  `).join('');
}

function renderProfileProgress(page, data) {
  const stats = data.stats || {};
  const progress = data.progress || [];
  const categories = data.categories || [];
  const total = Number(stats.totalActivities || 0);
  const completed = Number(stats.completed || 0);
  const percent = total > 0 ? Math.round((completed / total) * 100) : 0;

  page.querySelector('[data-progress-fill]').style.width = `${percent}%`;
  page.querySelector('[data-progress-percent]').textContent = `${percent}%`;
  page.querySelector('[data-progress-summary]').textContent = `${completed} de ${total}`;
  page.querySelector('[data-average-score]').textContent = stats.averageScore ? Math.round(stats.averageScore) : '-';
  page.querySelector('[data-best-score]').textContent = stats.bestScore || '-';

  const categoryTarget = page.querySelector('[data-progress-categories]');
  categoryTarget.innerHTML = categories.map((item) => `
    <div class="category-card">
      <div class="category-name">${escapeHtml(item.type || 'Actividad')}</div>
      <div class="category-info-row"><span>Exito:</span> <strong>${item.completed || 0} / ${item.total || 0}</strong></div>
      <div class="category-info-row"><span>Media:</span> <strong>${item.average ? Math.round(item.average) : '-'}</strong></div>
    </div>
  `).join('');

  const historyTarget = page.querySelector('[data-progress-history]');
  if (progress.length === 0) {
    historyTarget.innerHTML = '<div class="empty-history"><p>Aun no has completado ninguna actividad.</p></div>';
    return;
  }

  historyTarget.innerHTML = progress.map((item) => `
    <div class="activity-item">
      <div class="activity-info">
        <div class="activity-name">${escapeHtml(item.workName || `Actividad #${item.activityId}`)}</div>
        <div class="activity-meta">
          <span class="activity-tag">${escapeHtml(item.type || 'General')}</span>
          <span class="activity-level">Nivel: ${escapeHtml(item.level || 'Normal')}</span>
        </div>
      </div>
      <div class="activity-result">
        <div class="activity-points">
          <div class="points-number">${Number(item.score || 0)}</div>
          <div class="points-label">puntos</div>
        </div>
        <div class="activity-status">COMPLETADA</div>
      </div>
    </div>
  `).join('');
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

setupLogin();
setupRegister();
setupLogout();
setupProgressButtons();
setupProgressWidgets();
setupProfile();
loadCurrentUser();
