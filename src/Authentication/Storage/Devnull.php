<?php
/**
 * @author    Oliver Schieche <github+rc-client@spam.oliver-schieche.de>
 * @copyright 2019
 */
namespace LinusKleen\Lib\RocketChat\Authentication\Storage;

use LinusKleen\Lib\RocketChat\Contracts\AuthenticationStorageInterface;

/**
 * Class Devnull
 * @package LinusKleen\Lib\RocketChat\Authentication\Storage
 */
class Devnull implements AuthenticationStorageInterface
{
    /**
     * @param string $authenticationDigest
     * @return bool
     */
    public function has(string $authenticationDigest): bool
    {
        return false;
    }

    /**
     * @param string $authenticationDigest
     * @return \stdClass
     */
    public function get(string $authenticationDigest): \stdClass
    {
        return (object) [];
    }

    /**
     * @param string $authenticationDigest
     * @param \stdClass $data
     * @return void
     */
    public function store(string $authenticationDigest, \stdClass $data)
    {
    }
}
