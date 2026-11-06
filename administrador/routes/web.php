<?php

use App\Http\Controllers\AdministradorController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\RestauranteController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sistema administrador
|--------------------------------------------------------------------------
| Las rutas de lectura exigen *.ver y las de escritura *.gestionar. Quien gestiona también ve:
| todo rol con permiso de gestión debe incluir el de lectura del mismo módulo.
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [SesionController::class, 'create'])->name('login');
    Route::post('login', [SesionController::class, 'store'])->middleware('throttle:10,1');
});

Route::post('logout', [SesionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'activa'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('perfil', [PerfilController::class, 'update'])->name('perfil.update');

    // Restaurantes
    Route::middleware('permiso:restaurantes.gestionar')->group(function () {
        Route::get('restaurantes/nuevo', [RestauranteController::class, 'create'])->name('restaurantes.create');
        Route::post('restaurantes', [RestauranteController::class, 'store'])->name('restaurantes.store');
        Route::get('restaurantes/{restaurante}/editar', [RestauranteController::class, 'edit'])->name('restaurantes.edit');
        Route::put('restaurantes/{restaurante}', [RestauranteController::class, 'update'])->name('restaurantes.update');
        Route::post('restaurantes/{restaurante}/estado', [RestauranteController::class, 'estado'])->name('restaurantes.estado');

        Route::get('sucursales/nueva', [SucursalController::class, 'create'])->name('sucursales.create');
        Route::post('sucursales', [SucursalController::class, 'store'])->name('sucursales.store');
        Route::get('sucursales/{sucursal}/editar', [SucursalController::class, 'edit'])->name('sucursales.edit');
        Route::put('sucursales/{sucursal}', [SucursalController::class, 'update'])->name('sucursales.update');
        Route::post('sucursales/{sucursal}/matriz', [SucursalController::class, 'matriz'])->name('sucursales.matriz');
        Route::post('sucursales/{sucursal}/estado', [SucursalController::class, 'estado'])->name('sucursales.estado');
    });
    Route::middleware('permiso:restaurantes.ver')->group(function () {
        Route::get('restaurantes', [RestauranteController::class, 'index'])->name('restaurantes.index');
        Route::get('restaurantes/{restaurante}', [RestauranteController::class, 'show'])->name('restaurantes.show');
        Route::get('sucursales', [SucursalController::class, 'index'])->name('sucursales.index');
    });

    // Usuarios de los restaurantes
    Route::middleware('permiso:usuarios.gestionar')->group(function () {
        Route::get('usuarios/nuevo', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
        Route::post('usuarios/{usuario}/estado', [UsuarioController::class, 'estado'])->name('usuarios.estado');
    });
    Route::get('usuarios', [UsuarioController::class, 'index'])->middleware('permiso:usuarios.ver')->name('usuarios.index');

    // Seguridad
    Route::resource('administradores', AdministradorController::class)->except('show')
        ->parameters(['administradores' => 'administrador'])->middleware('permiso:administradores.gestionar');
    Route::resource('roles', RolController::class)->except('show')
        ->parameters(['roles' => 'rol'])->middleware('permiso:roles.gestionar');
    Route::resource('permisos', PermisoController::class)->except('show')->middleware('permiso:roles.gestionar');
    Route::get('auditoria', AuditoriaController::class)->middleware('permiso:auditoria.ver')->name('auditoria.index');

    // Catálogos comunes a todos los restaurantes
    Route::middleware('permiso:catalogos.gestionar')->group(function () {
        Route::get('catalogos/{catalogo}', [CatalogoController::class, 'index'])->name('catalogos.index');
        Route::get('catalogos/{catalogo}/nuevo', [CatalogoController::class, 'create'])->name('catalogos.create');
        Route::post('catalogos/{catalogo}', [CatalogoController::class, 'store'])->name('catalogos.store');
        Route::get('catalogos/{catalogo}/{id}/editar', [CatalogoController::class, 'edit'])->name('catalogos.edit');
        Route::put('catalogos/{catalogo}/{id}', [CatalogoController::class, 'update'])->name('catalogos.update');
        Route::delete('catalogos/{catalogo}/{id}', [CatalogoController::class, 'destroy'])->name('catalogos.destroy');
    });
});
