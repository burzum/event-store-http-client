<?php

/**
 * This file is part of `prooph/event-store-http-client`.
 * (c) 2018-2020 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2018-2020 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\EventStoreHttpClient;

use Exception;
use PHPUnit\Framework\TestCase;
use Prooph\EventStore\EventData;
use Prooph\EventStore\EventId;
use Prooph\EventStore\EventStorePersistentSubscription;
use Prooph\EventStore\ExpectedVersion;
use Prooph\EventStore\PersistentSubscriptionSettings;
use Prooph\EventStore\ResolvedEvent;
use Prooph\EventStore\SubscriptionDropReason;
use Prooph\EventStore\Util\Guid;
use Throwable;

class a_nak_in_subscription_handler_in_autoack_mode_drops_the_subscription extends TestCase
{
    use SpecificationWithConnection;

    /** @var string */
    private $stream;
    /** @var PersistentSubscriptionSettings */
    private $settings;
    /** @var bool */
    private $reset;
    /** @var Throwable */
    private $exception;
    /** @var SubscriptionDropReason */
    private $reason;
    /** @var string */
    private $group;
    /** @var EventStorePersistentSubscription */
    private $subscription;

    protected function setUp(): void
    {
        $this->stream = '$' . Guid::generateString();
        $this->settings = PersistentSubscriptionSettings::create()
            ->doNotResolveLinkTos()
            ->startFromBeginning()
            ->build();
        $this->reset = false;
        $this->group = 'naktest';
    }

    protected function given(): void
    {
        $this->conn->createPersistentSubscription(
            $this->stream,
            $this->group,
            $this->settings,
            DefaultData::adminCredentials()
        );

        $dropBehaviour = function (
            EventStorePersistentSubscription $subscription,
            SubscriptionDropReason $reason,
            ?Throwable $exception = null
        ): void {
            $this->reason = $reason;
            $this->exception = $exception;
            $this->reset = true;
        };

        $this->subscription = $this->conn->connectToPersistentSubscription(
            $this->stream,
            $this->group,
            function (
                EventStorePersistentSubscription $subscription,
                ResolvedEvent $resolvedEvent,
                ?int $retryCount = null
            ): void {
                throw new \Exception('test');
            },
            $dropBehaviour,
            10,
            true,
            DefaultData::adminCredentials()
        );
    }

    protected function when(): void
    {
        $this->conn->appendToStream(
            $this->stream,
            ExpectedVersion::ANY,
            [
                new EventData(EventId::generate(), 'test', true, '{"foo: "bar"}'),
            ],
            DefaultData::adminCredentials()
        );
    }

    /**
     * @test
     * @throws Throwable
     */
    public function the_subscription_gets_dropped(): void
    {
        $this->execute(function (): void {
            $this->subscription->start();

            $this->assertTrue($this->reset);
            $this->assertTrue($this->reason->equals(SubscriptionDropReason::eventHandlerException()));
            $this->assertInstanceOf(Exception::class, $this->exception);
            $this->assertSame('test', $this->exception->getMessage());
        });
    }
}
