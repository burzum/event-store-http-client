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

namespace Prooph\EventStoreHttpClient\Projections;

use Prooph\EventStore\EndPoint;
use Prooph\EventStore\Projections\QueryManager as SyncQueryManager;
use Prooph\EventStore\Transport\Http\EndpointExtensions;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStoreHttpClient\Http\HttpClient;

/**
 * API for executing queries in the Event Store through PHP code.
 * Communicates with the Event Store over the RESTful API.
 *
 * Note: Configure the HTTP client with large enough timeout.
 */
class QueryManager implements SyncQueryManager
{
    /** @var ProjectionsManager */
    private $projectionsManager;

    /** @internal  */
    public function __construct(
        HttpClient $client,
        EndPoint $endPoint,
        string $schema = EndpointExtensions::HTTP_SCHEMA,
        ?UserCredentials $defaultUserCredentials = null
    ) {
        $this->projectionsManager = new ProjectionsManager(
            $client,
            $endPoint,
            $schema,
            $defaultUserCredentials
        );
    }

    /**
     * Executes a query
     *
     * Creates a new transient projection and polls its status until it is Completed
     *
     * returns String of JSON containing query result
     *
     * @param string $name A name for the query
     * @param string $query The source code for the query
     * @param int $initialPollingDelay Initial time to wait between polling for projection status
     * @param int $maximumPollingDelay Maximum time to wait between polling for projection status
     * @param UserCredentials|null $userCredentials Credentials for a user with permission to create a query
     *
     * @return string
     */
    public function execute(
        string $name,
        string $query,
        int $initialPollingDelay,
        int $maximumPollingDelay,
        ?UserCredentials $userCredentials = null
    ): string {
        $this->projectionsManager->createTransient(
            $name,
            $query,
            $userCredentials
        );

        $this->waitForCompleted(
            $name,
            $initialPollingDelay,
            $maximumPollingDelay,
            $userCredentials
        );

        return $this->projectionsManager->getState(
            $name,
            $userCredentials
        );
    }

    private function waitForCompleted(
        string $name,
        int $initialPollingDelay,
        int $maximumPollingDelay,
        ?UserCredentials $userCredentials
    ): void {
        $attempts = 0;
        $status = $this->getStatus($name, $userCredentials);

        while (false === \strpos($status, 'Completed')) {
            $attempts++;

            $this->delayPolling(
                $attempts,
                $initialPollingDelay,
                $maximumPollingDelay
            );

            $status = $this->getStatus($name, $userCredentials);
        }
    }

    private function delayPolling(
        int $attempts,
        int $initialPollingDelay,
        int $maximumPollingDelay
    ): void {
        $delayInMilliseconds = $initialPollingDelay * (2 ** $attempts - 1);
        $delayInMilliseconds = (int) \min($delayInMilliseconds, $maximumPollingDelay);

        \usleep($delayInMilliseconds * 1000);
    }

    private function getStatus(string $name, ?UserCredentials $userCredentials): string
    {
        return $this->projectionsManager->getStatus($name, $userCredentials);
    }
}
