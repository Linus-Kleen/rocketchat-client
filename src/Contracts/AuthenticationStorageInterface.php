<?php
/**
 * @author    Oliver Schieche <github+rc-lclient@spam.oliver-schieche.de>
 * @copyright 2019
 */
namespace LinusKleen\Lib\RocketChat\Contracts;

/**
 * Interface AuthenticationStorageInterface
 * @package LinusKleen\Lib\RocketChat
 */
interface AuthenticationStorageInterface
{
    /**
     * @param string $authenticationDigest
     * @return bool
     */
    public function has(string $authenticationDigest): bool;

    /**
     * @param string $authenticationDigest
     * @return \stdClass
     */
    public function get(string $authenticationDigest): \stdClass;

    /**
     * @param string $authenticationDigest
     * @param \stdClass $data
     * @return void
     */
    public function store(string $authenticationDigest, \stdClass $data);
}
