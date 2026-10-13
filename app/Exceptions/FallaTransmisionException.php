<?php

namespace App\Exceptions;

use RuntimeException;

/** Caída del servicio del MH / sin conectividad → documento pasa a contingencia. */
class FallaTransmisionException extends RuntimeException
{
}