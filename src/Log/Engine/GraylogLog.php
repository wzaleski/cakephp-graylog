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
use kbATeam\GraylogUtilities\LogTypes;
use kbATeam\GraylogUtilities\Obfuscator;

/**
 * Class GraylogLog
 * @author Gregor J.
 * @license MIT
 * @link    https://github.com/the-kbA-team/cakephp-graylog.git Repository
 */
class GraylogLog extends BaseLog
{
    /**
     * Loop detection
     * @var bool
     */
    private $loop = false;

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
        'add_file_and_line' => true,
        'append_backtrace' => false,
        'append_session' => false,
        'append_post' => false,
        'append' => [],
        'additional' => [],
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
         * Set log levels.
         */
        $config['levels'] = (new LogTypes($config['levels']))->get();
        /**
         * Translate former append POST flag to new function.
         */
        if ($config['append_post'] === true) {
            $passwordKeys = $config['password_keys'];
            $config['append']['POST'] = static function () use ($passwordKeys) {
                if (!empty($_POST)) {
                    return json_encode(
                        (new Obfuscator($passwordKeys))->obfuscate($_POST),
                        JSON_PRETTY_PRINT
                    );
                }
                return null;
            };
        }
        /**
         * Translate former append session flag to new function.
         */
        if ($config['append_session'] === true) {
            $passwordKeys = $config['password_keys'];
            $config['append']['Session'] = static function () use ($passwordKeys) {
                if (!empty($_SESSION)) {
                    return json_encode(
                        (new Obfuscator($passwordKeys))->obfuscate($_SESSION),
                        JSON_PRETTY_PRINT
                    );
                }
                return null;
            };
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
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->loop === true) {
            return;
        }
        $this->loop = true;
        $this->getPublisher()->publish(
            $this->createMessage($level, $message)
        );
        $this->loop = false;
    }

    /**
     * @return Publisher
     * @throws \LogicException
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
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
     * @throws \InvalidArgumentException
     */
    private function initTransport()
    {
        if ($this->getConfig('scheme', 'udp') === 'udp') {
            return new UdpTransport(
                $this->getConfig('host'),
                $this->getConfig('port'),
                $this->getConfig('chunk_size')
            );
        }
        if ($this->getConfig('scheme') === 'tcp') {
            return new TcpTransport(
                $this->getConfig('host'),
                $this->getConfig('port'),
                $this->getConfig('ssl_options')
            );
        }
        throw new LogicException('Unkown transport scheme for GreyLog!');
    }

    /**
     * Create a GELF message.
     * @param string $level   Message level.
     * @param string $message Message to write.
     * @return GelfMessage
     * @throws \RuntimeException
     */
    protected function createMessage($level, $message)
    {
        $gelfMessage = (new GelfMessage())
            ->setVersion('1.1')
            ->setLevel($level)
            ->setFacility($this->getConfig('facility', 'CakePHP'));
        if (PHP_SAPI !== 'cli' && ($request = Router::getRequest()) !== null) {
            $referer = $request->referer(true);
            if (!empty($referer)) {
                $gelfMessage->setAdditional('http_referer', $referer);
            }
            $gelfMessage->setAdditional('request_uri', $request->getRequestTarget());
        }
        $add_file_and_line = $this->getConfig('add_file_and_line', true) === true;
        /**
         * Append backtrace in case it's not already in the message.
         */
        $append_backtrace = $this->getConfig('append_backtrace', false) === true
                            && strpos($message, 'Trace:') === false;
        /**
         * Create a debug backtrace.
         */
        if ($add_file_and_line || $append_backtrace) {
            $trace = new ClassicBacktrace(
                $this->getConfig('trace_level_offset'),
                $this->getConfig('file_root_dir')
            );
        }

        /**
         * In case the log didn't happen in memory (like with reflections), add
         * the filename and line to the message.
         */
        if ($add_file_and_line && $trace->lastStep('file') !== null) {
            $gelfMessage->setFile($trace->lastStep('file'));
            $gelfMessage->setFile($trace->lastStep('line'));
        }

        /**
         * Append function output to the message.
         */
        foreach ($this->getConfig('append', []) as $key => $function) {
            if (is_callable($function)) {
                $appendString = $function();
                if (!empty($appendString)) {
                    $message .= PHP_EOL . PHP_EOL . $key . ':' . PHP_EOL;
                    $message .= $appendString;
                }
            }
        }

        /**
         * Append backtrace in case it's not already in the message.
         */
        if ($append_backtrace) {
            /**
             * Append backtrace to message.
             */
            $message .= PHP_EOL . PHP_EOL . 'Trace:' . PHP_EOL;
            $message .= $trace->getClassicString();
        }

        /**
         * Set function output as additional field.
         */
        foreach ($this->getConfig('additional', []) as $key => $function) {
            if (is_callable($function)) {
                $gelfMessage->setAdditional($key, (string)$function());
            }
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
}
