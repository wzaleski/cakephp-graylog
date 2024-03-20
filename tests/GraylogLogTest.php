<?php

namespace Tests\kbATeam\CakePhpGraylog;

use Cake\Log\Engine\BaseLog;
use Gelf\Message as GelfMessage;
use Gelf\Publisher;
use Gelf\Transport\IgnoreErrorTransportWrapper;
use Gelf\Transport\SslOptions;
use Gelf\Transport\TcpTransport;
use Gelf\Transport\UdpTransport;
use InvalidArgumentException;
use kbATeam\CakePhpGraylog\Log\Engine\GraylogLog;
use LogicException;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use stdClass;

/**
 * Class GraylogLogTest
 */
class GraylogLogTest extends TestCase
{
    /**
     * Test inheritance chain to ensure this test deals with the correct class.
     * @throws Exception
     */
    public function testInheritance()
    {
        $log = new PublicGraylogLog();
        static::assertInstanceOf(LoggerInterface::class, $log);
        static::assertInstanceOf(BaseLog::class, $log);
        static::assertInstanceOf(GraylogLog::class, $log);
    }

    /**
     * Test default config settings to ensure that later settings are different.
     * @throws PHPUnit_Framework_AssertionFailedError
     * @throws InvalidArgumentException
     */
    public function testDefaultConfig()
    {
        $log = new PublicGraylogLog();
        static::assertSame('udp', $log->getMyConfig('scheme'));
        static::assertSame('127.0.0.1', $log->getMyConfig('host'));
        static::assertSame(12201, $log->getMyConfig('port'));
        static::assertSame(UdpTransport::CHUNK_SIZE_LAN, $log->getMyConfig('chunk_size'));
        static::assertNull($log->getMyConfig('ssl_options'));
        static::assertSame('CakePHP', $log->getMyConfig('facility'));
        static::assertFalse($log->getMyConfig('append_backtrace'));
        static::assertFalse($log->getMyConfig('append_session'));
        static::assertFalse($log->getMyConfig('append_post'));
        static::assertSame([
            'password',
            'new_password',
            'old_password',
            'current_password'
        ], $log->getMyConfig('password_keys'));
        static::assertSame([
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG
        ], $log->getMyConfig('levels'));
    }

    /**
     * Test that valid ssl options are being added to the configuration.
     * @throws PHPUnit_Framework_Exception
     * @throws InvalidArgumentException
     */
    public function testValidSslOptions()
    {
        $log = new PublicGraylogLog(['ssl_options' => new SslOptions()]);
        static::assertInstanceOf(SslOptions::class, $log->getMyConfig('ssl_options'));
    }

    /**
     * Provide invalid values for ssl options.
     * @return array
     */
    public static function provideInvalidSslOptions(): array
    {
        return [
            ['3UmVE8Hx8X'],
            [6710],
            [6.5],
            [true],
            [false],
            [null],
            [[new SslOptions()]],
            [new stdClass()]
        ];
    }

    /**
     * Test that invalid ssl options will always result in null.
     * @param mixed $option
     * @dataProvider provideInvalidSslOptions
     * @throws InvalidArgumentException
     */
    public function testInvalidSslOptions($option)
    {
        $log = new PublicGraylogLog(['ssl_options' => $option]);
        static::assertNull($log->getMyConfig('ssl_options'));
    }

    /**
     * Data provider for connection URLs and their parsed values.
     * @return array
     */
    public static function provideConnectionUrl(): array
    {
        return [
            ['tcp://1.2.3.4:5678', 'tcp', '1.2.3.4', 5678],
            ['1.2.3.4:5678', 'udp', '1.2.3.4', 5678],
            ['TCP://1.2.3.4', 'tcp', '1.2.3.4', 12201],
        ];
    }

    /**
     * Test that a provided url overwrites the default values of scheme, host
     * and port.
     * @param string $url
     * @param string $scheme
     * @param string $host
     * @param int $port
     * @dataProvider provideConnectionUrl
     * @throws InvalidArgumentException
     * @throws InvalidArgumentException
     * @throws InvalidArgumentException
     */
    public function testConnectionUrl(string $url, string $scheme, string $host, int $port)
    {
        $log = new PublicGraylogLog(['url' => $url]);
        static::assertSame($scheme, $log->getMyConfig('scheme'));
        static::assertSame($host, $log->getMyConfig('host'));
        static::assertSame($port, $log->getMyConfig('port'));
    }

    /**
     * Test setting only certain log levels.
     * @throws InvalidArgumentException
     */
    public function testSettingLogLevels()
    {
        $log = new PublicGraylogLog(['levels' => [LogLevel::ERROR, LogLevel::WARNING, 'i5A64FtlPt']]);
        static::assertSame([LogLevel::ERROR, LogLevel::WARNING], $log->getMyConfig('levels'));
    }

    /**
     * Data provider of invalid log levels.
     * @return array
     */
    public static function provideInvalidLogLevels(): array
    {
        return [
            [['vUBTx40Vjr', 'WLWCTyCihX', 152, 4.256, true, false, null, ['debug'], new stdClass()]],
            ['68KNtGxwon'],
            [4391],
            [87.7],
            [true],
            [false],
            [null],
            [new stdClass()]
        ];
    }

    /**
     * Test setting only invalid log levels resulting in enabling all log levels.
     * @param mixed $levels
     * @dataProvider provideInvalidLogLevels
     */
    public function testInvalidLogLevels($levels)
    {
        $this->expectException(\TypeError::class);

        $log = new PublicGraylogLog(['levels' => $levels]);
        static::assertSame([
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG
        ], $log->getMyConfig('levels'));
    }

    /**
     * Test creating a GELF message with default settings.
     * @throws RuntimeException
     * @throws PHPUnit_Framework_Exception
     * @throws PHPUnit_Framework_Exception
     * @throws PHPUnit_Framework_Exception
     * @throws PHPUnit_Framework_Exception
     */
    public function testCreatingLongMessage()
    {
        /** @noinspection PhpArrayWriteIsNotUsedInspection */
        $_POST = [
            'PAYy2EKmuW' => 'E8RUOjsjAn'
        ];
        /** @noinspection PhpArrayWriteIsNotUsedInspection */
        $_SESSION = [
            'FDSa5d3EAq' => [
                'Z00UBd2lf9' => 'x6jETf91v8'
            ]
        ];
        $log = new PublicGraylogLog([
            'append_backtrace' => true,
            'append_session' => true,
            'append_post' => true
        ]);
        $message = $log->createMessage(LogLevel::DEBUG, 'mnfiXQoolR');
        static::assertInstanceOf(GelfMessage::class, $message);
        static::assertSame('CakePHP', $message->getAdditional('facility'));
        static::assertSame(LogLevel::DEBUG, $message->getLevel());
        static::assertSame('mnfiXQoolR', $message->getShortMessage());
        static::assertCount(3, $message->getAllAdditionals());
        static::assertStringContainsString('POST:', $message->getFullMessage());
        static::assertStringContainsString('Session:', $message->getFullMessage());
        static::assertStringContainsString('Trace:', $message->getFullMessage());
        unset($_POST);
    }

    /**
     * Test creating a GELF message without any appended debug information.
     * @throws RuntimeException
     */
    public function testShortMessage()
    {
        $log = new PublicGraylogLog();
        $message = $log->createMessage(LogLevel::ALERT, 'oIEUMcF1Ce');
        static::assertSame(LogLevel::ALERT, $message->getLevel());
        static::assertSame('oIEUMcF1Ce', $message->getShortMessage());
        static::assertNull($message->getFullMessage());
    }

    /**
     * Test getting a UDP transport class from default configuration.
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws PHPUnit_Framework_Exception
     */
    public function testUdpTransport()
    {
        $log = new PublicGraylogLog(['ignore_transport_errors' => false]);
        $transport = $log->getTransport();
        static::assertInstanceOf(UdpTransport::class, $transport);
        /**
         * Assert that we actually get the same instance.
         */
        static::assertSame($transport, $log->getTransport());
    }

    /**
     * Test getting a TCP transport class from default configuration.
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws PHPUnit_Framework_Exception
     */
    public function testTcpTransport()
    {
        $log = new PublicGraylogLog(['scheme' => 'tcp', 'ignore_transport_errors' => false]);
        $transport = $log->getTransport();
        static::assertInstanceOf(TcpTransport::class, $transport);
    }

    /**
     * Test getting a transport wrapper class for ignoring errors by default.
     * @return void
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws PHPUnit_Framework_Exception
     */
    public function testTransportWrapper()
    {
        $log = new PublicGraylogLog();
        $transport = $log->getTransport();
        static::assertInstanceOf(IgnoreErrorTransportWrapper::class, $transport);
    }

    /**
     * Test getting an exception from an invalid scheme.
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws PHPUnit_Framework_Exception
     */
    public function testInvalidScheme()
    {
        $log = new PublicGraylogLog(['scheme' => 'http']);
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unkown transport scheme for GreyLog!');
        $log->getTransport();
    }

    /**
     * Test getting a publisher class from default configuration.
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws PHPUnit_Framework_Exception
     */
    public function testPublisher()
    {
        $log = new PublicGraylogLog();
        $publisher = $log->getPublisher();
        static::assertInstanceOf(Publisher::class, $publisher);
        /**
         * Assert that we actually get the same instance.
         */
        static::assertSame($publisher, $log->getPublisher());
    }

    /**
     * Test adding additional field.
     * @throws RuntimeException
     * @throws RuntimeException
     */
    public function testAddingAdditionalFields()
    {
        $log = new PublicGraylogLog([
            'additional' => [
                'bmC5B27F3R' => static function () {
                    return 'MSg9BrM4DG';
                }
            ]
        ]);
        $message = $log->createMessage(LogLevel::INFO, 'Cy6BWVa5E0');
        static::assertSame(LogLevel::INFO, $message->getLevel());
        static::assertSame('Cy6BWVa5E0', $message->getShortMessage());
        static::assertNull($message->getFullMessage());
        static::assertSame('MSg9BrM4DG', $message->getAdditional('bmC5B27F3R'));
    }

    /**
     * Test creating a GELF message with all flags enabled.
     * @throws RuntimeException
     * @throws PHPUnit_Framework_Exception
     */
    public function testNoEmptyPostInLongMessage()
    {
        /** @noinspection PhpArrayWriteIsNotUsedInspection */
        $_SESSION = [
            'edjjLLLg14' => 'G78eIm8UbE'
        ];
        $log = new PublicGraylogLog([
            'append_post' => true
        ]);
        $message = $log->createMessage(LogLevel::CRITICAL, 'oP6MkuApf9');
        static::assertInstanceOf(GelfMessage::class, $message);
        static::assertSame('CakePHP', $message->getAdditional('facility'));
        static::assertSame(LogLevel::CRITICAL, $message->getLevel());
        static::assertSame('oP6MkuApf9', $message->getShortMessage());
        static::assertCount(3, $message->getAllAdditionals());
        static::assertNull($message->getFullMessage());
    }
}
