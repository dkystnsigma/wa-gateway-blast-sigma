<?php

require_once 'vendor/autoload.php';

use App\Models\Device;

// Set up Laravel app context
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Device statuses in database:\n";
$devices = Device::select('name', 'status')->get();

foreach ($devices as $device) {
    echo "Name: {$device->name} => Status: '{$device->status}'\n";
}

echo "\nTotal devices: " . $devices->count() . "\n";
