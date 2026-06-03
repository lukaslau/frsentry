@echo off
cd /d "%~dp0.."

echo Running PHP CS Fixer...
php do_not_include/php-cs-fixer.phar fix --config=do_not_include/.php-cs-fixer.dist.php
