const configuredApiBase = window.LITTERALLY_API_URL || '__LITTERALLY_API_URL__';
const apiBase = (configuredApiBase.startsWith('__LITTERALLY_') ? '' : configuredApiBase).replace(/\/$/, '');

const appCss = document.createElement('link');
appCss.rel = 'stylesheet';
appCss.href = '/css/app.css';
document.head.append(appCss);

async function apiRequest(path, options = {}) {
  const headers = {
    ...(options.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
    ...(options.headers || {}),
  };

  const response = await fetch(`${apiBase}${path}`, {
    credentials: 'include',
    ...options,
    headers,
  });

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    const message = payload?.error || payload?.message || `Error HTTP ${response.status}`;
    throw new Error(message);
  }

  return payload;
}

window.LitterallyApi = {
  baseUrl: apiBase,
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

async function loadCurrentUser() {
  try {
    const data = await apiRequest('/api/me');
    setAuthState(data.user);
  } catch {
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
    button.disabled = true;

    try {
      const data = await apiRequest('/api/auth/login', {
        method: 'POST',
        body: JSON.stringify({
          email: form.email.value.trim(),
          password: form['contraseña'].value,
        }),
      });

      setAuthState(data.user);
      setFormMessage(form, 'Sesión iniciada.', 'success');
      window.location.href = 'content/pUsuario.html';
    } catch (error) {
      setFormMessage(form, error.message);
    } finally {
      button.disabled = false;
    }
  });
}

function setupRegister() {
  const form = document.querySelector('[data-register-form]');
  if (!form) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;

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
      setAuthState(null);
      window.location.href = '/index.html';
    }
  });
}

function setupProgressButtons() {
  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-progress-activity-id]');
    if (!button) return;

    if (!currentUser) {
      window.location.href = '/login.html';
      return;
    }

    button.disabled = true;
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
    } catch (error) {
      button.disabled = false;
      window.alert(error.message);
    }
  });
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

    try {
      const data = await apiRequest('/api/progress');
      renderProfileProgress(page, data);
    } catch (error) {
      page.querySelector('[data-progress-history]').innerHTML = `<div class="profile-error">${error.message}</div>`;
    }
  });
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
