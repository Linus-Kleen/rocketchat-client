#!/usr/bin/env php
<?php
/**
 * @author    Oliver Schieche <github+rc-client@spam.oliver-schieche.de>
 * @copyright 2019
 */
//------------------------------------------------------------------------------
namespace LinusKleen\Lib\RocketChat\Examples;

use LinusKleen\Lib\RocketChat\Authentication\Storage\File;
use LinusKleen\Lib\RocketChat\Client;

require dirname(__DIR__) . '/vendor/autoload.php';
// This file should define the macros LOGIN_HOST, LOGIN_USER and LOGIN_PASS
require __DIR__ . '/.credentials.php';

try {
    $client = new Client(\LOGIN_HOST);
    $auth = $client->setAuthenticationStorage(File::class, ['./rc.auth'])
        ->login(\LOGIN_USER, \LOGIN_PASS, true);
    \var_dump($auth);
} catch (\Exception $exception) {
    \fprintf(\STDERR, "Caught %s exception from %s, line %d: %s\n\n%s\n",
        \get_class($exception), $exception->getFile(), $exception->getLine(),
        $exception->getMessage(), $exception->getTraceAsString());
}
