<?php

namespace App\Exceptions;

use Exception;

/** A rule violation whose (already translated) message is safe to show to the user. */
class BusinessException extends Exception
{
}
