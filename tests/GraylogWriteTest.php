<?php

/**
 * In order to perform a write-test, we simply overwrite the class \Gelf\Publisher
 * with a fake one, that will not actually publish anything.
 * Yes it's dirty. Yes it's probably a bug in PHP. But it's so handy ... ^^
 * @noinspection PhpIllegalPsrClassPathInspection
 */
namespace Gelf {

    use Gelf\Transport\TransportInterface;

    /**
     * Fake class Publisher
     */
    class Publisher
    {
        /**
         * @var TransportInterface|null
         */
        public $transport;

        /**
         * @var MessageInterface
         */
        public $message;

        /**
         * FakePublisher constructor.
         * @param TransportInterface|null $transport
         */
        public function __construct(TransportInterface $transport = null)
        {
            $this->transport = $transport;
        }

        /**
         * @param MessageInterface $message
         * @noinspection PhpUnused
         * @return void
         */
        public function publish(MessageInterface $message)
        {
            $this->message = $message;
        }
    }
}

namespace Tests\kbATeam\CakePhpGraylog {

    use Gelf\Message as GelfMessage;
    use Gelf\Transport\UdpTransport;
    use InvalidArgumentException;
    use LogicException;
    use PHPUnit\Framework\Exception;
    use PHPUnit\Framework\TestCase;
    use PHPUnit_Framework_Exception;
    use PHPUnit_Framework_TestCase;
    use RuntimeException;

    /**
     * Class GraylogWriteTest
     */
    class GraylogWriteTest extends TestCase
    {
        /**
         * Test writing a message using a fake publisher class.
         * @throws Exception
         * @throws InvalidArgumentException
         * @throws LogicException
         * @throws RuntimeException
         * @return void
         */
        public function testWriteUsingFakePublisher()
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
            $log->log('error', 'P5oUZLqcjx');
            static::assertInstanceOf(GelfMessage::class, $publisher->message);
            static::assertSame('CakePHP', $publisher->message->getAdditional('facility'));
            static::assertSame('error', $publisher->message->getLevel());
            static::assertSame('P5oUZLqcjx', $publisher->message->getShortMessage());
            static::assertNull($publisher->message->getFullMessage());
            static::assertCount(1, $publisher->message->getAllAdditionals());

            /**
            $expected = [
                'facility' => 'CakePHP',
                'file' => '/app/vendor/phpunit/phpunit/src/Framework/TestCase.php',
               'line' => 1612,
            ];
            static::assertSame($expected, $publisher->message->getAllAdditionals());
             */
        }
    }
}
