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

namespace Prooph\EventStoreHttpClient;

class EventReadResult
{
    /** @var EventReadStatus */
    private $status;

    /** @var string */
    private $stream;

    /** @var int */
    private $eventNumber;

    /** @var ResolvedEvent|null */
    private $event;

    /** @internal */
    public function __construct(EventReadStatus $status, string $stream, int $eventNumber, ?ResolvedEvent $event)
    {
        $this->status = $status;
        $this->stream = $stream;
        $this->eventNumber = $eventNumber;
        $this->event = $event;
    }

    public function status(): EventReadStatus
    {
        return $this->status;
    }

    public function stream(): string
    {
        return $this->stream;
    }

    public function eventNumber(): int
    {
        return $this->eventNumber;
    }

    public function event(): ?ResolvedEvent
    {
        return $this->event;
    }
}
