# Deployment split

This repository now keeps the original PHP version and adds a deployable split:

- `frontend/`: static multi-page app for Vercel.
- `backend/`: Node API for auth, progress, user profile, and Litto chatbot.
- `backend/schema/litterally.sql`: original MySQL schema/data export.
- `backend/schema/supabase.sql`: Supabase/Postgres import file generated from the MySQL export.

## Frontend on Vercel

Create a Vercel project using this repository with:

- Root Directory: `frontend`
- Framework Preset: `Other`
- Build Command: `npm run build`
- Output Directory: `dist`

Set this environment variable:

```bash
VITE_API_URL=https://your-backend.vercel.app
```

After changing frontend environment variables, redeploy the frontend project so the static build receives the new values.

For local frontend work:

```bash
cd frontend
npm install
npm run dev
```

## Backend on Vercel

Create a second Vercel project using this repository with:

- Root Directory: `backend`
- Framework Preset: `Other`

Set these environment variables:

```bash
DATABASE_URL=postgresql://postgres.PROJECT_REF:PASSWORD@aws-0-eu-west-1.pooler.supabase.com:6543/postgres
JWT_SECRET=replace-with-a-long-random-secret
FRONTEND_ORIGIN=https://your-frontend.vercel.app
```

Use the Supabase transaction pooler connection string for serverless Vercel functions. If you use local Postgres instead of Supabase, set `DATABASE_SSL=false`.

For local backend work:

```bash
cd backend
npm install
cp .env.example .env
npm run dev
```

## Database

Create a Supabase project, open SQL Editor, then run:

```bash
backend/schema/supabase.sql
```

If `litterally.sql` changes later, regenerate the Supabase import:

```bash
cd backend
npm run schema:supabase
```

The backend keeps compatibility with the old `users.contraseña` column. When old plain-text users log in successfully, their password is upgraded to a bcrypt hash.

## What changed

- Public pages moved from `.php` to `.html`.
- Navigation points to static frontend routes.
- Login/register/profile/progress now call JSON API endpoints.
- Activity pages use `data-progress-*` buttons instead of PHP helper includes.
- Litto calls `POST /api/chatbot`.
- `database.php` hardcoded local MySQL is no longer used by deployed code.
- Backend uses `pg` and Supabase/Postgres connection strings.

## Remaining hardening

- Replace demo database credentials with a real managed DB.
- Rotate `JWT_SECRET` before production.
- Review old content for editorial typos and missing accents.
- Add automated tests after the first working deploy.
