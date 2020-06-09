<?php

namespace kbATeam\CakePhpGraylog\Log\Engine;

use Cake\Log\Engine\BaseLog;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Gelf\Message as GelfMessage;
use Gelf\Publisher;
use Gelf\Transport\SslOptions;
use Gelf\Transport\TcpTransport;
use Gelf\Transport\TransportInterface;
use Gelf\Transport\UdpTransport;
use kbATeam\PhpBacktrace\ClassicBacktrace;
use LogicException;
use Psr\Log\LogLevel;

/**
 * Class GraylogLog
 * @author Gregor J.
 * @license MIT
 * @link    https://github.com/the-kbA-team/cakephp-graylog.git Repository
 */
class GraylogLog extends BaseLog
{
    /**
     * @var array Configuration array containing sane defaults.
     */
    protected $_config = [
        'scheme' => 'udp',
        'host' => '127.0.0.1',
        'port' => 12201,
        'chunk_size' => UdpTransport::CHUNK_SIZE_LAN,
        'ssl_options' => null,
        'facility' => 'CakePHP',
        'append_backtrace' => true,
        'append_session' => true,
        'append_post' => true,
        /**
         * Start backtrace 3 steps back, assuming you use CakeLog::error() or
         * Model::log() and not CakeLog::write() directly.
         */
        'trace_level_offset' => 3,
        'file_root_dir' => null,
        'password_keys' => [
            'password',
            'new_password',
            'old_password',
            'current_password'
        ],
        'levels' => []
    ];

    /**
     * @var array Array of allowed log levels.
     */
    protected static $allowedLevels = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG
    ];

    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        $config = Hash::merge($this->_config, $config);
        /**
         * Ensure that the SSL options are an instance of SslOptions.
         */
        if (!is_object($config['ssl_options'])
            || !$config['ssl_options'] instanceof SslOptions
        ) {
            $config['ssl_options'] = null;
        }
        /**
         * In case an URL has been defined, parse that url and merge the result
         * with existing config.
         */
        if (array_key_exists('url', $config)) {
            $config = Hash::merge($config, parse_url($config['url']));
        }
        /**
         * URL scheme strings are expected to be lower case in this class.
         */
        $config['scheme'] = strtolower($config['scheme']);
        /**
         * Ensure that the levels array actually is an array.
         */
        if (!is_array($config['levels'])) {
            $config['levels'] = [];
        }
        /**
         * Remove all levels that are not PSR-3.
         */
        foreach ($config['levels'] as $key => $level) {
            if (!in_array($level, static::$allowedLevels, true)) {
                unset($config['levels'][$key]);
            }
        }
        /**
         * Enable all levels in case levels are empty.
         */
        if ($config['levels'] === []) {
            $config['levels'] = static::$allowedLevels;
        }
        parent::__construct($config);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed   $level
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function log($level, $message, array $context = [])
    {
        $this->getPublisher()->publish(
            $this->createMessage($level, $message)
        );
    }

    /**
     * @return Publisher
     * @throws \LogicException
     */
    protected function getPublisher()
    {
        if ($this->publisher === null) {
            $this->publisher = new Publisher($this->getTransport());
        }
        return $this->publisher;
    }

    /**
     * @return TransportInterface
     * @throws \LogicException
     */
    protected function getTransport()
    {
        if ($this->transport === null) {
            $this->transport = $this->initTransport();
        }
        return $this->transport;
    }

    /**
     * Initialize the transport class for sending greylog messages.
     * @return TransportInterface
     * @throws \LogicException
     */
    private function initTransport()
    {
        if ($this->_config['scheme'] === 'udp') {
            return new UdpTransport(
                $this->_config['host'],
                $this->_config['port'],
                $this->_config['chunk_size']
            );
        }
        if ($this->_config['scheme'] === 'tcp') {
            return new TcpTransport(
                $this->_config['host'],
                $this->_config['port'],
                $this->_config['ssl_options']
            );
        }
        throw new LogicException('Unkown transport scheme for GreyLog!');
    }

    /**
     * Create a GELF message.
     * @param string $level   Message level.
     * @param string $message Message to write.
     * @return GelfMessage
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function createMessage($level, $message)
    {
        $gelfMessage = (new GelfMessage())
            ->setVersion('1.1')
            ->setLevel($level)
            ->setFacility($this->_config['facility']);
        if (PHP_SAPI !== 'cli' && ($request = Router::getRequest()) !== null) {
            $referer = $request->referer(true);
            if (!empty($referer)) {
                $gelfMessage->setAdditional('http_referer', $referer);
            }
            $gelfMessage->setAdditional('request_uri', $request->getRequestTarget());
        }
        /**
         * Create a debug backtrace.
         */
        $trace = new ClassicBacktrace($this->getConfig('trace_level_offset'), $this->getConfig('file_root_dir'));

        /**
         * In case the log didn't happen in memory (like with reflections), add
         * the filename and line to the message.
         */
        if ($trace->lastStep('file') !== null) {
            $gelfMessage->setFile($trace->lastStep('file'));
            $gelfMessage->setFile($trace->lastStep('line'));
        }

        /**
         * Append backtrace in case it's not already in the message.
         */
        if ($this->_config['append_backtrace'] === true
            && strpos($message, 'Trace:') === false
        ) {
            /**
             * Append backtrace to message.
             */
            $message .= PHP_EOL . PHP_EOL . 'Trace:' . PHP_EOL;
            $message .= $trace->getClassicString();
        }

        /**
         * Append POST variables to message.
         */
        if ($this->_config['append_post'] === true && !empty($_POST)) {
            $message .= PHP_EOL . PHP_EOL . 'POST:' . PHP_EOL;
            $message .= json_encode(
                $this->obscurePasswords($_POST),
                JSON_PRETTY_PRINT
            );
        }

        /**
         * Append session variables to message.
         */
        if ($this->_config['append_session'] === true
            && isset($_SESSION)
            && !empty($_SESSION)
        ) {
            $message .= PHP_EOL . PHP_EOL . 'Session:' . PHP_EOL;
            $message .= json_encode(
                $this->obscurePasswords($_SESSION),
                JSON_PRETTY_PRINT
            );
        }

        /**
         * Tokenize message by line breaks and set the first line of the
         * message as short message.
         */
        $shortMessage = strtok($message, "\r\n");

        /**
         * Send only the short message in case short and full message are the same.
         */
        if ($shortMessage === $message) {
            return $gelfMessage->setShortMessage($shortMessage);
        }
        return $gelfMessage
            ->setShortMessage($shortMessage)
            ->setFullMessage($message);
    }

    /**
     * Replace password(s) in data array.
     * @param array $data
     * @return array
     */
    protected function obscurePasswords(array $data)
    {
        array_walk_recursive(
            $data,
            function (&$contents, $key) {
                if (isset($contents)
                    && is_string($contents)
                    && in_array(strtolower($key), $this->_config['password_keys'], true)
                    && trim($contents) !== ''
                ) {
                    $contents = '********';
                }
            }
        );
        return $data;
    }
}
