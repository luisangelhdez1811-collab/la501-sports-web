<?php

namespace App\Exceptions;

use RuntimeException;

/** Una línea de pedido que no se puede armar; el mensaje se le muestra tal cual al usuario. */
class InvalidOrderLine extends RuntimeException
{
}
