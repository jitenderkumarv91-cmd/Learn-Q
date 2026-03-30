@echo off
set PHP_EXE=C:\xampp\php\php.exe
set PHP_INI=%~dp0php.ini

if not exist "%PHP_EXE%" (
  echo php.exe not found at %PHP_EXE%
  pause
  exit /b 1
)

if not exist "%PHP_INI%" (
  echo php.ini not found at %PHP_INI%
  pause
  exit /b 1
)

echo Starting ScholarGrid on http://localhost:8000
"%PHP_EXE%" -c "%PHP_INI%" -S localhost:8000 -t "%~dp0"
