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

namespace Prooph\EventStoreHttpClient\ClientOperations;

use Http\Message\RequestFactory;
use Http\Message\UriFactory;
use Prooph\EventStoreHttpClient\Common\SystemEventTypes;
use Prooph\EventStoreHttpClient\EventId;
use Prooph\EventStoreHttpClient\Exception\AccessDeniedException;
use Prooph\EventStoreHttpClient\Http\HttpClient;
use Prooph\EventStoreHttpClient\Http\HttpMethod;
use Prooph\EventStoreHttpClient\ReadDirection;
use Prooph\EventStoreHttpClient\RecordedEvent;
use Prooph\EventStoreHttpClient\ResolvedEvent;
use Prooph\EventStoreHttpClient\SliceReadStatus;
use Prooph\EventStoreHttpClient\StreamEventsSlice;
use Prooph\EventStoreHttpClient\UserCredentials;
use Prooph\EventStoreHttpClient\Util\DateTime;
use Prooph\EventStoreHttpClient\Util\Json;

/** @internal */
class ReadStreamEventsForwardOperation extends Operation
{
    public function __invoke(
        HttpClient $httpClient,
        RequestFactory $requestFactory,
        UriFactory $uriFactory,
        string $baseUri,
        string $stream,
        int $start,
        int $count,
        bool $resolveLinkTos,
        int $longPoll,
        ?UserCredentials $userCredentials,
        bool $requireMaster
    ): StreamEventsSlice {
        $headers = [
            'Accept' => 'application/vnd.eventstore.atom+json',
        ];

        if (! $resolveLinkTos) {
            $headers['ES-ResolveLinkTos'] = 'false';
        }

        if ($requireMaster) {
            $headers['ES-RequiresMaster'] = 'true';
        }

        if ($longPoll > 0) {
            $headers['ES-LongPoll'] = $longPoll;
        }

        $request = $requestFactory->createRequest(
            HttpMethod::GET,
            $uriFactory->createUri(
                $baseUri . '/streams/' . \urlencode($stream) . '/' . $start . '/forward/' . $count . '?embed=tryharder'
            ),
            $headers
        );

        $response = $this->sendRequest($httpClient, $userCredentials, $request);

        switch ($response->getStatusCode()) {
            case 401:
                throw AccessDeniedException::toStream($stream);
            case 404:
                return new StreamEventsSlice(
                    SliceReadStatus::streamNotFound(),
                    $stream,
                    $start,
                    ReadDirection::forward(),
                    [],
                    0,
                    0,
                    true
                );
            case 410:
                return new StreamEventsSlice(
                    SliceReadStatus::streamDeleted(),
                    $stream,
                    $start,
                    ReadDirection::forward(),
                    [],
                    0,
                    0,
                    true
                );
            case 200:
                $json = Json::decode($response->getBody()->getContents());

                $events = [];
                $lastEventNumber = 0;
                foreach (\array_reverse($json['entries']) as $entry) {
                    if ($json['streamId'] !== $stream) {
                        $data = $entry['data'] ?? '';

                        if (\is_array($data)) {
                            $data = Json::encode($data);
                        }

                        $field = isset($json['isLinkMetaData']) && $json['isLinkMetaData'] ? 'linkMetaData' : 'metaData';

                        $metadata = $json[$field] ?? '';

                        if (\is_array($metadata)) {
                            $metadata = Json::encode($metadata);
                        }

                        $link = new RecordedEvent(
                            $stream,
                            $json['positionEventNumber'],
                            EventId::fromString($json['eventId']),
                            $json['eventType'],
                            $json['isJson'],
                            $data,
                            $metadata,
                            DateTime::create($json['updated'])
                        );
                    } else {
                        $link = null;
                    }

                    $record = new RecordedEvent(
                        $json['streamId'],
                        $json['eventNumber'],
                        EventId::fromString($json['eventId']),
                        SystemEventTypes::LINK_TO,
                        false,
                        $json['title'],
                        '',
                        DateTime::create($json['updated'])
                    );

                    $events[] = new ResolvedEvent($record, $link, null);

                    $lastEventNumber = $entry['eventNumber'];
                }

                return new StreamEventsSlice(
                    SliceReadStatus::success(),
                    $stream,
                    $start,
                    ReadDirection::forward(),
                    $events,
                    $lastEventNumber + 1,
                    $lastEventNumber,
                    $json['headOfStream']
                );
            default:
                throw new \UnexpectedValueException('Unexpected status code ' . $response->getStatusCode() . ' returned');
        }
    }
}
