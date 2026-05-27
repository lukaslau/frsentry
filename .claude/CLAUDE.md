# frsentry — Project Memory

> **Full project documentation:** `do_not_include/README.md` — architecture, scripts, dependency pinning, Sentry SDK update procedure. Check it first when context is needed.

## Module overview
PrestaShop Sentry integration module. Monitors PHP errors, exceptions, DB failures, and frontend JS issues by forwarding them to Sentry.

- **Namespace:** `Frento\FrSentry\src\*`
- **Main file:** `frsentry.php`
- **Tested against:** PrestaShop 1.7.7+, PHP 7.2–8.x

---

## Known issues & lessons learned

### Composer sub-dependencies must be pinned for PHP 7.2 compatibility

`sentry/sentry` itself declares `^7.2|^8.0`, but its transitive dependencies resolve to versions that require PHP 8.1 or 8.2 if left unpinned:

| Package | Unpinned version | PHP required | Pinned to |
|---|---|---|---|
| `symfony/options-resolver` | v7.4.x | >=8.2 | `^5.4` |
| `symfony/deprecation-contracts` | v3.7.x | >=8.1 | `^2.5` |
| `psr/log` | v3.x | >=8.0 | `^1.0` |
| `jean85/pretty-package-versions` | v2.x | ^7.4\|^8.0 | `^1.6` |

When the wrong versions are resolved, Composer generates `vendor/composer/platform_check.php` with `PHP_VERSION_ID >= 80200`. PrestaShop loads the module main file during validation (`ModuleRepository::getModuleAttributes()`), which triggers `require_once vendor/autoload.php`, which hits `platform_check.php`, which calls `trigger_error(..., E_USER_ERROR)` — causing the module to be marked invalid and uninstallable.

**Fix already applied** in `composer.json`:
```json
"require": {
    "php": ">=7.2.0",
    "sentry/sentry": "^4.8",
    "symfony/options-resolver": "^5.4",
    "symfony/deprecation-contracts": "^2.5",
    "psr/log": "^1.0",
    "jean85/pretty-package-versions": "^1.6"
},
"config": {
    "prepend-autoloader": false,
    "platform-check": false
}
```

After any `composer update`, verify no package requires PHP 8+ exclusively:
```bash
python -c "
import json
with open('composer.lock') as f:
    lock = json.load(f)
for p in lock.get('packages', []):
    php = p.get('require', {}).get('php', '')
    if '8.1' in php or '8.2' in php:
        print(p['name'], p['version'], '->', php)
"
```

---

## Architecture notes

- `vendor/autoload.php` is loaded at the top of `frsentry.php` after `libs/sentry/autoload.php`. `sentry/sentry` is `require-dev` only — production `vendor/` never contains the unscoped SDK, so there is no `\Sentry\*` function conflict with ps_mbo.
- `FrSentry` (lib class) vs `FrSentry` (module class) — the lib lives in `src/Libs/FrSentry.php` under the `Frento\FrSentry\src\Libs` namespace; the module class is the root `frsentry.php`.
- Config is cached per-request in `FrConfiguration::$cache`. Call `FrConfiguration::clearCache()` after saving settings.
- Excimer tracing/profiling is gated on `extension_loaded('excimer')` at runtime; the admin UI always renders but the transaction is only started when the extension is present.
