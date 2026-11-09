# CatatIn

CatatIn is a Laravel web application for personal learning and productivity. Users can manage study notes, categories, tasks, goals and progress, saved media, private attachments, and an AI learning assistant.

## Stack

- Laravel 11 and PHP 8.3+
- Blade, Tailwind CSS 3, Vite 5, and Lucide icons
- MySQL for local/deployed application data
- Ollama for local AI chat; the legacy AI transcription feature is currently disabled

## Features

- Account registration, login/logout, password reset, and account settings.
- Notes with search, categories, favorites, and private attachments.
- Tasks with status, category, due date, filtering, and search.
- Goals with progress history and optional private images.
- Saved media links and favorites.
- CatatIn AI with user-specific conversation history, relevant notes/productivity/saved-media retrieval, and source references.
- Admin dashboard and user administration for authorized administrators.

## Local setup (Laragon / Windows)

Requirements: PHP 8.3+, Composer, Node.js/npm, and MySQL. Use the PHP 8.3 runtime configured in Laragon; enable the PHP extensions required by Laravel (including `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, and `zip`).

1. Copy `.env.example` to `.env` if needed and set a unique local `APP_KEY` with `php artisan key:generate`.
2. Create a local MySQL database and set `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.
3. Install dependencies and build assets:

   ```sh
   composer install
   npm ci
   npm run build
   ```

4. **Review pending migrations before applying them.** Do not run `php artisan migrate` on a database containing data you need until the pending `2026_11_07_000000_remove_daily_activities` migration has been reviewed: it drops the `daily_activities` table. Back up the database and decide how to preserve/archive those records first. The migration status can be inspected with `php artisan migrate:status`; do not use `migrate:fresh` or `db:wipe`.
5. Once migrations have been reviewed and the database is backed up, apply approved migrations with `php artisan migrate`.
6. Start the application with `php artisan serve` and open `http://127.0.0.1:8000`.

For iterative frontend development, use `npm run dev`. Keep the local Laragon `.env` private; never commit it.

## Environment configuration

Configure these values in the deployment environment, not in source control:

- **Application:** `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`, and a unique `APP_KEY`.
- **Database:** production MySQL connection values. Do not point automated tests at production.
- **Session/cache/queue:** keep `SESSION_DRIVER=database` for password-reset session revocation and provision the required tables. Configure persistent production-compatible cache/queue drivers and their tables/services as needed.
- **Mail:** `MAIL_MAILER=log` is for local development only and does not send email. Password reset reports that no email was sent and writes notifications to the local log in this mode. Configure and verify SMTP or another supported delivery transport before enabling real password resets.
- **File storage:** learning attachments and goal images use the private `learning_private` disk and authorized application routes; they are not public assets. Configure persistent private storage or an appropriately protected object store for deployment. The `public` disk's storage symlink is for intentionally public files only.
- **Admin bootstrap (optional):** set `ADMIN_PASSWORD` only in a local/deployment secret store before running the approved `AdminSeeder`. Never put the value in Git.

Run `php artisan config:cache` only after deployment environment variables have been set; clear cached configuration when changing them.

## CatatIn AI and transcription

Chat uses a locally reachable Ollama service. Configure `OLLAMA_BASE_URL` and `OLLAMA_MODEL`; install/pull the chosen model separately. Retrieval is scoped to the signed-in user's notes, attachments, tasks, goals, and accessible saved-media metadata (title, description, platform, and category); media links do not imply that video contents are analyzed. Chat requests are rate limited per signed-in user. CatatIn does not automatically download models or send prompts to a paid external AI API. A hosted demo cannot access Ollama on a developer's computer: deploy an Ollama service privately and securely, or mark AI as unavailable in the demo.

Audio/video transcription is currently disabled: no user-facing transcription route is registered, and the queued worker marks legacy pending jobs as disabled. Existing transcription records are retained, but new media is not transcribed. Restoring this feature requires implementing and testing its authenticated upload/status/result flow and installing whisper.cpp, FFmpeg/ffprobe, and a compatible model on the host. No model is downloaded automatically.

## Tests and checks

The PHPUnit suite uses the database configured in `phpunit.xml`; verify it points to an isolated test database or an in-memory database before running:

```sh
php artisan test
npm run build
php artisan route:list
php artisan migrate:status
```

The project has feature coverage for authentication/password reset, notes, productivity, categories, attachments, admin access, and AI behavior. Add regression tests for any behavior changes. Do not run the suite against a production database.

## Deployment notes

This is a Laravel application with MySQL, persistent sessions/cache, private file storage, and optional Ollama. Vercel is not the recommended host for the complete application: its serverless model does not provide the persistent local storage or long-running local Ollama service this project expects.

Railway's Railpack reads the PHP version constraint from `composer.json`; the `^8.3` requirement selects PHP 8.3 for builds and runtime. Keep `composer.lock` committed so Railway installs the audited dependency versions.

Deploy the Laravel backend to a PHP-capable host (for example, a managed Laravel/PHP platform or a VPS), use managed MySQL and persistent private/object storage, configure HTTPS and production secrets, and run only reviewed migrations after taking a backup. GitHub can host the source repository and CI; Vercel may host a separate static frontend if one is introduced, but it does not replace the Laravel backend. Confirm every optional external service is reachable from the deployed backend.

## Security and data handling

- Private application routes authorize records against the signed-in user; admin routes require admin authorization.
- Do not publish `.env`, credentials, AI model files, private storage, SQL dumps, database backups, or logs.
- Use HTTPS, secure production cookies, a least-privilege database account, backups, and monitored error reporting in deployment.
- Review migrations and retention requirements before changing or deleting stored user data.
