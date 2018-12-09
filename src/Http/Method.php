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

namespace Prooph\EventStoreHttpClient\Http;

class Method
{
    public const Get = 'GET';
    public const Post = 'POST';
    public const Put = 'PUT';
    public const Delete = 'DELETE';
    public const Options = 'OPTIONS';
    public const Head = 'HEAD';
    public const Patch = 'PATCH';
}
