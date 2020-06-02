<?php

namespace  Srustamov\FileManager\Exceptions;


class FileManagerException extends \RuntimeException
{
    public function __construct($message = '')
    {
        parent::__construct(
            $message,
            0,
            null
        );
    }
}