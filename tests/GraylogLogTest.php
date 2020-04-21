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
        static::assertTrue($log->getConfig('append_backtrace'));
        static::assertTrue($log->getConfig('append_session'));
        static::assertTrue($log->getConfig('append_post'));
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
        $log = new PublicGraylogLog(['types' => ['error', 'warning', 'i5A64FtlPt']]);
        static::assertSame(['error', 'warning'], $log->getConfig('types'));
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
     * Test creating a GELF message with default settings.
     */
    public function testCreatingMessageWithDefaults()
    {
        $_POST = [
            'PAYy2EKmuW' => 'E8RUOjsjAn'
        ];
        $_SESSION = [
            'FDSa5d3EAq' => [
                'Z00UBd2lf9' => 'x6jETf91v8'
            ]
        ];
        $log = new PublicGraylogLog();
        $message = $log->createMessage('debug', 'mnfiXQoolR');
        static::assertInstanceOf(GelfMessage::class, $message);
        static::assertSame('CakePHP', $message->getFacility());
        static::assertSame('debug', $message->getLevel());
        static::assertSame('mnfiXQoolR', $message->getShortMessage());
        static::assertSame([
            'http_referer' => null,
            'request_uri' => null
        ], $message->getAllAdditionals());
        static::assertContains('POST:', $message->getFullMessage());
        static::assertContains('Session:', $message->getFullMessage());
        static::assertContains('Trace:', $message->getFullMessage());
    }

    /**
     * Test creating a GELF message without any appended debug information.
     */
    public function testShortMessage()
    {
        $log = new PublicGraylogLog([
            'append_backtrace' => false,
            'append_session' => false,
            'append_post' => false
        ]);
        $message = $log->createMessage('info', 'oIEUMcF1Ce');
        static::assertSame('info', $message->getLevel());
        static::assertSame('oIEUMcF1Ce', $message->getShortMessage());
        static::assertNull($message->getFullMessage());
    }

    /**
     * Test obscuring passwords.
     */
    public function testObscuringPasswords()
    {
        $log = new PublicGraylogLog();
        $result = $log->obscurePasswords([
            'new_password' => null,
            'hQCJnsPUkD' => [
                'YwbEbRTYK7' => 'lHsnKC4GLN',
                'password' => 'AQeb4yDHAd',
                'old_password' => false,
                'current_password' => ''
            ]
        ]);
        static::assertSame([
            'new_password' => null,
            'hQCJnsPUkD' => [
                'YwbEbRTYK7' => 'lHsnKC4GLN',
                'password' => '********',
                'old_password' => false,
                'current_password' => ''
            ]
        ], $result);
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
}
