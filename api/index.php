<?php

// Re-dirigir directorios de caché y compilación a /tmp ya que Vercel es de solo lectura (Serverless)
putenv('APP_CONFIG_CACHE=/tmp/config.php');
putenv('APP_EVENTS_CACHE=/tmp/events.php');
putenv('APP_PACKAGES_CACHE=/tmp/packages.php');
putenv('APP_ROUTES_CACHE=/tmp/routes.php');
putenv('APP_SERVICES_CACHE=/tmp/services.php');
putenv('VIEW_COMPILED_PATH=/tmp');

// Ajustar logs y sesiones para funcionar en Serverless
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=cookie');
putenv('LOG_CHANNEL=stderr');

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

// Cambiar la ruta de almacenamiento al directorio temporal de Vercel
$app->useStoragePath('/tmp');

// Vercel /tmp está vacío, Laravel necesita estas carpetas para no lanzar error 500
$storageDirs = [
    '/tmp/app',
    '/tmp/framework/views',
    '/tmp/framework/cache/data',
    '/tmp/framework/sessions',
    '/tmp/logs'
];
foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

$app->handleRequest(Illuminate\Http\Request::capture());
