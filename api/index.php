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

// Cargar el punto de entrada principal de Laravel
require __DIR__ . '/../public/index.php';
