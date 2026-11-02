@echo off
REM Lance les tests PHPUnit avec le PHP et le php.ini de MAMP (extensions pdo_mysql, curl, mbstring).
REM Usage : lancer-tests.bat            -> affichage lisible dans la console
REM         lancer-tests.bat rapport    -> genere aussi le rapport dans docs\tests\
cd /d "%~dp0"
set PHP=C:\MAMP\bin\php\php8.3.1\php.exe
set INI=C:\MAMP\conf\php8.3.1\php.ini
if /I "%1"=="rapport" (
    "%PHP%" -c "%INI%" vendor\phpunit\phpunit\phpunit --testdox --testdox-html docs\tests\rapport-phpunit.html --testdox-text docs\tests\rapport-phpunit.txt --log-junit docs\tests\rapport-phpunit.xml
) else (
    "%PHP%" -c "%INI%" vendor\phpunit\phpunit\phpunit --testdox %*
)
