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

namespace ProophTest\EventStoreHttpClient;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\EventReadResult;
use Prooph\EventStore\EventReadStatus;
use Throwable;

class read_event_of_linkto_to_deleted_event extends TestCase
{
    use SpecificationWithLinkToToDeletedEvents;

    /** @var EventReadResult */
    private $read;

    protected function when(): void
    {
        $this->read = $this->conn->readEvent(
            $this->linkedStreamName,
            0,
            true
        );
    }

    /**
     * @test
     * @throws Throwable
     */
    public function the_linked_event_is_returned(): void
    {
        $this->markTestIncomplete('not yet implemented');

        $this->execute(function () {
            $this->assertNotNull($this->read->event()->link());
        });
    }

    /**
     * @test
     * @throws Throwable
     */
    public function the_deleted_event_is_not_resolved(): void
    {
        $this->markTestIncomplete('not yet implemented');

        $this->execute(function () {
            $this->assertNull($this->read->event()->event());
        });
    }

    /**
     * @test
     * @throws Throwable
     */
    public function the_status_is_success(): void
    {
        $this->markTestIncomplete('not yet implemented');

        $this->execute(function () {
            $this->assertTrue(EventReadStatus::success()->equals($this->read->status()));
        });
    }
}
