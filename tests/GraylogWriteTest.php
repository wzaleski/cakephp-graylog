<?php

/**
 * In order to perform a write test, we simply overwrite the class \Gelf\Publisher
 * with a fake one, that will not actually publish anyting.
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

    /**
     * Class GraylogWriteTest
     */
    class GraylogWriteTest extends PHPUnit_Framework_TestCase
    {
        /**
         * Test writing a message using a fake publisher class.
         */
        public function testWriteUsingFakePublisher()
        {
            $log = new PublicGraylogLog([
                'append_backtrace' => false,
                'append_session' => false,
                'append_post' => false
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
            static::assertSame([
                'http_referer' => null,
                'request_uri' => null
            ], $publisher->message->getAllAdditionals());
        }
    }
}
