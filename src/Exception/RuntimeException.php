<?php
/**
 * @author    Oliver Schieche <github+rc-client@spam.oliver-schieche.de>
 * @copyright 2019
 */
namespace LinusKleen\Lib\RocketChat\Exception;

/**
 * Class RuntimeException
 *
 * Report occurrences of those as bugs.
 *
 * @package LinusKleen\Lib\RocketChat\Exception
 */
class RuntimeException extends Exception
{
    /**
     * RuntimeException constructor.
     * @param string $format
     * @param mixed ...$arguments
     */
    public function __construct(string $format, ...$arguments)
    {
        parent::__construct(\vsprintf($format, $arguments));
    }
}
