<?php

/**
 * This file is part of `prooph/event-store-http-client`.
 * (c) 2018-2020 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2018-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStoreHttpClient\Exception;

use Prooph\EventStore\Exception\UserCommandConflict as BaseException;

class UserCommandConflictException extends BaseException
{
    private int $httpStatusCode;

    public function __construct(int $httpStatusCode, string $message)
    {
        $this->httpStatusCode = $httpStatusCode;

        parent::__construct($message);
    }

    public function httpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}
