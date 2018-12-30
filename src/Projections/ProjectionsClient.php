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
use Prooph\EventStore\Exception\EventStoreConnectionException;
use Prooph\EventStore\Exception\ProjectionCommandConflictException;
use Prooph\EventStore\Projections\ProjectionDetails;
use Prooph\EventStore\Transport\Http\EndpointExtensions;
use Prooph\EventStore\Transport\Http\HttpStatusCode;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStore\Util\Json;
use Prooph\EventStoreHttpClient\Exception\ProjectionCommandFailedException;
use Prooph\EventStoreHttpClient\Http\HttpClient;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/** @internal */
class ProjectionsClient
{
    /** @var HttpClient */
    private $client;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    public function enable(
        EndPoint $endPoint,
        string $name,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): void {
        $this->sendPost(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s/command/enable',
                $name
            ),
            '',
            $userCredentials,
            HttpStatusCode::OK
        );
    }

    public function disable(
        EndPoint $endPoint,
        string $name,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): void {
        $this->sendPost(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s/command/disable',
                $name
            ),
            '',
            $userCredentials,
            HttpStatusCode::OK
        );
    }

    public function abort(
        EndPoint $endPoint,
        string $name,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): void {
        $this->sendPost(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s/command/abort',
                $name
            ),
            '',
            $userCredentials,
            HttpStatusCode::OK
        );
    }

    public function createOneTime(
        EndPoint $endPoint,
        string $query,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): void {
        $this->sendPost(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projections/onetime?type=JS'
            ),
            $query,
            $userCredentials,
            HttpStatusCode::CREATED
        );
    }

    public function createTransient(
        EndPoint $endPoint,
        string $name,
        string $query,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): void {
        $this->sendPost(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projections/transient?name=%s&type=JS',
                $name
            ),
            $query,
            $userCredentials,
            HttpStatusCode::CREATED
        );
    }

    public function createContinuous(
        EndPoint $endPoint,
        string $name,
        string $query,
        bool $trackEmittedStreams = false,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): void {
        $this->sendPost(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projections/continuous?name=%s&type=JS&emit=1&trackemittedstreams=%d',
                $name,
                (int) $trackEmittedStreams
            ),
            $query,
            $userCredentials,
            HttpStatusCode::CREATED
        );
    }

    /**
     * @return ProjectionDetails[]
     */
    public function listAll(
        EndPoint $endPoint,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): array {
        $response = $this->sendGet(
            EndpointExtensions::rawUrlToHttpUrl($endPoint, $httpSchema, '/projections/any'),
            $userCredentials,
            HttpStatusCode::OK
        );

        $data = Json::decode($response->getBody()->getContents());

        $projectionDetails = [];

        if (null === $data['projections']) {
            return $projectionDetails;
        }

        foreach ($data['projections'] as $entry) {
            $projectionDetails[] = $this->buildProjectionDetails($entry);
        }

        return $projectionDetails;
    }

    /**
     * @return ProjectionDetails[]
     */
    public function listOneTime(
        EndPoint $endPoint,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): array {
        $response = $this->sendGet(
            EndpointExtensions::rawUrlToHttpUrl($endPoint, $httpSchema, '/projections/onetime'),
            $userCredentials,
            HttpStatusCode::OK
        );

        $data = Json::decode($response->getBody()->getContents());

        $projectionDetails = [];

        if (null === $data['projections']) {
            return $projectionDetails;
        }

        foreach ($data['projections'] as $entry) {
            $projectionDetails[] = $this->buildProjectionDetails($entry);
        }

        return $projectionDetails;
    }

    /**
     * @return ProjectionDetails[]
     */
    public function listContinuous(
        EndPoint $endPoint,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): array {
        $response = $this->sendGet(
            EndpointExtensions::rawUrlToHttpUrl($endPoint, $httpSchema, '/projections/continuous'),
            $userCredentials,
            HttpStatusCode::OK
        );

        $data = Json::decode($response->getBody()->getContents());

        $projectionDetails = [];

        if (null === $data['projections']) {
            return $projectionDetails;
        }

        foreach ($data['projections'] as $entry) {
            $projectionDetails[] = $this->buildProjectionDetails($entry);
        }

        return $projectionDetails;
    }

    public function getStatus(
        EndPoint $endPoint,
        string $name,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): string {
        return $this->sendGet(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s',
                $name
            ),
            $userCredentials,
            HttpStatusCode::OK
        )->getBody()->getContents();
    }

    public function getState(
        EndPoint $endPoint,
        string $name,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): string {
        return $this->sendGet(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s/state',
                $name
            ),
            $userCredentials,
            HttpStatusCode::OK
        )->getBody()->getContents();
    }

    public function getPartitionState(
        EndPoint $endPoint,
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): string {
        return $this->sendGet(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s/state?partition=%s',
                $name,
                $partition
            ),
            $userCredentials,
            HttpStatusCode::OK
        )->getBody()->getContents();
    }

    public function getResult(
        EndPoint $endPoint,
        string $name,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): string {
        return $this->sendGet(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s/result',
                $name
            ),
            $userCredentials,
            HttpStatusCode::OK
        )->getBody()->getContents();
    }

    public function getPartitionResult(
        EndPoint $endPoint,
        string $name,
        string $partition,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): string {
        return $this->sendGet(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s/result?partition=%s',
                $name,
                $partition
            ),
            $userCredentials,
            HttpStatusCode::OK
        )->getBody()->getContents();
    }

    public function getStatistics(
        EndPoint $endPoint,
        string $name,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): string {
        return $this->sendGet(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s/statistics',
                $name
            ),
            $userCredentials,
            HttpStatusCode::OK
        )->getBody()->getContents();
    }

    public function getQuery(
        EndPoint $endPoint,
        string $name,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): string {
        return $this->sendGet(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s/query',
                $name
            ),
            $userCredentials,
            HttpStatusCode::OK
        )->getBody()->getContents();
    }

    public function updateQuery(
        EndPoint $endPoint,
        string $name,
        string $query,
        bool $emitEnabled = false,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): string {
        return $this->sendPut(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s/query?emit=' . (int) $emitEnabled,
                $name
            ),
            $query,
            $userCredentials,
            HttpStatusCode::OK
        )->getBody()->getContents();
    }

    public function reset(
        EndPoint $endPoint,
        string $name,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): void {
        $this->sendPost(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s/command/reset',
                $name
            ),
            '',
            $userCredentials,
            HttpStatusCode::OK
        );
    }

    public function delete(
        EndPoint $endPoint,
        string $name,
        bool $deleteEmittedStreams,
        ?UserCredentials $userCredentials = null,
        string $httpSchema = EndpointExtensions::HTTP_SCHEMA
    ): void {
        $this->sendDelete(
            EndpointExtensions::formatStringToHttpUrl(
                $endPoint,
                $httpSchema,
                '/projection/%s?deleteEmittedStreams=%d',
                $name,
                (int) $deleteEmittedStreams
            ),
            $userCredentials,
            HttpStatusCode::OK
        );
    }

    private function sendGet(
        string $uri,
        ?UserCredentials $userCredentials,
        int $expectedCode
    ): ResponseInterface {
        $response = $this->client->get(
            $uri,
            [],
            $userCredentials,
            static function (Throwable $e) {
                throw new EventStoreConnectionException($e->getMessage());
            }
        );

        if ($response->getStatusCode() !== $expectedCode) {
            throw new ProjectionCommandFailedException(
                $response->getStatusCode(),
                \sprintf(
                    'Server returned %d (%s) for GET on %s',
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    $uri
                )
            );
        }

        return $response;
    }

    private function sendDelete(
        string $uri,
        ?UserCredentials $userCredentials,
        int $expectedCode
    ): ResponseInterface {
        $response = $this->client->delete(
            $uri,
            [],
            $userCredentials,
            static function (Throwable $e) {
                throw new EventStoreConnectionException($e->getMessage());
            }
        );

        if ($response->getStatusCode() !== $expectedCode) {
            throw new ProjectionCommandFailedException(
                $response->getStatusCode(),
                \sprintf(
                    'Server returned %d (%s) for DELETE on %s',
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    $uri
                )
            );
        }

        return $response;
    }

    private function sendPut(
        string $uri,
        string $content,
        ?UserCredentials $userCredentials,
        int $expectedCode
    ): ResponseInterface {
        $response = $this->client->put(
            $uri,
            ['Content-Type' => 'application/json'],
            $content,
            $userCredentials,
            static function (Throwable $e) {
                throw new EventStoreConnectionException($e->getMessage());
            }
        );

        if ($response->getStatusCode() !== $expectedCode) {
            throw new ProjectionCommandFailedException(
                $response->getStatusCode(),
                \sprintf(
                    'Server returned %d (%s) for PUT on %s',
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    $uri
                )
            );
        }

        return $response;
    }

    private function sendPost(
        string $uri,
        string $content,
        ?UserCredentials $userCredentials,
        int $expectedCode
    ): ResponseInterface {
        $response = $this->client->post(
            $uri,
            ['Content-Type' => 'application/json'],
            $content,
            $userCredentials,
            static function (Throwable $e) {
                throw new EventStoreConnectionException($e->getMessage());
            }
        );

        if ($response->getStatusCode() === HttpStatusCode::CONFLICT) {
            throw new ProjectionCommandConflictException($response->getStatusCode(), $response->getReasonPhrase());
        }

        if ($response->getStatusCode() !== $expectedCode) {
            throw new ProjectionCommandFailedException(
                $response->getStatusCode(),
                \sprintf(
                    'Server returned %d (%s) for POST on %s',
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    $uri
                )
            );
        }

        return $response;
    }

    private function buildProjectionDetails(array $entry): ProjectionDetails
    {
        return new ProjectionDetails(
            $entry['coreProcessingTime'],
            $entry['version'],
            $entry['epoch'],
            $entry['effectiveName'],
            $entry['writesInProgress'],
            $entry['readsInProgress'],
            $entry['partitionsCached'],
            $entry['status'],
            $entry['stateReason'],
            $entry['name'],
            $entry['mode'],
            $entry['position'],
            $entry['progress'],
            $entry['lastCheckpoint'],
            $entry['eventsProcessedAfterRestart'],
            $entry['statusUrl'],
            $entry['stateUrl'],
            $entry['resultUrl'],
            $entry['queryUrl'],
            $entry['enableCommandUrl'],
            $entry['disableCommandUrl'],
            $entry['checkpointStatus'],
            $entry['bufferedEvents'],
            $entry['writePendingEventsBeforeCheckpoint'],
            $entry['writePendingEventsAfterCheckpoint']
        );
    }
}
