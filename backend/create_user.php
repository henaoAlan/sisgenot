<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Crear o actualizar usuario admin
$user = User::updateOrCreate(
    ['email' => 'admin@sisgenot.edu'],
    [
        'full_name' => 'Admin',
        'password' => Hash::make('Admin@123'),
        'role' => 'admin',
        'is_active' => true,
    ]
);

echo "Usuario creado/actualizado: " . $user->email . " - Contraseña: Admin@123\n";
