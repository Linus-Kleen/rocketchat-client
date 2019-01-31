<?php
/**
 * @author    Oliver Schieche <github+rc-lclient@spam.oliver-schieche.de>
 * @copyright 2019
 */
//------------------------------------------------------------------------------
namespace LinusKleen\Lib\RocketChat;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use LinusKleen\Lib\RocketChat\Authentication\Storage\Devnull;
use LinusKleen\Lib\RocketChat\Contracts\AuthenticationStorageInterface;
use LinusKleen\Lib\RocketChat\Exception\CommunicationException;
use LinusKleen\Lib\RocketChat\Exception\Exception;
use LinusKleen\Lib\RocketChat\Exception\RocketChatException;
use LinusKleen\Lib\RocketChat\Exception\RuntimeException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Client
 *
 * @package LinusKleen\Lib\RocketChat
 */
class Client
{
    /** @var string */
    const SUCCESS_MESSAGE = 'success';

    /** @var AuthenticationStorageInterface */
    protected $authStorage;
    /** @var array */
    protected $authStorageConfig;
    /** @var \stdClass */
    protected $currentAuth;
    /** @var string|null */
    protected $hostName;
    /** @var boolean */
    protected $secure;
    /** @var boolean */
    protected $sslVerify;
    /** @var int */
    protected $timeoutConnect;
    /** @var int */
    protected $timeoutTransfer;
    /** @var string */
    protected $url;

    /**
     * Client constructor.
     *
     * @param string|null $hostName Hostname of RocketChat server
     */
    public function __construct(string $hostName = null)
    {
        $this->setHostName($hostName)
            ->setSecure(true)
            ->setSslVerify(true)
            ->setTimeoutConnect(15)
            ->setTimeoutTransfer(60);
    }

    /**
     * @param string $class
     * @param array $ctorArguments
     * @return Client
     * @throws RuntimeException
     */
    public function setAuthenticationStorage(string $class, array $ctorArguments = []): self
    {
        if (null !== $this->authStorage) {
            throw new RuntimeException('Cannot alter authentication storage after it had been instantiated');
        }

        $this->authStorageConfig = ['class' => $class, 'ctorArguments' => $ctorArguments];

        return $this;
    }

    /**
     * @return string|null
     */
    public function getHostName()
    {
        return $this->hostName;
    }

    /**
     * @param string|null $hostName
     * @return Client
     */
    public function setHostName(string $hostName = null): Client
    {
        $this->hostName = $hostName;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @param bool $secure
     * @return Client
     */
    public function setSecure(bool $secure): Client
    {
        $this->secure = $secure;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSslVerify(): bool
    {
        return $this->sslVerify;
    }

    /**
     * @param bool $sslVerify
     * @return Client
     */
    public function setSslVerify(bool $sslVerify): Client
    {
        $this->sslVerify = $sslVerify;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeoutConnect(): int
    {
        return $this->timeoutConnect;
    }

    /**
     * @param int $timeoutConnect
     * @return Client
     */
    public function setTimeoutConnect(int $timeoutConnect): Client
    {
        $this->timeoutConnect = $timeoutConnect;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeoutTransfer(): int
    {
        return $this->timeoutTransfer;
    }

    /**
     * @param int $timeoutTransfer
     * @return Client
     */
    public function setTimeoutTransfer(int $timeoutTransfer): Client
    {
        $this->timeoutTransfer = $timeoutTransfer;
        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     * @param bool $persist Whether to store authentication
     * @return Client
     * @throws CommunicationException
     * @throws Exception
     * @throws RuntimeException
     */
    public function login(string $username, string $password, bool $persist = false): self
    {
        $digest = $this->getAuthenticationDigest($username, $password);

        if ($persist && $this->hasAuthentication($digest)) {
            return $this->storeAuthentication($this->getAuthentication($digest));
        }

        $response = $this->post('login', [
            'user' => $username,
            'password' => $password
        ]);

        if (null === ($message = $this->extractJsonPayload($response))) {
            throw new RuntimeException('Login call returned non-json payload: %s', $response->getBody());
        }

        if (static::SUCCESS_MESSAGE !== $message->status) {
            throw new RuntimeException('Unexpected message status "%s"', $message->status);
        }

        return $this->storeAuthentication((object) [
            'userId' => $message->data->userId,
            'authToken' => $message->data->authToken
        ], $persist ? $digest : null);
    }

    /**
     * @param string $apiCall
     * @param array $parameters
     * @param array $headers
     * @return ResponseInterface
     * @throws CommunicationException
     * @throws Exception
     */
    public function post(string $apiCall, array $parameters = [], array $headers = []): ResponseInterface
    {
        return $this->sendRequest('POST', $apiCall, [
            'json' => $parameters,
            'headers' => $headers
        ]);
    }

    /**
     * @return HttpClient
     */
    protected function createClient(): HttpClient
    {
        return new HttpClient([
            'base_uri' => $this->getUrl(),
            'connect_timeout' => $this->getTimeoutConnect(),
            'timeout' => $this->getTimeoutTransfer(),
            'headers' => $this->getDefaultHeaders(),
            'verify' => $this->isSslVerify()
        ]);
    }

    /**
     * @return AuthenticationStorageInterface
     */
    protected function getAuthenticationStorage(): AuthenticationStorageInterface
    {
        if (null === $this->authStorage) {
            $this->authStorage = $this->createAuthenticationStorage();
        }

        return $this->authStorage;
    }

    /**
     * @return array
     */
    protected function getDefaultHeaders(): array
    {
        $headers = ['User-Agent' => \sprintf('LK/RocketChat-%s', Version::VERSION)];

        if (null !== $this->currentAuth) {
            $headers += [
                'X-Auth-Token' => $this->currentAuth->authToken,
                'X-User-Id' => $this->currentAuth->userId
            ];
        }

        return $headers;
    }
    /**
     * @return string
     */
    public function getUrl(): string
    {
        if (null === $this->url) {
            $this->url = \sprintf('http%s://%s/api/v1/', $this->isSecure() ? 's' : '', $this->getHostName());
        }

        return $this->url;
    }

    /**
     * @param ResponseInterface $response
     * @return \stdClass|null
     */
    protected function extractJsonPayload(ResponseInterface $response)
    {
        list($contentType) = $response->getHeader('Content-Type');

        if (0 === \strpos($contentType, 'application/json')) {
            $message = \json_decode($response->getBody());

            if (false !== $message) {
                return $message;
            }
        }

        return null;
    }

    /**
     * @param string $method
     * @param string $apiCall
     * @param array $parameters
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws CommunicationException
     * @throws Exception
     */
    protected function sendRequest(string $method, string $apiCall, array $parameters)
    {
        try {
            $httpClient = $this->createClient();
            /** @noinspection PhpUnhandledExceptionInspection */
            return $httpClient->request($method, $apiCall, $parameters);
        } catch(ClientException $exception) {
            throw $this->convertClientException($exception);
        } catch(GuzzleException $exception) {
            throw new CommunicationException('Failed to send request: ' . $exception->getMessage(), 1548940810, $exception);
        }
    }

    /**
     * @param GuzzleException $exception
     * @return Exception
     */
    protected function convertClientException($exception): Exception
    {
        if ($exception instanceof ClientException) {
            $response = $exception->getResponse();

            if (null !== $response && null !== ($message = $this->extractJsonPayload($response))) {
                return new RocketChatException($message->message, $message->error);
            }
        }

        return new CommunicationException('Client error while sending request: ' . $exception->getMessage(), 1548941070, $exception);
    }

    /**
     * @param string $digest
     * @return bool
     */
    protected function hasAuthentication(string $digest): bool
    {
        return $this->getAuthenticationStorage()->has($digest);
    }

    /**
     * @param string $digest
     * @return \stdClass
     */
    protected function getAuthentication(string $digest): \stdClass
    {
        return $this->getAuthenticationStorage()->get($digest);
    }

    /**
     * @param $data
     * @param string|null $digest
     * @return Client
     */
    protected function storeAuthentication($data, string $digest = null): self
    {
        if (null !== $digest) {
            $this->getAuthenticationStorage()->store($digest, $data);
        }

        $this->currentAuth = $data;
        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     * @return string
     */
    protected function getAuthenticationDigest(string $username, string $password): string
    {
        return \sha1("$username:$password");
    }

    /**
     * @return AuthenticationStorageInterface
     */
    private function createAuthenticationStorage(): AuthenticationStorageInterface
    {
        if (null === $this->authStorageConfig) {
            return new Devnull();
        }

        $class = $this->authStorageConfig['class'];
        $ctorArguments = $this->authStorageConfig['ctorArguments'];

        return new $class(...$ctorArguments);
    }
}
