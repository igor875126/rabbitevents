<?php

namespace Nuwber\Events\Event;

use Illuminate\Support\Arr;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\AmqpContext;
use Interop\Queue\Exception;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use ReflectionClass;

class Publisher
{
    /**
     * @var AmqpTopic
     */
    private $topic;

    /**
     * @var AmqpContext
     */
    private $context;

    public function __construct(AmqpContext $context, AmqpTopic $topic)
    {
        $this->context = $context;
        $this->topic = $topic;
    }

    /**
     * Publishes payload
     *
     * @param string $event
     * @param array $payload
     * @return Publisher
     *
     * @throws Exception
     * @throws InvalidDestinationException
     * @throws InvalidMessageException
     */
    public function send(string $event, array $payload): self
    {
        $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $message = $this->context->createMessage($payload);
        $message->setRoutingKey($event);

        $this->context->createProducer()->send($this->topic, $message);

        return $this;
    }

    public function publish($event, array $payload = [])
    {
        return $this->send(...$this->extractEventAndPayload($event, $payload));
    }

    /**
     *  Extract event and payload and prepare them for publishing.
     *
     * @param $event
     * @param array $payload
     * @return array
     */
    private function extractEventAndPayload($event, array $payload)
    {
        if (is_object($event) && $this->eventShouldBePublished($event)) {
            return [$event->publishEventKey(), $event->toPublish()];
        }

        if (is_string($event)) {
            return [$event, Arr::wrap($payload)];
        }

        throw new \InvalidArgumentException('Event must be a string or implement `ShouldPublish` interface');
    }

    /**
     * Determine if the event handler class should be queued.
     *
     * @param object $event
     * @return bool
     */
    protected function eventShouldBePublished($event)
    {
        try {
            return (new ReflectionClass(get_class($event)))
                ->implementsInterface(ShouldPublish::class);
        } catch (Exception $e) {
            return false;
        }
    }
}
