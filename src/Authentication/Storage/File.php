<?php
/**
 * @author    Oliver Schieche <github+rc-lclient@spam.oliver-schieche.de>
 * @copyright 2019
 */
namespace LinusKleen\Lib\RocketChat\Authentication\Storage;

use LinusKleen\Lib\RocketChat\Contracts\AuthenticationStorageInterface;
use LinusKleen\Lib\RocketChat\Exception\RuntimeException;

/**
 * Class File
 *
 * @package LinusKleen\Lib\RocketChat\Authentication\Storage
 */
class File implements AuthenticationStorageInterface
{
    /** @var \stdClass */
    protected $authStore;
    /** @var string */
    protected $filename;

    /**
     * @param string $authenticationDigest
     * @return bool
     * @throws RuntimeException
     */
    public function has(string $authenticationDigest): bool
    {
        if (null === $this->authStore) {
            $this->authStore = $this->getAuthStore();
        }

        return \property_exists($this->authStore, $authenticationDigest);
    }

    /**
     * @param string $authenticationDigest
     * @return \stdClass
     * @throws RuntimeException
     */
    public function get(string $authenticationDigest): \stdClass
    {
        if (!$this->has($authenticationDigest)) {
            throw new RuntimeException("No such stored authentication '$authenticationDigest'");
        }

        return $this->authStore->{$authenticationDigest};
    }

    /**
     * @param string $authenticationDigest
     * @param \stdClass $data
     * @return void
     * @throws RuntimeException
     */
    public function store(string $authenticationDigest, \stdClass $data)
    {
        $auth = $this->getAuthStore();
        $auth->{$authenticationDigest} = $data;
        $this->authStore = $auth;
        $this->dumpAuthStore();
    }

    /**
     * File constructor.
     *
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->authStore = null;
        $this->filename = $file;
    }

    /**
     * @throws RuntimeException
     */
    private function dumpAuthStore()
    {
        if (false === ($fp = \fopen($this->filename, 'wb'))) {
            throw new RuntimeException('Failed to open %s for writing', $this->filename);
        }
        \fwrite($fp, \json_encode($this->authStore, \JSON_PRETTY_PRINT));
        \fclose($fp);
    }

    /**
     * @return \stdClass
     * @throws RuntimeException
     */
    private function getAuthStore(): \stdClass
    {
        if (!\file_exists($this->filename)) {
            return (object) [];
        }

        if (false === ($fp = \fopen($this->filename, 'rb'))) {
            throw new RuntimeException('Failed to open %s for reading', $this->filename);
        }
        $auth = \stream_get_contents($fp);
        \fclose($fp);

        if (false === ($auth = \json_decode($auth))) {
            throw new RuntimeException('Failed to decode contents of %s', $this->filename);
        }

        return $auth;
    }
}
