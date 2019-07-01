<?php

/**
 * This file is part of `prooph/event-store-http-client`.
 * (c) 2018-2019 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2018-2019 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStoreHttpClient\Internal;

/**
 * @internal
 * To work around https://github.com/EventStore/EventStore/issues/1903
 */
final class DateTimeStringBugWorkaround
{
    public static function fixDateTimeString(string $dateTimeString): string
    {
        $micros = \substr($dateTimeString, 20, -1);
        $length = \strlen($micros);

        if ($length < 6) {
            $micros .= \str_repeat('0', 6 - $length);
        } elseif ($length > 6) {
            $micros = \substr($micros, 0, 6);
        }

        return \substr($dateTimeString, 0, 20) . $micros . 'Z';
    }
}
