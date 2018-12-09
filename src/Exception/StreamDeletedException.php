<?php

/**
 * This file is part of `prooph/event-store-http-client`.
 * (c) 2018-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2018-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStoreHttpClient\Exception;

class StreamDeletedException extends RuntimeException
{
    public static function with(string $stream): StreamDeletedException
    {
        return new self(\sprintf(
            'Stream \'%s\' is deleted',
            $stream
        ));
    }
}
