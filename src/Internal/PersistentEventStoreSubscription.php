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

namespace Prooph\EventStoreHttpClient\Internal;

use Prooph\EventStore\Internal\PersistentEventStoreSubscription as PersistentEventStoreSubscriptionBase;

class PersistentEventStoreSubscription extends PersistentEventStoreSubscriptionBase
{
    private ConnectToPersistentSubscriptionOperation $operation;

    public function __construct(
        ConnectToPersistentSubscriptionOperation $subscriptionOperation,
        string $streamId
    ) {
        parent::__construct(
            $subscriptionOperation,
            $streamId,
            -1,
            -1
        );

        $this->operation = $subscriptionOperation;
    }

    public function operation(): ConnectToPersistentSubscriptionOperation
    {
        return $this->operation;
    }
}
