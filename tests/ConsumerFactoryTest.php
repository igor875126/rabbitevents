<?php

namespace Nuwber\Events\Tests;

use Enqueue\AmqpLib\AmqpConsumer;
use Enqueue\AmqpLib\AmqpContext;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Nuwber\Events\ConsumerFactory;
use Nuwber\Events\NameResolver;

class ConsumerFactoryTest extends TestCase
{
    private $event = 'item.created';

    public function testMake()
    {
        $nameResolver = new NameResolver($this->event, 'test-app');

        $queue = new AmqpQueue('');

        $consumer = \Mockery::mock(AmqpConsumer::class)->makePartial();

        $context = \Mockery::mock(AmqpContext::class)->makePartial();
        $context->shouldReceive('createConsumer')
            ->with($queue)
            ->once()
            ->andReturn($consumer);

        $context->shouldReceive('createQueue')
            ->with($nameResolver->queue())
            ->once()
            ->andReturn($queue);

        $context->shouldReceive('bind')
            ->withAnyArgs()
            ->once();
        $context->shouldReceive('declareQueue')
            ->with($queue)
            ->once();

        $factory = new ConsumerFactory($context, new AmqpTopic('events'));

        self::assertEquals($consumer, $factory->make($nameResolver));
    }
}
