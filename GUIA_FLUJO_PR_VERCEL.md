# Flujo de cambios con Vercel

Este proyecto debe cambiarse siempre mediante pull request.

## Regla principal

No subir cambios directos a `main`.

## Pasos

1. Crear una rama nueva desde `main`.

   ```bash
   git switch main
   git pull
   git switch -c fix/nombre-del-cambio
   ```

2. Hacer cambios pequeños.

3. Probar en local si el cambio toca frontend o backend.

   ```bash
   cd frontend
   npm run build
   ```

   ```bash
   cd backend
   npm run dev
   ```

4. Guardar cambios en commit.

   ```bash
   git status
   git add archivo-cambiado
   git commit -m "fix: descripcion corta"
   ```

5. Subir la rama.

   ```bash
   git push -u origin fix/nombre-del-cambio
   ```

6. Abrir pull request en GitHub.

7. Revisar preview/checks de Vercel desde el pull request.

8. Hacer merge solo cuando Vercel esté correcto.

## Variables necesarias en Vercel

Frontend:

```text
VITE_API_URL=https://URL-DEL-BACKEND.vercel.app
```

Backend:

```text
DATABASE_URL=postgresql://...
JWT_SECRET=valor-largo-secreto
FRONTEND_ORIGIN=https://URL-DEL-FRONTEND.vercel.app,https://URL-DEL-FRONTEND-*.vercel.app
```

