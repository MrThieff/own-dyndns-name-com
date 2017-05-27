<?php

/**
 * Created by PhpStorm.
 * User: MrThief
 * Date: 23.05.2017
 * Time: 23:54
 */
class MyException extends Exception
{
    public function __construct(string ... $message)
    {
        $message = $this->formatMsg($message);
        parent::__construct($message, $code = 666, $previous = null);
    }

    function formatMsg(array $lines)
    {
        $newString = "\n    ";
        return $newString . implode($newString, $lines) . "\n";
    }
}