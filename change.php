<?php

require_once('MyException.php');
require_once('CurlClient.php');
require_once('DnsRecord.php');
require_once('ApiWorker.php');


$config = json_decode(file_get_contents('config.json'), true);

$worker = new ApiWorker($config);

$worker->updateDnsRecord();



function consoleLog(string ... $pieces)
{
    $timestamp = getTimestamp();
    array_unshift($pieces, $timestamp);

    echo implode(' - ', $pieces) . "\n";
}

function getTimestamp()
{
    return '[' . date("Y-m-d H:i:s") . ']';
}