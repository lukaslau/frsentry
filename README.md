# FrSentry — Sentry Integration for PrestaShop

Real-time error monitoring for PrestaShop. Automatically captures PHP exceptions, database failures, and JavaScript errors and forwards them to your [Sentry](https://sentry.io) project so you can find and fix problems before customers notice them.

---

## Merchant Benefits

- **Stop losing sales to silent errors.** Unhandled PHP exceptions, database failures, and JavaScript crashes are captured automatically — you see them in Sentry before a customer files a complaint.
- **Save hours of debugging.** Every error arrives with a full stack trace, the SQL query that failed, the page URL, HTTP method, and (when logged in) the customer ID and cart ID.
- **Zero performance cost when idle.** The module adds no overhead until an error actually occurs; if no DSN is configured it is completely passive.
- **Configurable noise filtering.** Suppress E_NOTICE, E_WARNING, E_DEPRECATED, and trigger_error calls independently so your Sentry inbox shows only what matters.
- **Performance tracing built in.** Optionally record transaction timings for every PHP request and front-end page load. Adjust sampling rates (0–100 %) to control event volume and cost.

---

## Features

### Error Monitoring

- Captures all PHP error levels: `E_ERROR`, `E_PARSE`, `E_CORE_ERROR`, `E_COMPILE_ERROR`, `E_RECOVERABLE_ERROR`, `E_WARNING`, `E_NOTICE`, `E_DEPRECATED`, and user-triggered variants
- Fatal errors caught via `register_shutdown_function` — even parse-time crashes reach Sentry
- Database failures captured with the full offending SQL query (stored as a Sentry context block, not truncated as a tag)
- JavaScript errors captured via the official Sentry Browser SDK; errors from browser extensions and third-party domains are filtered out automatically
- Duplicate suppression: the same exception is reported at most once per request
- Back-office monitoring can be enabled or disabled independently of the front office

### Rich Context on Every Event

| Field | Source |
|---|---|
| PHP version | `PHP_VERSION` |
| PrestaShop version | `_PS_VERSION_` |
| Shop ID | `Context::getContext()->shop->id` |
| Language ID | `Context::getContext()->language->id` |
| Controller | `$context->controller->php_self` |
| Customer ID + email | `Context::getContext()->customer` (when logged in) |
| Cart ID | `Context::getContext()->cart->id` |
| Order ID | resolved from cart when available |
| Request URL, method, headers | captured automatically |
| POST body / uploaded file metadata | captured automatically |

### Performance Tracing (optional)

- **Backend tracing:** records a Sentry transaction per PHP request; configurable sample rate (0–100 %)
- **Backend profiling:** attaches an excimer flame graph to each sampled transaction; requires the `excimer` PHP extension
- **Frontend tracing:** uses the Sentry Browser Tracing integration; configurable sample rate
- **Frontend profiling:** uses the JS Self-Profiling API; requires `Document-Policy: js-profiling` (sent automatically)

### Compatibility

- PrestaShop **1.7.7** and above (including 8.x)
- PHP **7.2 – 8.x**
- Multistore: yes (shop ID attached to every event)
- The Sentry SDK is bundled under a private namespace (`FrSentry\`) and does not conflict with other modules that also include Sentry

---

## Customer Benefits

Shoppers experience a more reliable store. Because errors are detected and resolved faster — often before any customer-facing impact — checkout flows stay intact, product pages load correctly, and payment processing remains uninterrupted.

---

## External Service

This module requires a **Sentry** account ([sentry.io](https://sentry.io)). A free tier is available that covers small to medium stores. Error data is transmitted from your server directly to Sentry's ingestion API (`*.ingest.sentry.io`). No data passes through Frento IT servers.

You are responsible for ensuring that the data sent to Sentry (which may include customer IP addresses and email addresses when customers are logged in) complies with applicable privacy laws (GDPR, etc.) and your store's privacy policy.

---

## Requirements

| Requirement | Minimum |
|---|---|
| PrestaShop | 1.7.7 |
| PHP | 7.2 |
| Sentry account | Free tier or higher |
| PHP `excimer` extension | Optional — required only for backend flame-graph profiling |

---

## Installation

1. Download the module ZIP from the PrestaShop Marketplace.
2. In your PrestaShop back office go to **Modules → Module Manager → Upload a module** and upload the ZIP.
3. Click **Install**.
4. Go to **Modules → Module Manager**, find *Sentry Integration For PrestaShop*, and click **Configure**.

---

## Configuration

### 1. Obtain a Sentry DSN

1. Log in to [sentry.io](https://sentry.io) and create a project (Platform: **PHP** for backend, **JavaScript/Browser** for frontend, or create two separate projects).
2. Go to **Settings → Projects → {your project} → Client Keys (DSN)**.
3. Copy the DSN (format: `https://PUBLIC_KEY@oXXXXX.ingest.sentry.io/PROJECT_ID`).

### 2. Enter the DSN in the module

Open the module configuration page and paste the DSN into the appropriate field:

- **Backend DSN Key** — PHP server-side errors, exceptions, and database failures.
- **Frontend DSN Key** — JavaScript browser errors. Can be the same project or a different one.

Use the **Send test event** button next to each field to verify the connection before saving.

### 3. Backend Settings

| Setting | Default | Description |
|---|---|---|
| Monitor back office | Off | Send errors that occur in the PrestaShop admin panel |
| Ignore user errors | On | Suppress `E_USER_ERROR`, `E_USER_WARNING`, `E_USER_NOTICE` |
| Ignore deprecated | On | Suppress `E_DEPRECATED`, `E_USER_DEPRECATED` |
| Ignore warnings | On | Suppress `E_WARNING` |
| Ignore notices | On | Suppress `E_NOTICE` |

### 4. Frontend Settings

| Setting | Default | Description |
|---|---|---|
| Performance insights | Off | Enable the Browser Tracing integration |
| Tracing sample rate | 20 % | Percentage of page loads that create a Sentry transaction |
| Performance profiling | Off | Enable the JS Self-Profiling integration |
| Profiling sample rate | 20 % | Percentage of traced loads that also record a JS profile |

### 5. Backend Performance (optional)

| Setting | Default | Description |
|---|---|---|
| Enable tracing | Off | Record a Sentry transaction per PHP request |
| Transaction sampling rate | 100 % | Percentage of requests that create a transaction |
| Enable profiling | Off | Attach an excimer flame graph to sampled transactions |
| Profile sampling rate | 100 % | Percentage of traced requests that also include a profile |

> **Note:** Backend profiling requires the `excimer` PHP extension (`apt install php-excimer` on Debian/Ubuntu). The configuration page shows whether the extension is loaded.

---

## Manual Exception Capture

You can capture exceptions from any other module or custom code:

```php
Module::getInstanceByName('frsentry')->captureException(
    new Exception('Something went wrong'),
    ['type' => 'my_module', 'orderId' => (int) $orderId]
);
```

The second argument is an optional array of tags that appear in Sentry alongside the standard PrestaShop context.

---

## Privacy & Data

The following data is sent to Sentry when an error occurs:

- Visitor IP address
- Request URL, HTTP method, and request headers (Authorization and Cookie headers are masked)
- POST body fields and uploaded file names/sizes (file contents are never sent)
- Customer ID and email address (only when the customer is logged in and a backend DSN is configured)
- Cart ID and order ID (when available in the current context)

To disable sending customer-identifying information, leave the **Backend DSN Key** empty or turn off the module entirely for logged-in sessions in your Sentry project's data scrubbing settings.

---

## Support

For support requests, bug reports, and feature suggestions, please use the support channel on the [PrestaShop Marketplace](https://addons.prestashop.com) product page.

---

## License

Commercial license — see the license agreement included with your purchase.

© 2024–2026 Frento IT
