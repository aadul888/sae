<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rute dipecah per domain. File ini hanya titik masuk.
require __DIR__ . '/installer-public.php';
require __DIR__ . '/dashboard-core.php';
require __DIR__ . '/misc.php';
