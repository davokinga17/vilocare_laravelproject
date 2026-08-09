# ViloCare InfinityFree Deployment

This folder contains the practical files to help deploy ViloCare to InfinityFree using the domain `vilocare.infinityfree.io`.

## Recommended remote folder structure

Create this layout in your InfinityFree account:

```text
/vilocare_app
  app
  bootstrap
  config
  database
  public
  resources
  routes
  storage
  vendor
  .env
  artisan

/htdocs
  index.php
  .htaccess
  build
  css
  images
  storage
  uploads
  ...
```

## What to upload where

Upload these project items to `/vilocare_app`:

- `app`
- `bootstrap`
- `config`
- `database`
- `resources`
- `routes`
- `storage`
- `vendor`
- `artisan`
- `composer.json`
- `composer.lock`
- `.env`

Upload the contents of your local `public` folder to `/htdocs`:

- `.htaccess`
- `index.php`
- `build` after running `npm run build`
- `css`
- `images`
- `storage`
- `uploads`
- icons and manifest files

Also upload this deployment-adjusted front controller:

- `deploy/infinityfree/htdocs-index.php` -> remote `/htdocs/index.php`

## Important local preparation steps

Run these locally before uploading:

```bash
php artisan storage:link
npm install
npm run build
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Replace the public entry point

Do not use the default Laravel `public/index.php` as-is on InfinityFree.

Copy [htdocs-index.php](./htdocs-index.php) into remote `/htdocs/index.php`.

That file expects the Laravel app to live in `/vilocare_app`.

## Production environment file

Use [env.production.example](./env.production.example) as your starting point for the production `.env`.

Set:

- `APP_URL=https://vilocare.infinityfree.io`
- the InfinityFree database credentials
- fresh production secrets for mail, Twilio, OpenAI, and reCAPTCHA
- the MTN MoMo variables if you want live MoMo payments

## MTN MoMo note

The app now supports MTN Mobile Money request-to-pay. For production:

- `MTN_MOMO_BASE_URL` should match the environment MTN gives you
- `MTN_MOMO_SUBSCRIPTION_KEY`, `MTN_MOMO_API_USER`, and `MTN_MOMO_API_KEY` must come from the MTN developer/partner portal
- `MTN_MOMO_TARGET_ENVIRONMENT` must match the MTN environment name
- the callback host must be on the same domain configured for the API user

If callbacks are not yet working, the app can still refresh MTN payment status manually from the payment details page.

## Security reminder

Do not reuse the current local secrets in production. Rotate all exposed credentials before going live.
