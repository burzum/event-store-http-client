<?php

/**
 * This file is part of `prooph/event-store-http-client`.
 * (c) 2018-2019 prooph software GmbH <contact@prooph.de>
 * (c) 2018-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStoreHttpClient;

use Prooph\EventStore\Exception\InvalidArgumentException;
use Prooph\EventStore\UserCredentials;

class Uri
{
    /**
     * Sub-delimiters used in user info, query strings and fragments.
     * @const string
     */
    private const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    /**
     * Unreserved characters used in user info, paths, query strings, and fragments.
     * @const string
     */
    private const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~\pL';

    private const TCP_PORT_DEFAULT = 1113;

    /** @var string */
    private $scheme;
    /** @var UserCredentials|null */
    private $userCredentials;
    /** @var string */
    private $host;
    /** @var int */
    private $port;

    public function __construct(
        string $scheme,
        string $host,
        int $port,
        ?UserCredentials $userCredentials = null
    ) {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->userCredentials = $userCredentials;
    }

    public static function fromString(string $uri): self
    {
        $parts = \parse_url($uri);

        if (false === $parts) {
            throw new InvalidArgumentException(
                'The source URI string appears to be malformed'
            );
        }

        $scheme = isset($parts['scheme']) ? self::filterScheme($parts['scheme']) : '';
        $host = isset($parts['host']) ? \strtolower($parts['host']) : '';
        $port = isset($parts['port']) ? (int) $parts['port'] : self::TCP_PORT_DEFAULT;
        $userCredentials = null;

        if (isset($parts['user'])) {
            $user = self::filterUserInfoPart($parts['user']);
            $pass = $parts['pass'] ?? '';

            $userCredentials = new UserCredentials($user, $pass);
        }

        return new self($scheme, $host, $port, $userCredentials);
    }

    public function scheme(): string
    {
        return $this->scheme;
    }

    public function userCredentials(): ?UserCredentials
    {
        return $this->userCredentials;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    private static function filterScheme(string $scheme): string
    {
        return \preg_replace('#:(//)?$#', '', \strtolower($scheme));
    }

    private static function filterUserInfoPart(string $part): string
    {
        // Note the addition of `%` to initial charset; this allows `|` portion
        // to match and thus prevent double-encoding.
        return \preg_replace_callback(
            '/(?:[^%' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ']+|%(?![A-Fa-f0-9]{2}))/u',
            function (array $matches): string {
                return \rawurlencode($matches[0]);
            },
            $part
        );
    }
}
