<?php

namespace Mortezamasumi\FbCopydb\Exceptions;

use Exception;

class InvalidDatabaseException extends Exception
{
    public $message = 'Destination database can not create or driver unsupported';
}
