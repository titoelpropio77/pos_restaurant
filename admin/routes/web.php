<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sistema administrador
|--------------------------------------------------------------------------
| Cada acción exige su propio permiso; superadmin los pasa todos por Gate::before.
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Restaurantes
    Route::get('restaurants', [RestaurantController::class, 'index'])->middleware('permission:restaurants.view')->name('restaurants.index');
    Route::get('restaurants/create', [RestaurantController::class, 'create'])->middleware('permission:restaurants.create')->name('restaurants.create');
    Route::post('restaurants', [RestaurantController::class, 'store'])->middleware('permission:restaurants.create')->name('restaurants.store');
    Route::get('restaurants/{restaurant}', [RestaurantController::class, 'show'])->middleware('permission:restaurants.view')->name('restaurants.show');
    Route::get('restaurants/{restaurant}/edit', [RestaurantController::class, 'edit'])->middleware('permission:restaurants.update')->name('restaurants.edit');
    Route::put('restaurants/{restaurant}', [RestaurantController::class, 'update'])->middleware('permission:restaurants.update')->name('restaurants.update');
    Route::post('restaurants/{restaurant}/status', [RestaurantController::class, 'status'])->middleware('permission:restaurants.deactivate')->name('restaurants.status');

    // Sucursales
    Route::get('branches', [BranchController::class, 'index'])->middleware('permission:branches.view')->name('branches.index');
    Route::get('branches/create', [BranchController::class, 'create'])->middleware('permission:branches.create')->name('branches.create');
    Route::post('branches', [BranchController::class, 'store'])->middleware('permission:branches.create')->name('branches.store');
    Route::get('branches/{branch}/edit', [BranchController::class, 'edit'])->middleware('permission:branches.update')->name('branches.edit');
    Route::put('branches/{branch}', [BranchController::class, 'update'])->middleware('permission:branches.update')->name('branches.update');
    Route::post('branches/{branch}/main', [BranchController::class, 'main'])->middleware('permission:branches.set_main')->name('branches.main');
    Route::post('branches/{branch}/status', [BranchController::class, 'status'])->middleware('permission:branches.deactivate')->name('branches.status');

    // Usuarios de los restaurantes
    Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
    Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
    Route::post('users/{user}/status', [UserController::class, 'status'])->middleware('permission:users.deactivate')->name('users.status');

    // Cuentas del sistema
    Route::get('admins', [AdminController::class, 'index'])->middleware('permission:admins.view')->name('admins.index');
    Route::get('admins/create', [AdminController::class, 'create'])->middleware('permission:admins.create')->name('admins.create');
    Route::post('admins', [AdminController::class, 'store'])->middleware('permission:admins.create')->name('admins.store');
    Route::get('admins/{admin}/edit', [AdminController::class, 'edit'])->middleware('permission:admins.update')->name('admins.edit');
    Route::put('admins/{admin}', [AdminController::class, 'update'])->middleware('permission:admins.update')->name('admins.update');
    Route::delete('admins/{admin}', [AdminController::class, 'destroy'])->middleware('permission:admins.deactivate')->name('admins.destroy');

    // Roles
    Route::get('roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
    Route::post('roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.update')->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.update')->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');

    // Permisos
    Route::get('permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.view')->name('permissions.index');
    Route::get('permissions/create', [PermissionController::class, 'create'])->middleware('permission:permissions.create')->name('permissions.create');
    Route::post('permissions', [PermissionController::class, 'store'])->middleware('permission:permissions.create')->name('permissions.store');
    Route::get('permissions/{permission}/edit', [PermissionController::class, 'edit'])->middleware('permission:permissions.update')->name('permissions.edit');
    Route::put('permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:permissions.update')->name('permissions.update');
    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:permissions.delete')->name('permissions.destroy');

    Route::get('audit_logs', AuditController::class)->middleware('permission:audit.view')->name('audit.index');

    // Catálogos comunes a todos los restaurantes: CatalogController exige catalogs.{catalogo}.{accion}
    Route::get('catalogs/{catalog}', [CatalogController::class, 'index'])->name('catalogs.index');
    Route::get('catalogs/{catalog}/create', [CatalogController::class, 'create'])->name('catalogs.create');
    Route::post('catalogs/{catalog}', [CatalogController::class, 'store'])->name('catalogs.store');
    Route::get('catalogs/{catalog}/{id}/edit', [CatalogController::class, 'edit'])->name('catalogs.edit');
    Route::put('catalogs/{catalog}/{id}', [CatalogController::class, 'update'])->name('catalogs.update');
    Route::delete('catalogs/{catalog}/{id}', [CatalogController::class, 'destroy'])->name('catalogs.destroy');
});
