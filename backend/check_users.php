<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Import the User model
use App\Models\User;

// Count total users
$totalUsers = User::count();
echo "Total de usuarios: " . $totalUsers . "\n\n";

if ($totalUsers > 0) {
    echo "Primeros 5 usuarios:\n";
    $users = User::limit(5)->get();
    foreach ($users as $user) {
        echo "- ID: {$user->id}, Nombre: {$user->name}, Email: {$user->email}, Rol: {$user->role}\n";
    }
} else {
    echo "No hay usuarios en la base de datos.\n";
}
