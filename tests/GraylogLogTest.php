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
use PHPUnit\Framework\AssertionFailedError;
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
     * @return void
     */
    public function testInheritance()
    {
        $log = new PublicGraylogLog();
        $this->assertInstanceOf(LoggerInterface::class, $log);
        $this->assertInstanceOf(BaseLog::class, $log);
        $this->assertInstanceOf(GraylogLog::class, $log);
    }

    /**
     * Test default config settings to ensure that later settings are different.
     * @throws AssertionFailedError
     * @throws InvalidArgumentException
     * @return void
     */
    public function testDefaultConfig()
    {
        $log = new PublicGraylogLog();
        $this->assertSame('udp', $log->getMyConfig('scheme'));
        $this->assertSame('127.0.0.1', $log->getMyConfig('host'));
        $this->assertSame(12201, $log->getMyConfig('port'));
        $this->assertSame(UdpTransport::CHUNK_SIZE_LAN, $log->getMyConfig('chunk_size'));
        $this->assertNull($log->getMyConfig('ssl_options'));
        $this->assertSame('CakePHP', $log->getMyConfig('facility'));
        $this->assertFalse($log->getMyConfig('append_backtrace'));
        $this->assertFalse($log->getMyConfig('append_session'));
        $this->assertFalse($log->getMyConfig('append_post'));
        $this->assertSame([
            'password',
            'new_password',
            'old_password',
            'current_password'
        ], $log->getMyConfig('password_keys'));
        $this->assertSame([
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
     * @throws Exception
     * @throws InvalidArgumentException
     * @return void
     */
    public function testValidSslOptions()
    {
        $log = new PublicGraylogLog(['ssl_options' => new SslOptions()]);
        $this->assertInstanceOf(SslOptions::class, $log->getMyConfig('ssl_options'));
    }

    /**
     * Provide invalid values for ssl options.
     * @return array<int, array<mixed>>
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
     * @return void
     */
    public function testInvalidSslOptions($option)
    {
        $log = new PublicGraylogLog(['ssl_options' => $option]);
        $this->assertNull($log->getMyConfig('ssl_options'));
    }

    /**
     * Data provider for connection URLs and their parsed values.
     * @return array<int,array<mixed>>
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
     * @return void
     */
    public function testConnectionUrl(string $url, string $scheme, string $host, int $port)
    {
        $log = new PublicGraylogLog(['url' => $url]);
        $this->assertSame($scheme, $log->getMyConfig('scheme'));
        $this->assertSame($host, $log->getMyConfig('host'));
        $this->assertSame($port, $log->getMyConfig('port'));
    }

    /**
     * Test setting only certain log levels.
     * @throws InvalidArgumentException
     * @return void
     */
    public function testSettingLogLevels()
    {
        $log = new PublicGraylogLog(['levels' => [LogLevel::ERROR, LogLevel::WARNING, 'i5A64FtlPt']]);
        $this->assertSame([LogLevel::ERROR, LogLevel::WARNING], $log->getMyConfig('levels'));
    }

    /**
     * Data provider of invalid log levels.
     * @return array<mixed>
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
     * @return void
     */
    public function testInvalidLogLevels($levels)
    {
        $this->expectException(\TypeError::class);

        $log = new PublicGraylogLog(['levels' => $levels]);
        $this->assertSame([
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
     * @throws Exception
     * @return void
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
        $this->assertInstanceOf(GelfMessage::class, $message);
        $this->assertSame('CakePHP', $message->getAdditional('facility'));
        $this->assertSame(LogLevel::DEBUG, $message->getLevel());
        $this->assertSame('mnfiXQoolR', $message->getShortMessage());
        $this->assertCount(1, $message->getAllAdditionals());
        $this->assertStringContainsString('POST:', (string)$message->getFullMessage());
        $this->assertStringContainsString('Session:', (string)$message->getFullMessage());
        $this->assertStringContainsString('Trace:', (string)$message->getFullMessage());
        unset($_POST);
    }

    /**
     * Test creating a GELF message without any appended debug information.
     * @throws RuntimeException
     * @return void
     */
    public function testShortMessage()
    {
        $log = new PublicGraylogLog();
        $message = $log->createMessage(LogLevel::ALERT, 'oIEUMcF1Ce');
        $this->assertSame(LogLevel::ALERT, $message->getLevel());
        $this->assertSame('oIEUMcF1Ce', $message->getShortMessage());
        $this->assertNull($message->getFullMessage());
    }

    /**
     * Test getting a UDP transport class from default configuration.
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws Exception
     * @return void
     */
    public function testUdpTransport()
    {
        $log = new PublicGraylogLog(['ignore_transport_errors' => false]);
        $transport = $log->getTransport();
        $this->assertInstanceOf(UdpTransport::class, $transport);
        /**
         * Assert that we actually get the same instance.
         */
        $this->assertSame($transport, $log->getTransport());
    }

    /**
     * Test getting a TCP transport class from default configuration.
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws Exception
     * @return void
     */
    public function testTcpTransport()
    {
        $log = new PublicGraylogLog(['scheme' => 'tcp', 'ignore_transport_errors' => false]);
        $transport = $log->getTransport();
        $this->assertInstanceOf(TcpTransport::class, $transport);
    }

    /**
     * Test getting a transport wrapper class for ignoring errors by default.
     * @return void
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws Exception
     * @return void
     */
    public function testTransportWrapper()
    {
        $log = new PublicGraylogLog();
        $transport = $log->getTransport();
        $this->assertInstanceOf(IgnoreErrorTransportWrapper::class, $transport);
    }

    /**
     * Test getting an exception from an invalid scheme.
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws Exception
     * @return void
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
     * @throws Exception
     * @return void
     */
    public function testPublisher()
    {
        $log = new PublicGraylogLog();
        $publisher = $log->getPublisher();
        $this->assertInstanceOf(Publisher::class, $publisher);
        /**
         * Assert that we actually get the same instance.
         */
        $this->assertSame($publisher, $log->getPublisher());
    }

    /**
     * Test adding additional field.
     * @throws RuntimeException
     * @throws RuntimeException
     * @return void
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
        $this->assertSame(LogLevel::INFO, $message->getLevel());
        $this->assertSame('Cy6BWVa5E0', $message->getShortMessage());
        $this->assertNull($message->getFullMessage());
        $this->assertSame('MSg9BrM4DG', $message->getAdditional('bmC5B27F3R'));
    }

    /**
     * Test creating a GELF message with all flags enabled.
     * @throws RuntimeException
     * @throws Exception
     * @return void
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
        $this->assertInstanceOf(GelfMessage::class, $message);
        $this->assertSame('CakePHP', $message->getAdditional('facility'));
        $this->assertSame(LogLevel::CRITICAL, $message->getLevel());
        $this->assertSame('oP6MkuApf9', $message->getShortMessage());
        $this->assertCount(1, $message->getAllAdditionals());
        $this->assertNull($message->getFullMessage());
    }
}
