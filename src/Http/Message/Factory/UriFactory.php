<?php
declare(strict_types=1);
namespace Karthus\Http\Message\Factory;

use Karthus\Http\Message\Uri\Uri;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class UriFactory
 *
 * @package Karthus\Http\Message\Factory
 */
class UriFactory implements UriFactoryInterface {
    /**
     * Create a new URI.
     *
     * @param string $uri
     *
     * @return UriInterface
     *
     * @throws \InvalidArgumentException If the given URI cannot be parsed.
     */
    public function createUri(string $uri = ''): UriInterface {
        return new Uri($uri);
    }
}
