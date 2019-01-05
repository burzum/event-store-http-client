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
use Prooph\EventStore\EventData;
use Prooph\EventStore\ExpectedVersion;
use Prooph\EventStore\PersistentSubscriptionSettings;
use Prooph\EventStore\Util\Guid;

class update_existing_persistent_subscription extends TestCase
{
    use SpecificationWithConnection;

    /** @var string */
    private $stream;
    /** @var PersistentSubscriptionSettings */
    private $settings;

    protected function given(): void
    {
        $this->stream = Guid::generateAsHex();
        $this->settings = PersistentSubscriptionSettings::create()
            ->doNotResolveLinkTos()
            ->startFromCurrent()
            ->build();

        $this->conn->appendToStream(
            $this->stream,
            ExpectedVersion::ANY,
            [new EventData(null, 'whatever', true, '{"foo":2}')]
        );

        $this->conn->createPersistentSubscription(
            $this->stream,
            'existing',
            $this->settings,
            DefaultData::adminCredentials()
        );
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function the_completion_succeeds(): void
    {
        $this->execute(function () {
            $this->conn->updatePersistentSubscription(
                $this->stream,
                'existing',
                $this->settings,
                DefaultData::adminCredentials()
            );
        });
    }
}
