<?php
/**
 * @author    Oliver Schieche <github+rc-lclient@spam.oliver-schieche.de>
 * @copyright 2019
 */
namespace LinusKleen\Lib\RocketChat\Exception;

use Throwable;

/**
 * Class RocketChatException
 * @package LinusKleen\Lib\RocketChat\Exception
 */
class RocketChatException extends Exception
{
    /** @var string */
    protected $errorType;
    /** @var string */
    protected $errorMessage;

    /**
     * RocketChatException constructor.
     * @param string $errorType
     * @param string $errorMessage
     * @param Throwable|null $previous
     */
    public function __construct(string $errorType, string $errorMessage, Throwable $previous = null)
    {
        $this->errorMessage = $errorMessage;
        $this->errorType = $errorType;

        parent::__construct($errorMessage, 0, $previous);
    }

    /**
     * @return string
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
