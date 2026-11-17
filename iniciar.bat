@echo off
chcp 65001 >nul 2>&1
title Amigo Bicho - Clinica Veterinaria

set "BASEDIR=%~dp0"
set "PORT=8080"
set "URL=http://127.0.0.1:%PORT%"

echo.
echo  ===========================================
echo   Amigo Bicho - Clinica Veterinaria
echo   Iniciando o sistema...
echo  ===========================================
echo.

:: Detecta o PHP instalado no sistema
set "PHP="

:: Tenta PHP no PATH primeiro
where php.exe >nul 2>&1
if %errorlevel%==0 (
    for /f "tokens=*" %%i in ('where php.exe') do set "PHP=%%i" & goto :php_found
)

:: Tenta caminho do WinGet PHP 8.4
set "WINGET_PHP=%LOCALAPPDATA%\Microsoft\WinGet\Packages"
for /d %%d in ("%WINGET_PHP%\PHP.PHP.8.4*") do (
    if exist "%%d\php.exe" (
        set "PHP=%%d\php.exe"
        goto :php_found
    )
)

:: Tenta PHP 8.3 do WinGet
for /d %%d in ("%WINGET_PHP%\PHP.PHP.8.3*") do (
    if exist "%%d\php.exe" (
        set "PHP=%%d\php.exe"
        goto :php_found
    )
)

:: Tenta runtime local (para instalador futuro)
if exist "%BASEDIR%runtime\php\php.exe" (
    set "PHP=%BASEDIR%runtime\php\php.exe"
    goto :php_found
)

echo [ERRO] PHP nao encontrado no sistema.
echo.
echo Instale o PHP com o comando:
echo   winget install PHP.PHP.8.4
echo.
echo Depois reinicie este programa.
pause
exit /b 1

:php_found
echo [OK] PHP encontrado: %PHP%
echo.

:: Verifica se o .env existe
if not exist "%BASEDIR%.env" (
    echo [INFO] Criando arquivo de configuracao...
    if exist "%BASEDIR%.env.example" (
        copy "%BASEDIR%.env.example" "%BASEDIR%.env" >nul
    )
    "%PHP%" "%BASEDIR%artisan" key:generate --force
    echo [OK] Configuracao criada.
)

:: Verifica se o banco de dados existe
if not exist "%BASEDIR%database\database.sqlite" (
    echo [INFO] Criando banco de dados...
    type nul > "%BASEDIR%database\database.sqlite"
    "%PHP%" "%BASEDIR%artisan" migrate --force
    "%PHP%" "%BASEDIR%artisan" db:seed --force
    echo [OK] Banco de dados criado.
)

:: Limpa cache
echo [INFO] Inicializando...
"%PHP%" "%BASEDIR%artisan" config:clear >nul 2>&1
"%PHP%" "%BASEDIR%artisan" cache:clear >nul 2>&1
"%PHP%" "%BASEDIR%artisan" view:clear >nul 2>&1
echo [OK] Pronto!
echo.
echo  ===========================================
echo   Sistema iniciado!
echo   Acesse: %URL%
echo   Login:  super@vet.com
echo   Senha:  password
echo  ===========================================
echo.
echo   NAO feche esta janela enquanto usar
echo   o sistema. Para encerrar, feche aqui.
echo.

:: Aguarda e abre o navegador
timeout /t 3 /nobreak >nul
start "" "%URL%"

:: Inicia o servidor PHP
"%PHP%" "%BASEDIR%artisan" serve --host=127.0.0.1 --port=%PORT%

pause
