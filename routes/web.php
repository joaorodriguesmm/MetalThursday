<?php

use App\Http\Controllers\MetalThursday\MetalThursdayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Inclui os ficheiros de rotas separados por funcionalidade.
 *
 * @since 1.0
 * @version 1.0
 */
require __DIR__ . '/auth.php';
require __DIR__ . '/metalthursday.php';
