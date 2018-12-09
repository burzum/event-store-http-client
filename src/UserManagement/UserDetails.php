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

use DateTimeImmutable;
use Exception;
use Prooph\EventStoreHttpClient\Util\DateTime;

/** @internal */
final class UserDetails
{
    /** @var string */
    private $loginName;
    /** @var string */
    private $fullName;
    /** @var string[] */
    private $groups = [];
    /** @var DateTimeImmutable */
    private $dateLastUpdated;
    /** @var bool */
    private $disabled;
    /** @var RelLink[] */
    private $links = [];

    private function __construct()
    {
    }

    public static function fromArray(array $data): self
    {
        $details = new self();

        $details->loginName = $data['loginName'];
        $details->fullName = $data['fullName'];
        $details->groups = $data['groups'];
        $details->disabled = $data['disabled'];

        $details->dateLastUpdated = isset($data['dateLastUpdated'])
            ? DateTime::create($data['dateLastUpdated'])
            : null;

        $links = [];
        if (isset($data['links'])) {
            foreach ($data['links'] as $link) {
                $links[] = new RelLink($link['href'], $link['rel']);
            }
        }
        $details->links = $links;

        return $details;
    }

    public function loginName(): string
    {
        return $this->loginName;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    /** @return string[] */
    public function groups(): array
    {
        return $this->groups;
    }

    public function dateLastUpdated(): ?DateTimeImmutable
    {
        return $this->dateLastUpdated;
    }

    public function disabled(): bool
    {
        return $this->disabled;
    }

    /** @return RelLink[] */
    public function links(): array
    {
        return $this->links;
    }

    /** @throws Exception if rel not found */
    public function getRelLink(string $rel): string
    {
        $rel = \strtolower($rel);

        foreach ($this->links() as $link) {
            if (\strtolower($link->rel()) === $rel) {
                return $link->href();
            }
        }

        throw new Exception('rel not found');
    }
}
