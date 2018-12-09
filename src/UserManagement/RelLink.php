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

class RelLink
{
    /** @var string */
    private $href;
    /** @var string */
    private $rel;

    public function __construct(string $href, string $rel)
    {
        $this->href = $href;
        $this->rel = $rel;
    }

    public function href(): string
    {
        return $this->href;
    }

    public function rel(): string
    {
        return $this->rel;
    }
}
