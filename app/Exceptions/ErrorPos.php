<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error de negocio del POS. Se muestra al cajero tal cual, dentro del contrato
 * {"error": "...", "result": ""} que espera el frontend.
 */
class ErrorPos extends RuntimeException {}
