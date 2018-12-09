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
class ChangePasswordDetails implements JsonSerializable
{
    /** @var string */
    private $currentPassword;
    /** @var string */
    private $newPassword;

    public function __construct(string $currentPassword, string $newPassword)
    {
        $this->currentPassword = $currentPassword;
        $this->newPassword = $newPassword;
    }

    public function jsonSerialize(): object
    {
        $object = new stdClass();
        $object->currentPassword = $this->currentPassword;
        $object->newPassword = $this->newPassword;

        return $object;
    }
}
