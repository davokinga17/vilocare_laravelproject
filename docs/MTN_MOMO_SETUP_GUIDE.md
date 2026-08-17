# MTN Mobile Money Setup Guide

This guide matches the current ViloCare codebase as of August 6, 2026.

## 1. What is already done in the code

The application already supports:

- selecting `MTN MoMo` on the payment form
- sending a `requesttopay` request to MTN
- saving the MTN reference on the payment record
- refreshing payment status from the payment details page
- receiving a callback on `/payments/{payment}/mtn-momo/callback`

Relevant files:

- `app/Services/MtnMomoService.php`
- `app/Http/Controllers/PaymentController.php`
- `config/services.php`
- `resources/views/payments/create.blade.php`

## 2. Create your MTN developer account

Use the MTN MoMo developer portal:

- https://momodeveloper.mtn.com/

From the official MTN developer guidance, the high-level flow is:

1. Sign up
2. Subscribe to the `Collections` product
3. Create subscription keys
4. Create an API user and API key
5. Get an access token
6. Send `RequestToPay`
7. Check status or receive callbacks

## 3. Subscribe to the correct MTN product

For this ViloCare payment flow, the needed product is:

- `Collections`

This is the MTN product used to collect money from a customer wallet into the business wallet.

## 4. Get the MTN credentials

From MTN, collect these values:

- `Collection` subscription primary key
- API user
- API key
- target environment name
- approved callback host/domain if MTN requires it during API user setup

Important:

- callbacks require the `X-Callback-Url` host to match the host configured for the API user
- MTN documents also note that the callback URL should use a hostname, not a raw IP address

## 5. Update the production `.env`

On the online server, open the ViloCare production `.env` and add:

```env
MTN_MOMO_BASE_URL=https://sandbox.momodeveloper.mtn.com
MTN_MOMO_SUBSCRIPTION_KEY=your_collection_primary_key
MTN_MOMO_API_USER=your_api_user_uuid
MTN_MOMO_API_KEY=your_api_key
MTN_MOMO_TARGET_ENVIRONMENT=sandbox
MTN_MOMO_CALLBACK_URL=
MTN_MOMO_TIMEOUT=30
```

Notes:

- keep `MTN_MOMO_CALLBACK_URL` blank unless you want to force a fixed callback header
- the app already sends a payment-specific callback route automatically
- when MTN moves you from sandbox to live, replace the base URL and target environment with the live values MTN gives you

## 6. Make sure your online domain is used

Your live app should use a real domain such as:

- `https://vilocare.infinityfree.io`

Do not use:

- `127.0.0.1`
- `localhost`
- a raw server IP

This matters because MTN validates callback hosts.

## 7. Clear Laravel cached config after editing `.env`

Run:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## 8. Test in sandbox first

After deployment:

1. Log in to the live system
2. Open `Payments`
3. Create a payment
4. Choose `MTN MoMo`
5. Enter the payer phone number
6. Submit the form
7. Confirm the payment prompt on the test wallet or test device
8. Open the payment details page
9. Click `Refresh MTN Status`

Expected result:

- the payment starts as `pending`
- after approval, it changes to `paid`
- the MTN gateway reference is stored as `external_reference`

## 9. If callback does not arrive

The system can still work because manual status refresh is available.

Check these items:

- the public callback route is reachable on the live domain
- HTTPS is working
- the callback host matches the API user host
- the MTN subscription key belongs to `Collections`
- the target environment is correct

## 9a. If you get `invalid_client`

If the app fails before creating a payment request and shows `invalid_client`, the MTN sandbox rejected the authentication credentials used for token generation.

What this usually means:

- the `MTN_MOMO_API_USER` and `MTN_MOMO_API_KEY` do not belong together
- the API key was regenerated in sandbox and the old one is still in `.env`
- the subscription key is not the `Collections` primary key
- the API user/API key came from the wrong product or old sandbox provisioning

What to do:

1. Open the MTN MoMo developer portal
2. Confirm you are subscribed to `Collections`
3. Regenerate or recreate the sandbox API user and API key for `Collections`
4. Copy the fresh values into `.env`
5. Clear Laravel config cache
6. Retry the payment

Important:

- `API user` and `API key` are not your MTN portal login email/password
- the app already sends the `Ocp-Apim-Subscription-Key` and Basic auth headers correctly
- if `invalid_client` still appears, the values from MTN are still mismatched or stale

## 10. Recommended go-live order

1. Deploy the updated code to the online server
2. Update the live `.env`
3. Clear Laravel caches
4. Test one sandbox payment
5. Confirm callback or manual status refresh works
6. Move to MTN live credentials only after sandbox succeeds
