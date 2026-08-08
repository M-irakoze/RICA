<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$request = Illuminate\Http\Request::create('/attendance/view/1785531711_1785525917_report.xlsx', 'GET');
$route = $app->make('router')->getRoutes()->match($request);
echo $route->uri() . ' -> ' . $route->getName() . "\n";
