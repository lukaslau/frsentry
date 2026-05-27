@echo off
cd /d "%~dp0.."

echo Running PHP CS Fixer...
php vendor/bin/php-cs-fixer fix --config=do_not_include/.php-cs-fixer.dist.php --allow-risky=yes
