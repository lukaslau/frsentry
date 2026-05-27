@echo off
cd /d "%~dp0.."
set ROOT=%CD%
set MODULE_NAME=frsentry
set RELEASE_DIR=%ROOT%\release
set OUT_ZIP=%ROOT%\%MODULE_NAME%.zip
set CREATE_ZIP=0

if /i "%1"=="/zip" set CREATE_ZIP=1

echo [1/4] Running PHP CS Fixer...
php vendor/bin/php-cs-fixer fix --config=do_not_include/.php-cs-fixer.dist.php --allow-risky=yes
if %ERRORLEVEL% neq 0 (
    echo ERROR: PHP CS Fixer failed.
    exit /b 1
)

echo [2/4] Installing production dependencies...
composer install --no-dev --optimize-autoloader
if %ERRORLEVEL% neq 0 (
    echo ERROR: composer install failed.
    exit /b 1
)

echo [3/4] Copying module files...
if exist "%RELEASE_DIR%" rmdir /s /q "%RELEASE_DIR%"
mkdir "%RELEASE_DIR%\%MODULE_NAME%"
powershell -NoProfile -Command " ^
    $src = '%ROOT%'; ^
    $dst = '%RELEASE_DIR%\%MODULE_NAME%'; ^
    $exclude = @('.git','.claude','do_not_include','node_modules','release'); ^
    $excludeFiles = @('*.bat','*.zip','.gitignore','.gitattributes','.php_cs','.php-cs-fixer.cache','composer.lock'); ^
    Get-ChildItem -Path $src -Force | Where-Object { ^
        $_.Name -notin $exclude -and ^
        -not ($excludeFiles | Where-Object { $_.Name -like $_ }) ^
    } | ForEach-Object { ^
        Copy-Item -Path $_.FullName -Destination $dst -Recurse -Force ^
    } ^
"
if %ERRORLEVEL% neq 0 (
    echo ERROR: File copy failed.
    exit /b 1
)

if "%CREATE_ZIP%"=="1" (
    echo [4/4] Creating zip...
    if exist "%OUT_ZIP%" del /f /q "%OUT_ZIP%"
    powershell -NoProfile -Command "Compress-Archive -Path '%RELEASE_DIR%\%MODULE_NAME%' -DestinationPath '%OUT_ZIP%' -Force"
    if %ERRORLEVEL% neq 0 (
        echo ERROR: Zip creation failed.
        exit /b 1
    )
    echo Release ready: %OUT_ZIP%
) else (
    echo [4/4] Skipping zip. Release folder: %RELEASE_DIR%\%MODULE_NAME%
)

echo.
echo Restoring dev dependencies...
composer install
