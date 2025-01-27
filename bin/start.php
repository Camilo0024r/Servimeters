<?php
session_start();
require(dirname(__DIR__) . "/controller/automaticForm.php");

$config = AutomaticForm::getConfig();

// comandos
// 1. Entra a la carpeta del proyecto
// 2. Actualiza composer
// 3. Ejecuta el servidor
$cmd = 'cd ' . $config->FOLDER_SITE . ' & composer update & php ' . __DIR__ . '\server.php';

$check_bat = fopen("check.bat", "w");
$bat_Code = <<<BAT
@echo off

setlocal

REM Puerto a verificar
set "puerto={$config->WEBSOCKET}"

REM Comando a ejecutar si encuentra el puerto
set "comando={$cmd}"

REM Verificar el puerto
netstat -ano | findstr ":%puerto%" >nul

REM Si encuentra el puerto, ejecuta el comando
if %errorlevel% equ 0 (
    echo El puerto %puerto% esta activo, Pero puede que el proceso en segundo plano este inactivo, busca el cmd con el servidor y presiona la tecla "Enter".
) else (
    %comando%
)

endlocal
BAT;

fwrite($check_bat, $bat_Code);
fclose($check_bat);

executeCMD('start cmd.exe @cmd /k "curl parrot.live"');
executeCMD('start cmd.exe @cmd /k "' . __DIR__ . '\check.bat"');

function executeCMD($cmd)
{
    if (substr(php_uname(), 0, 7) == "Windows") pclose(popen("start /B {$cmd}", "r"));
    else exec("{$cmd} > /dev/null &");
}
