<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Las pruebas recrean el esquema (RefreshDatabase): se verifica antes de aplicar los traits
     * que nunca apunten a la base de datos de trabajo, que comparten las dos aplicaciones.
     */
    protected function setUpTraits(): array
    {
        $base = (string) config('database.connections.'.config('database.default').'.database');

        if (! str_ends_with($base, '_test')) {
            throw new RuntimeException("Las pruebas deben usar una base *_test; se intentó usar \"{$base}\".");
        }

        return parent::setUpTraits();
    }
}
