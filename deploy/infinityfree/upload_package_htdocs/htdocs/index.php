<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// InfinityFree can serve this app directly from /htdocs, so the Laravel
// application and public assets both live in the same directory.
$appRoot = __DIR__;

if (file_exists($maintenance = $appRoot . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appRoot . '/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appRoot . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
