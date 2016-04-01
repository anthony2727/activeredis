<?php
namespace ActiveRedis\Exceptions;

use \Exception;

class ScoreTypeNotValidException extends Exception
{
    public function __construct($info, $code = null)
    {
        throw new Exception($info, $code);
    }
}
