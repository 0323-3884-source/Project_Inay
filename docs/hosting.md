# Railway and Render deployment

Platform references: [Railway health checks](https://docs.railway.com/deployments/healthchecks), [Render Docker](https://render.com/docs/docker), [Render persistent disks](https://render.com/docs/disks), and [Render free-service limits](https://render.com/docs/free).

Both hosts use the root `Dockerfile`. It builds Vite assets, installs production PHP dependencies, listens on the host's `PORT`, and prepares Laravel storage and caches at startup. Use `/up` as the health-check path. Leave the start command unset to use the Docker command.

## Required environment

Set these in the hosting dashboard, not in a committed `.env` file:

```dotenv
APP_NAME="Project INAY"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_EXISTING_KEY
APP_URL=https://YOUR_SERVICE_DOMAIN
LOG_CHANNEL=stderr
LOG_LEVEL=warning
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local
```

Keep your existing APP_KEY when moving an existing installation. For a new installation, generate one once with `php artisan key:generate --show` and save it as a secret. Do not generate a new key at every deploy. Leave SESSION_DOMAIN unset unless you deliberately share sessions across subdomains.

Use `QUEUE_CONNECTION=database` only with a separate worker running `php artisan queue:work --tries=3 --timeout=90` from the same image and environment.

## Database

Use a managed database, not localhost or the local XAMPP database. Set either DB_URL or individual DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD variables; remove any stale DB_URL when using individual variables.

- Railway MySQL: set `DB_CONNECTION=mysql` and reference the MySQL service's connection variables in the web service. Internal service addresses work within the same Railway project/network.

  For a database service named `MySQL`, set `DB_URL=${{MySQL.MYSQL_URL}}` on the **web service**. Replace `MySQL` with the actual database service name. Adding a database service alone does not configure the web service's variables. Remove conflicting old `DB_URL` or `DATABASE_URL` values. URL-based driver detection supports MySQL and PostgreSQL when `DB_CONNECTION` is unset; an explicit `DB_CONNECTION` takes precedence.
- Render PostgreSQL: set `DB_CONNECTION=pgsql` and `DB_URL` to the database's internal connection URL. Set `DB_SSLMODE=require` when required by the database provider. PostgreSQL support is installed in the image; validate your existing data migration separately before switching database engines.
- Render can also use an externally hosted MySQL database with `DB_CONNECTION=mysql` and the provider's externally reachable connection settings.

For MySQL requiring a CA, set `MYSQL_ATTR_SSL_CA=/var/www/html/storage/certificates/ca.pem` if that file is the correct CA for your provider. A Windows path will not work in Linux. Keep TLS verification enabled. Do not put client private keys in the image.

Migrations run automatically on container startup (`RUN_MIGRATIONS` defaults to `true`). If you want to disable automatic startup migrations, set `RUN_MIGRATIONS=false`. Back up an existing database before migration. Never use `migrate:fresh`, `db:wipe`, or test seeders against hosted records. New installations need migrations before database sessions/cache can work.

Startup stops after five failed migration attempts so Railway cannot mark a deployment ready while its database is unavailable or its schema is incomplete. Inspect the first migration error in deployment logs.

## Persistent uploads

The application uses local public and private file storage. Attach a Railway volume or Render persistent disk at `/var/www/html/storage/app`. Without persistence, uploads disappear when the container is replaced. Keep a single web instance with this local storage arrangement; horizontal scaling needs shared file storage and application changes. Copy existing uploads into the volume when migrating.

Do not mount all of `/var/www/html` or `storage`: this hides application files or the bundled CA certificate. Framework caches are intentionally recreated inside each container.

## Railway

Deploy the repository using the root Dockerfile; `railway.json` supplies build and health-check settings. Add your database and volume, configure the environment above, and generate a public domain. Set APP_URL to that HTTPS domain. Clear any old custom start command or port override that conflicts with the Docker startup.

If the domain shows Railway's "Not Found / The train has not arrived" page while the deployment is Active, inspect **Project_Inay > Settings > Networking**. Confirm that this exact domain belongs to the web service in the active environment and that its target port matches the startup log's `Using PORT` value (8080 by default). A successful `/up` health check does not verify the public domain mapping. Do not point the web domain at the MySQL service. Save any corrected networking settings and test both `/up` and `/login` on the displayed domain.

Startup clears local compiled files before migrations, then builds configuration and view caches. It deliberately does not run `optimize:clear`, which would try to clear the database cache before the cache table exists on a new installation.

## Render

Create a Docker web service from this repository with Dockerfile path `./Dockerfile`, health check `/up`, the environment above, and a persistent disk at the upload path above. Use a plan that supports persistent disks for uploaded records. The server binds to all interfaces using Render's PORT. Set APP_URL to the final HTTPS service domain. Configure the migration command before using the app.

## Email and troubleshooting

Configure real MAIL_* credentials in the host dashboard. Use a mail provider and transport permitted by your hosting plan; do not assume Gmail SMTP is reachable. Render free web services block outbound SMTP ports 25, 465 and 587. The current app supports SMTP; an API email transport requires its matching Composer package and provider configuration.

- Build failure: inspect the first failed Docker RUN step, not only the final deployment message.
- Startup failure: check APP_KEY, PORT, filesystem permissions and migration errors in service logs.
- Database failure: check network reachability, DB_CONNECTION, credentials and Linux CA path.
- Missing styles: confirm the Vite build succeeded and no local `public/hot` file was deployed.
- Missing uploads: check the volume mount and `public/storage` link.
- Login/session errors: check APP_URL, secure cookies, migrations and a stable APP_KEY across restarts.

`/up` confirms Laravel boots, not that every database query or email delivery works. After deployment, check login, an existing profile, upload/download, appointments and password-reset delivery. A local test run cannot verify hosted networking or credentials.
