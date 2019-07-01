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

namespace ProophTest\EventStoreHttpClient;

use PHPUnit\Framework\TestCase;
use Prooph\EventStore\Common\SystemEventTypes;
use Prooph\EventStore\Common\SystemRoles;
use Prooph\EventStore\EventData;
use Prooph\EventStore\ExpectedVersion;
use Prooph\EventStore\SliceReadStatus;
use Prooph\EventStore\StreamEventsSlice;
use Prooph\EventStore\StreamMetadata;
use ProophTest\EventStoreHttpClient\Helper\TestEvent;

class read_stream_events_with_unresolved_linkto extends TestCase
{
    use SpecificationWithConnection;

    /** @var EventData[] */
    private $testEvents;
    /** @var string */
    private $stream;
    /** @var string */
    private $links;

    protected function when(): void
    {
        $this->conn->setStreamMetadata(
            '$all',
            ExpectedVersion::ANY,
            StreamMetadata::create()->setReadRoles(SystemRoles::ALL)->build(),
            DefaultData::adminCredentials()
        );

        $this->testEvents = TestEvent::newAmount(20);

        $this->conn->appendToStream(
            $this->stream,
            ExpectedVersion::EMPTY_STREAM,
            $this->testEvents
        );

        $this->conn->appendToStream(
            $this->links,
            ExpectedVersion::EMPTY_STREAM,
            [new EventData(null, SystemEventTypes::LINK_TO, false, '0@read_stream_events_with_unresolved_linkto')]
        );

        $this->conn->deleteStream($this->stream, ExpectedVersion::ANY);
    }

    /** @test */
    public function ensure_deleted_stream(): void
    {
        $this->stream = 'read_stream_events_with_unresolved_linkto_1';
        $this->links = 'read_stream_events_with_unresolved_linkto_links_1';

        $this->execute(function () {
            $res = $this->conn->readStreamEventsForward(
                $this->stream,
                0,
                100,
                false
            );
            \assert($res instanceof StreamEventsSlice);

            $this->assertTrue(SliceReadStatus::streamNotFound()->equals($res->status()));
            $this->assertCount(0, $res->events());
        });
    }

    /** @test */
    public function returns_unresolved_linkto(): void
    {
        $this->markTestSkipped('Does not work via HTTP API');

        $this->stream = 'read_stream_events_with_unresolved_linkto_2';
        $this->links = 'read_stream_events_with_unresolved_linkto_links_2';

        $this->execute(function () {
            $read = $this->conn->readStreamEventsForward(
                $this->links,
                0,
                1,
                true
            );
            \assert($read instanceof StreamEventsSlice);

            $this->assertCount(1, $read->events());
            $this->assertNull($read->events()[0]->event());
            $this->assertNotNull($read->events()[0]->link());
        });
    }
}
