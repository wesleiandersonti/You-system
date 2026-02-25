@echo off
setlocal

set "PHP_EXE=C:\php\php.exe"
set "APP_DIR=C:\Users\Win10-Escritorio\.openclaw\workspace\you-system"
set "HOST=127.0.0.1"
set "PORT=8090"

if not exist "%PHP_EXE%" (
  echo [ERRO] PHP nao encontrado em %PHP_EXE%
  echo Ajuste o caminho no arquivo run-you-system.bat
  pause
  exit /b 1
)

cd /d "%APP_DIR%"

echo ==========================================
echo Iniciando You-system em http://%HOST%:%PORT%
echo Health: http://%HOST%:%PORT%/?health=1
echo ==========================================

"%PHP_EXE%" -S %HOST%:%PORT% -t public

endlocal
