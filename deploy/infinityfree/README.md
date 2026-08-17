# ViloCare InfinityFree Deployment

This folder contains two InfinityFree deployment layouts:

- `upload_package`: split layout for `/htdocs` + `/vilocare_app`
- `upload_package_htdocs`: full-app layout where everything is uploaded inside `/htdocs`

Your current deployment target is the second option, because the live InfinityFree setup works only when the full app is inside `htdocs`.

## Current InfinityFree constraints

As of May 12, 2026, InfinityFree's Laravel guidance says free hosting does not provide SSH or server-side command execution for `composer` or `artisan`. Prepare everything locally, then upload the finished files.

Practical implications for ViloCare:

- run `composer install --no-dev` locally
- build frontend assets locally with `npm run build`
- upload the generated `vendor` folder
- keep `QUEUE_CONNECTION=sync`
- keep `CACHE_STORE=file`
- keep `SESSION_DRIVER=file`
- do not depend on Redis, workers, or server cron jobs

## Local preparation

Run these commands from the Laravel project root before packaging:

```powershell
composer install --no-dev
npm install
npm run build
php artisan storage:link
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Build the all-in-htdocs package

For the layout that keeps every deployable file inside InfinityFree `htdocs`, run:

```powershell
powershell -ExecutionPolicy Bypass -File .\deploy\infinityfree\build-package-htdocs.ps1
```

That script recreates:

- `deploy/infinityfree/upload_package_htdocs/htdocs`
- `deploy/infinityfree/htdocs_full_app_upload.zip`

## Resulting live folder structure

Upload the contents of `deploy/infinityfree/upload_package_htdocs/htdocs` directly into remote `/htdocs`.

That package contains:

```text
/htdocs
  app
  bootstrap
  config
  database
  resources
  routes
  storage
  vendor
  .htaccess
  index.php
  artisan
  composer.json
  composer.lock
  build
  css
  images
  uploads
  .env.example
  ...
```

## Front controller for all-in-htdocs

When the full app lives in `/htdocs`, use `deploy/infinityfree/htdocs-root-index.php` as the remote `/htdocs/index.php`.

That file expects the Laravel app root and public assets to live in the same directory.

## Production env file

Use `deploy/infinityfree/env.production.example` as the starting point for the live `.env`.

The `build-package-htdocs.ps1` script also copies it into the package as:

- `upload_package_htdocs/htdocs/.env.example`

Before going live:

- rename `.env.example` to `.env` on the server, or upload a prepared `.env`
- generate a fresh `APP_KEY`
- set the real InfinityFree database host, name, username, and password
- set real SMTP, Twilio, OpenAI, reCAPTCHA, and MTN MoMo credentials
- disable any sandbox or simulator settings you do not want in production

## Local safety

These deploy scripts only create copies under `deploy/infinityfree`. They do not restructure or overwrite the local localhost app, so local development keeps working.

## Security

Do not commit live production secrets into this repository. If any secrets were previously saved here, rotate them before deployment.
