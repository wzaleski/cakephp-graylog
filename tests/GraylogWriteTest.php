<?php

/**
 * In order to perform a write test, we simply overwrite the class \Gelf\Publisher
 * with a fake one, that will not actually publish anything.
 * Yes it's dirty. Yes it's probably a bug in PHP. But it's so handy ... ^^
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
namespace Gelf {
    /**
     * Fake class Publisher
     */
    class Publisher
    {
        /**
         * @var \Gelf\Transport\TransportInterface
         */
        public $transport;

        /**
         * @var \Gelf\MessageInterface
         */
        public $message;

        /**
         * FakePublisher constructor.
         * @param \Gelf\Transport\TransportInterface|null $transport
         */
        public function __construct($transport = null)
        {
            $this->transport = $transport;
        }

        /**
         * @param \Gelf\MessageInterface $message
         * @return void
         */

        public function publish($message)
        {
            $this->message = $message;
        }
    }
}

namespace {

    use Gelf\Message as GelfMessage;
    use Gelf\Transport\UdpTransport;
    use PHPUnit\Framework\TestCase;

    /**
     * Class GraylogWriteTest
     */
    class GraylogWriteTest extends TestCase
    {
        /**
         * Test writing a message using a fake publisher class.
         * @return void
         */
        public function testWriteUsingFakePublisher(): void
        {
            $log = new PublicGraylogLog([
                'append_backtrace' => false,
                'append_session' => false,
                'append_post' => false,
                'ignore_transport_errors' => false
            ]);
            //This is the fake publisher, not the real one.
            $publisher = $log->getPublisher();
            /**
             * In order to test whether this actually is the fake publisher,
             * let's see if the expected variables are present.
             */
            static::assertInstanceOf(UdpTransport::class, $publisher->transport);
            static::assertNull($publisher->message);
            $log->write('error', 'P5oUZLqcjx');
            static::assertInstanceOf(GelfMessage::class, $publisher->message);
            static::assertSame('CakePHP', $publisher->message->getFacility());
            static::assertSame('error', $publisher->message->getLevel());
            static::assertSame('P5oUZLqcjx', $publisher->message->getShortMessage());
            static::assertNull($publisher->message->getFullMessage());
            static::assertSame([], $publisher->message->getAllAdditionals());
        }
    }
}
