<?php

use App\Http\Controllers\PublicMenuController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Menú digital
|--------------------------------------------------------------------------
| Público y sin sesión: el restaurante se toma de la sucursal dueña del token.
*/

Route::get('menu/{token}', PublicMenuController::class)
    ->where('token', '[A-Za-z0-9]{8,16}')
    ->name('menu.show');
