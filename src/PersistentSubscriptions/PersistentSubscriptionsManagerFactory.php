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

namespace Prooph\EventStoreHttpClient\PersistentSubscriptions;

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\RequestFactory;
use Http\Message\ResponseFactory;
use Prooph\EventStoreHttpClient\EndPoint;
use Prooph\EventStoreHttpClient\Http\EndpointExtensions;
use Prooph\EventStoreHttpClient\Http\HttpClient;
use Prooph\EventStoreHttpClient\UserCredentials;
use Psr\Http\Client\ClientInterface;

class PersistentSubscriptionsManagerFactory
{
    public static function create(
        EndPoint $endPoint,
        string $schema = EndpointExtensions::HTTP_SCHEMA,
        ?UserCredentials $defaultUserCredentials = null,
        ClientInterface $client = null,
        RequestFactory $requestFactory = null,
        ResponseFactory $responseFactory = null
    ): PersistentSubscriptionsManager {
        if (null === $client) {
            $client = HttpClientDiscovery::find();
        }

        if (null === $requestFactory) {
            $requestFactory = MessageFactoryDiscovery::find();
        }

        if (null === $responseFactory) {
            $responseFactory = MessageFactoryDiscovery::find();
        }

        $httpClient = new HttpClient($client, $requestFactory, $responseFactory);

        return new PersistentSubscriptionsManager(
            $httpClient,
            $endPoint,
            $schema,
            $defaultUserCredentials
        );
    }
}
