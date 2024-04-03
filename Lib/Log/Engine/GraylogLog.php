<?php

use Gelf\Message as GelfMessage;
use Gelf\Publisher;
use Gelf\Transport\IgnoreErrorTransportWrapper;
use Gelf\Transport\SslOptions;
use Gelf\Transport\TcpTransport;
use Gelf\Transport\TransportInterface;
use Gelf\Transport\AbstractTransport;
use Gelf\Transport\UdpTransport;
use kbATeam\GraylogUtilities\LogTypes;
use kbATeam\GraylogUtilities\Obfuscator;
use kbATeam\PhpBacktrace\ClassicBacktrace;

App::uses('BaseLog', 'Log/Engine');
App::uses('Router', 'Routing');

/**
 * Class GraylogLog
 * @author  Gregor J.
 * @license MIT
 * @link    https://github.com/the-kbA-team/cakephp-graylog.git Repository
 */
class GraylogLog extends BaseLog
{
    /**
     * Loop detection.
     * @var bool
     */
    private $loop = false;

    /**
     * @var array<mixed> Configuration array containing sane defaults.
     */
    protected $_config = [
        'scheme' => 'udp',
        'host' => '127.0.0.1',
        'port' => 12201,
        'ignore_transport_errors' => true,
        'chunk_size' => UdpTransport::CHUNK_SIZE_LAN,
        'ssl_options' => null,
        'facility' => 'CakePHP',
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
        'types' => []
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
         * Set log types.
         */
        $config['types'] = (new LogTypes($config['types']))->get();
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
        /**
         * Configure parent class.
         */
        parent::__construct($config);
    }

    /**
     * Write method to handle writes being made to the Logger
     *
     * @param string $type    Message type.
     * @param string $message Message to write.
     * @return void
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function write($type, $message)
    {
        if ($this->loop === true) {
            return;
        }
        $this->loop = true;
        $this->getPublisher()->publish(
            $this->createMessage($type, $message)
        );
        $this->loop = false;
    }

    /**
     * @return Publisher
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    protected function getPublisher(): Publisher
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
    protected function getTransport(): TransportInterface
    {
        if ($this->transport === null) {
            $this->transport = $this->initTransport();
        }
        return $this->transport;
    }

    /**
     * Initialize the transport and wrap it into a class ignoring transport
     * errors depending on the configuration.
     *
     * @return TransportInterface
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    private function initTransport(): TransportInterface
    {
        if ($this->_config['ignore_transport_errors'] === false) {
            return $this->buildTransport();
        }
        return new IgnoreErrorTransportWrapper($this->buildTransport());
    }

    /**
     * Initialize the transport class for sending greylog messages.
     * @return AbstractTransport
     * @throws \LogicException Connection scheme configuration error.
     * @throws \InvalidArgumentException UdpTransport or TcpTransport config errors.
     */
    private function buildTransport(): AbstractTransport
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
        throw new LogicException('Unknown transport scheme for GreyLog!');
    }

    /**
     * Create a GELF message.
     * @param string $type    Message type.
     * @param string $message Message to write.
     * @return GelfMessage
     * @throws \RuntimeException
     */
    protected function createMessage(string $type, string $message): GelfMessage
    {
        $gelfMessage = (new GelfMessage())
            ->setVersion('1.1')
            ->setLevel($type)
            ->setFacility($this->_config['facility']);
        if (PHP_SAPI !== 'cli' && ($request = Router::getRequest()) !== null) {
            $referer = $request->referer(true);
            if (!empty($referer)) {
                $gelfMessage->setAdditional('http_referer', $referer);
            }
            $gelfMessage->setAdditional('request_uri', $request->url);
        }
        /**
         * Append backtrace in case it's not already in the message.
         */
        $append_backtrace = $this->_config['append_backtrace'] === true
                            && strpos($message, 'Trace:') === false;
        /**
         * Create a debug backtrace.
         */
        $trace = null;
        if ($append_backtrace) {
            $trace = new ClassicBacktrace(
                $this->_config['trace_level_offset'],
                $this->_config['file_root_dir']
            );
        }

        /**
         * Append function output to the message.
         */
        foreach ($this->_config['append'] as $key => $function) {
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
        if ($append_backtrace && (null !== $trace)) {
            /**
             * Append backtrace to message.
             */
            $message .= PHP_EOL . PHP_EOL . 'Trace:' . PHP_EOL;
            $message .= $trace->getClassicString();
        }

        /**
         * Set function output as additional field.
         */
        foreach ($this->_config['additional'] as $key => $function) {
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
