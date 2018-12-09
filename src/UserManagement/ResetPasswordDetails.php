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

namespace Prooph\EventStoreHttpClient\UserManagement;

use JsonSerializable;
use stdClass;

/** @internal */
class ResetPasswordDetails implements JsonSerializable
{
    /** @var string */
    private $newPassword;

    public function __construct(string $newPassword)
    {
        $this->newPassword = $newPassword;
    }

    public function jsonSerialize(): object
    {
        $object = new stdClass();
        $object->newPassword = $this->newPassword;

        return $object;
    }
}
