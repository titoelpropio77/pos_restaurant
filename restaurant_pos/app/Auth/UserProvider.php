<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;

/**
 * Autenticar es lo que determina el restaurante, así que no puede depender de tenerlo:
 * el usuario se busca sin el scope de empresa. El login es único en todo el sistema.
 */
class UserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null)
    {
        return parent::newModelQuery($model)->withoutGlobalScopes();
    }
}
