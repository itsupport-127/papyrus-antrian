<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$data = [
    'counter' => null,
    'counterMessage' => 'Test message',
];

try {
    $view = view('operator.dashboard', $data);
    echo $view->render();
    echo "View rendered successfully with minimal data\n";
} catch (Throwable $e) {
    echo "Error with minimal data: " . $e->getMessage() . "\n";
}
