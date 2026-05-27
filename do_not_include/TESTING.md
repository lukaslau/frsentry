# FrSentry Testing Guide

Two parallel test surfaces — server-side (PHP) and client-side (JavaScript).
Both require **the module to be installed** and **DSN keys configured** for the
events to actually reach Sentry. Without keys, captures are silently dropped.

---

## Backend tests

Two ways to run them — pick whichever is more convenient:

### Option A — Standalone CLI script (recommended)

No file copying, no URL routing. Just run:

```
php do_not_include/test-backend.php exception
php do_not_include/test-backend.php warning
php do_not_include/test-backend.php mysql
php do_not_include/test-backend.php           # lists all types
```

The script bootstraps PrestaShop, fires the `moduleRoutes` hook (same path a
real HTTP request takes), then triggers the chosen error. Configure your
**Backend DSN Key** first or the script will warn that events are dropped.

### Option B — PrestaShop front controller (HTTP-based)

If you want to test through the real HTTP stack (e.g. to verify back-office
catching, custom routes, or anything that depends on a real request):

1. Copy `do_not_include/test-controller.php` to `controllers/front/test.php`.
2. Configure **Backend DSN Key** in the module settings.
3. Hit any URL below in a browser or with `curl`.
4. **Delete `controllers/front/test.php` when finished.**

All URLs are of the form `/module/frsentry/test?type=<name>`.

Hit `/module/frsentry/test` with no `type` to get the full list printed in plain text.

| Category | `type=` value | What it triggers |
|---|---|---|
| Throwable | `exception` | Generic `\Exception` |
| Throwable | `prestashop` | `\PrestaShopException` |
| Throwable | `runtime` | `\RuntimeException` |
| Throwable | `logic` | `\LogicException` |
| Throwable | `error` | `\Error` |
| Throwable | `type` | `\TypeError` (bad argument type) |
| Throwable | `division` | `\DivisionByZeroError` |
| PHP error | `warning` | `E_WARNING` (missing file read) |
| PHP error | `notice` | `E_NOTICE` / `E_WARNING` (undefined index) |
| PHP error | `deprecated` | `E_DEPRECATED` (deprecated core fn) |
| PHP error | `user_error` | `E_USER_ERROR` via `trigger_error` |
| PHP error | `user_warning` | `E_USER_WARNING` via `trigger_error` |
| PHP error | `user_notice` | `E_USER_NOTICE` via `trigger_error` |
| PHP error | `user_deprecated` | `E_USER_DEPRECATED` via `trigger_error` |
| Fatal | `fatal` | `E_ERROR` (undefined function) |
| Database | `mysql` | `SELECT` from nonexistent table |
| Database | `mysql_syntax` | Invalid SQL syntax |
| Manual | `manual` | `Module::captureException()` with extra tags |
| Manual | `manual_with_user` | Same, with customer context |
| Dedup | `dedup` | Throw same exception 3× — Sentry should show 1 |

### What to verify in Sentry

For each captured event:

- **Tags** include `type`, `php_version`, `shop_id`, `lang_id`, `controller`
- **User** has `ip_address`; if customer was logged in, also `id` and `email`
- **MySQL events** include an `SQL Query` context block with the full query
- **`dedup`** test should produce **exactly one** event in Sentry, not three
- **Suppressed types** — toggle the module's "Ignore notices", "Ignore warnings",
  etc., then re-run the matching `?type=` test. The event must NOT appear in Sentry.

### Back-office vs front-office monitoring

The module gates back-office capture behind the **"Monitor back office"** setting.
To test:
- Turn it OFF → hit a back-office URL that throws an exception → nothing in Sentry.
- Turn it ON → same URL → event in Sentry.

(The test controller above is a front-office controller; for back-office testing
you'd trigger an exception through any admin page, e.g. by visiting a bad URL.)

---

## Frontend tests

### Setup

1. Configure **Frontend DSN Key** in the module settings.
2. Open the storefront in a browser.
3. Open DevTools → Console.
4. Verify `Sentry` is defined: type `Sentry` and press Enter.
   If `undefined`, the SDK didn't load — check Network tab for `sentry.min.js`.
5. Paste any snippet below into the console.
6. Check Sentry → Issues.

### Snippets

#### Generic JS error

```js
throw new Error('FrSentry test :: generic Error');
```

#### TypeError

```js
null.someMethod();
```

#### ReferenceError

```js
nonExistentVariable.foo;
```

#### Synchronous error inside a function call

```js
(function () {
    function deep() { throw new Error('FrSentry test :: nested call stack'); }
    function mid()  { deep(); }
    function top()  { mid(); }
    top();
})();
```

#### Unhandled promise rejection

```js
new Promise((_, reject) => reject(new Error('FrSentry test :: unhandled rejection')));
```

#### Handled promise rejection (should NOT appear in Sentry by default)

```js
new Promise((_, reject) => reject(new Error('Should be ignored')))
    .catch(() => console.log('handled — Sentry should not capture this'));
```

#### Manual exception capture

```js
Sentry.captureException(new Error('FrSentry test :: manual captureException'));
```

#### Manual message capture

```js
Sentry.captureMessage('FrSentry test :: manual message', 'info');
```

#### Capture with extra tags and context

```js
Sentry.captureException(new Error('FrSentry test :: with tags'), {
    tags: { test_run: 'true', env: 'manual' },
    extra: { note: 'pasted from devtools console' },
});
```

#### Error from setTimeout (async)

```js
setTimeout(() => { throw new Error('FrSentry test :: async setTimeout'); }, 100);
```

#### Error from fetch failure (network)

```js
fetch('https://this-domain-does-not-exist-xyz-123.test')
    .then(r => r.text())
    .catch(e => { throw new Error('FrSentry test :: fetch failure -> ' + e.message); });
```

#### Filtering check — error from a third-party URL (should be SUPPRESSED)

The module's `beforeSend` drops events whose first stack frame is not from
the shop's own domain. To test it:

```js
// Simulate a third-party script error by injecting a remote script that throws
const s = document.createElement('script');
s.src = 'data:text/javascript,throw new Error("FrSentry test :: third party")';
document.head.appendChild(s);
```

This should NOT appear in Sentry (filtered by `beforeSend`).

### What to verify in Sentry

- **User** has `ip_address`; if logged in, also `id` and `email`
- Events from `chrome://`, `moz-extension://`, Google Analytics, Tag Manager,
  Facebook, DoubleClick are dropped (see `denyUrls` in `sentry_init.tpl`)
- The third-party-URL test above does NOT appear
- All other tests DO appear

---

## Quick cleanup checklist

When you finish testing:

- [ ] Delete `controllers/front/test.php`
- [ ] Clear browser console history (so test errors aren't replayed)
- [ ] (Optionally) delete the test events from your Sentry project
