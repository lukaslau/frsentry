@echo off
cd /d "%~dp0.."

echo [1/2] Running PHP-Scoper...
php do_not_include/php-scoper.phar add-prefix --config=do_not_include/scoper.inc.php --force
if %ERRORLEVEL% neq 0 (
    echo ERROR: PHP-Scoper failed.
    exit /b 1
)

echo [2/2] Updating Composer package...
composer update frento/frsentry-scoped
if %ERRORLEVEL% neq 0 (
    echo ERROR: composer update failed.
    exit /b 1
)

echo Done.
