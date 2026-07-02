Config y despliegue - Variables de entorno

- `ARTIST_API_SECRET` (requerido): clave secreta compartida entre tu plataforma PHP y el servicio Next.js para autorizar llamadas a `/api/artists/claim` y operaciones privadas.
- `PUBLIC_ARTIST_URL` (opcional): URL pública donde se sirve la app Next.js (ej. `https://microsites.tudominio.com` o `http://localhost:3000` para pruebas locales).

Docker (local)
- Copia `.env.example` a `.env` y reemplaza `ARTIST_API_SECRET` por un valor seguro (256-bit o 32+ bytes en hex/base64).
- Luego:
```
docker compose up --build
```
La app Next.js leerá la variable y la expondrá internamente; desde tu PHP, usa `PUBLIC_ARTIST_URL` para construir la URL del endpoint.

Vercel (producción)
- En el panel del proyecto en Vercel > Settings > Environment Variables, añade `ARTIST_API_SECRET` con el mismo valor que uses en tu plataforma PHP.
- Añade `PUBLIC_ARTIST_URL` apuntando a la URL de despliegue de la app (ej. `https://artist-microsite.vercel.app`).

Seguridad
- Nunca subas `.env` al repositorio. Añade `.env` a `.gitignore` si no está ya.
- Usa un secreto largo y único. Puedes generarlo con `openssl rand -hex 32` o `node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"`.
