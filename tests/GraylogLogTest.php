<?php

use Gelf\Message as GelfMessage;
use Gelf\Publisher;
use Gelf\Transport\SslOptions;
use Gelf\Transport\TcpTransport;
use Gelf\Transport\UdpTransport;
use Psr\Log\LogLevel;

/**
 * Class GraylogLogTest
 */
class GraylogLogTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test inheritance chain to ensure this test deals with the correct class.
     */
    public function testInheritance()
    {
        $log = new PublicGraylogLog();
        static::assertInstanceOf(CakeLogInterface::class, $log);
        static::assertInstanceOf(BaseLog::class, $log);
        static::assertInstanceOf(GraylogLog::class, $log);
    }

    /**
     * Test default config settings to ensure that later settings are different.
     */
    public function testDefaultConfig()
    {
        $log = new PublicGraylogLog();
        static::assertSame('udp', $log->getConfig('scheme'));
        static::assertSame('127.0.0.1', $log->getConfig('host'));
        static::assertSame(12201, $log->getConfig('port'));
        static::assertSame(UdpTransport::CHUNK_SIZE_LAN, $log->getConfig('chunk_size'));
        static::assertNull($log->getConfig('ssl_options'));
        static::assertSame('CakePHP', $log->getConfig('facility'));
        static::assertFalse($log->getConfig('append_backtrace'));
        static::assertFalse($log->getConfig('append_session'));
        static::assertFalse($log->getConfig('append_post'));
        static::assertSame([
            'password',
            'new_password',
            'old_password',
            'current_password'
        ], $log->getConfig('password_keys'));
        static::assertSame([
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG
        ], $log->getConfig('types'));
    }

    /**
     * Test that valid ssl options are being added to the configuration.
     */
    public function testValidSslOptions()
    {
        $log = new PublicGraylogLog(['ssl_options' => new SslOptions()]);
        static::assertInstanceOf(SslOptions::class, $log->getConfig('ssl_options'));
    }

    /**
     * Provide invalid values for ssl options.
     * @return array
     */
    public static function provideInvalidSslOptions()
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
     */
    public function testInvalidSslOptions($option)
    {
        $log = new PublicGraylogLog(['ssl_options' => $option]);
        static::assertNull($log->getConfig('ssl_options'));
    }

    /**
     * Data provider for connection URLs and their parsed values.
     * @return array
     */
    public static function provideConnectionUrl()
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
     */
    public function testConnectionUrl($url, $scheme, $host, $port)
    {
        $log = new PublicGraylogLog(['url' => $url]);
        static::assertSame($scheme, $log->getConfig('scheme'));
        static::assertSame($host, $log->getConfig('host'));
        static::assertSame($port, $log->getConfig('port'));
    }

    /**
     * Test setting only certain log types.
     */
    public function testSettingLogTypes()
    {
        $log = new PublicGraylogLog(['types' => [LogLevel::ERROR, LogLevel::WARNING, 'i5A64FtlPt']]);
        static::assertSame([LogLevel::ERROR, LogLevel::WARNING], $log->getConfig('types'));
    }

    /**
     * Data provider of invalid log types.
     * @return array
     */
    public static function provideInvalidLogTypes()
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
     * Test setting only invalid log types resulting in enabling all log types.
     * @param mixed $types
     * @dataProvider provideInvalidLogTypes
     */
    public function testInvalidLogTypes($types)
    {
        $log = new PublicGraylogLog(['types' => $types]);
        static::assertSame([
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG
        ], $log->getConfig('types'));
    }

    /**
     * Test creating a GELF message with all flags enabled.
     */
    public function testCreatingLongMessage()
    {
        $_POST = [
            'PAYy2EKmuW' => 'E8RUOjsjAn'
        ];
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
        static::assertSame('CakePHP', $message->getFacility());
        static::assertSame(LogLevel::DEBUG, $message->getLevel());
        static::assertSame('mnfiXQoolR', $message->getShortMessage());
        static::assertSame([], $message->getAllAdditionals());
        static::assertContains('POST:', $message->getFullMessage());
        static::assertContains('Session:', $message->getFullMessage());
        static::assertContains('Trace:', $message->getFullMessage());
    }

    /**
     * Test creating a GELF message without any appended debug information.
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
     */
    public function testUdpTransport()
    {
        $log = new PublicGraylogLog();
        $transport = $log->getTransport();
        static::assertInstanceOf(UdpTransport::class, $transport);
        /**
         * Assert that we actually get the same instance.
         */
        static::assertSame($transport, $log->getTransport());
    }

    /**
     * Test getting a TCP transport class from default configuration.
     */
    public function testTcpTransport()
    {
        $log = new PublicGraylogLog(['scheme' => 'tcp']);
        $transport = $log->getTransport();
        static::assertInstanceOf(TcpTransport::class, $transport);
    }

    /**
     * Test getting an exception from an invalid scheme.
     * @expectedException \LogicException
     * @expectedExceptionMessage Unkown transport scheme for GreyLog!
     */
    public function testInvalidScheme()
    {
        $log = new PublicGraylogLog(['scheme' => 'http']);
        $log->getTransport();
    }

    /**
     * Test getting a publisher class from default configuration.
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
        static::assertSame('info', $message->getLevel());
        static::assertSame('Cy6BWVa5E0', $message->getShortMessage());
        static::assertNull($message->getFullMessage());
        static::assertSame('MSg9BrM4DG', $message->getAdditional('bmC5B27F3R'));
    }

    /**
     * Test creating a GELF message with all flags enabled.
     */
    public function testNoEmptyPostInLongMessage()
    {
        $_SESSION = [
            'edjjLLLg14' => 'G78eIm8UbE'
        ];
        $log = new PublicGraylogLog([
            'append_post' => true
        ]);
        $message = $log->createMessage(LogLevel::CRITICAL, 'oP6MkuApf9');
        static::assertInstanceOf(GelfMessage::class, $message);
        static::assertSame('CakePHP', $message->getFacility());
        static::assertSame(LogLevel::CRITICAL, $message->getLevel());
        static::assertSame('oP6MkuApf9', $message->getShortMessage());
        static::assertSame([], $message->getAllAdditionals());
        static::assertNull($message->getFullMessage());
    }
}
