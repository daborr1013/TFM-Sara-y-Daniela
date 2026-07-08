# Litterally - Plataforma de Análisis Literario Interactivo

Litterally es una plataforma diseñada para transformar la lectura pasiva en una experiencia inmersiva e interactiva. Los lectores pueden explorar personajes, simbología y completar actividades de comprensión sobre grandes obras literarias.

## Deploy moderno

El proyecto conserva la versión PHP original y añade una separación deployable:

- `frontend/`: app estática para Vercel.
- `backend/`: API Node para login, registro, perfil, progreso y Litto sobre Supabase/Postgres.

Guía completa: [`DEPLOYMENT_SPLIT.md`](DEPLOYMENT_SPLIT.md).

Flujo de cambios con Vercel: [`GUIA_FLUJO_PR_VERCEL.md`](GUIA_FLUJO_PR_VERCEL.md).

## 🚀 Características
- **Análisis Profundo**: Secciones dedicadas a personajes, contextos históricos y temas.
- **Interactividad**: Actividades tipo test, rellenar espacios y juegos de memoria.
- **Seguimiento de Progreso**: Panel de usuario con estadísticas y logros.
- **Asistente Virtual**: "Litto", un compañero para guiarte en tus lecturas.

## 🛠️ Tecnologías
- **Frontend**: HTML5, CSS3 (Vanilla), JavaScript (ES6+).
- **Backend deployable**: Node.js sobre Vercel Functions.
- **Base de Datos deployable**: Supabase/Postgres.
- **Versión original conservada**: PHP 8.x + MySQL.

## 📁 Estructura del Proyecto (Módulos Principales)
- `frontend/`: aplicación estática que se despliega en Vercel.
- `backend/`: API Node para autenticación, perfil, progreso y Litto.
- `content/`: versión PHP original conservada como referencia.
- `media/`: recursos visuales y gráficos.

## 🔧 Instalación y Configuración
Consulta [`DEPLOYMENT_SPLIT.md`](DEPLOYMENT_SPLIT.md) para configurar los dos proyectos de Vercel:

1. Proyecto frontend con Root Directory `frontend`.
2. Proyecto backend con Root Directory `backend`.
3. Supabase/Postgres cargando `backend/schema/supabase.sql`.

La instalación PHP/XAMPP queda como versión histórica del proyecto, no como ruta principal de despliegue.

## ✍️ Autoras
Proyecto desarrollado para el **TFM – Letras Digitales – UCM**.

---
*Litterally: Porque no se trata solo de leer, sino de lo que sucede mientras lees.*
