<?php

namespace Tests\kbATeam\CakePhpGraylog;

use Gelf\Message;
use Gelf\Publisher;
use Gelf\Transport\TransportInterface;
use InvalidArgumentException;
use kbATeam\CakePhpGraylog\Log\Engine\GraylogLog;
use Cake\Utility\Hash;

/**
 * Class PublicGraylogLog
 * Class with the sole purpose to make the config array public for testing.
 */
class PublicGraylogLog extends GraylogLog
{
    /**
     * Get log engine config key or the whole config array in case key is null.
     * @param string|null $key
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function getMyConfig(string $key = null)
    {
        if ($key === null) {
            return $this->_config;
        }
        return Hash::get($this->_config, $key);
    }

    /**
     * @inheritDoc
     * @noinspection PhpOverridingMethodVisibilityInspection
     */
    public function getPublisher(): Publisher
    {
        return parent::getPublisher();
    }

    /**
     * @inheritDoc
     * @noinspection PhpOverridingMethodVisibilityInspection
     */
    public function getTransport(): TransportInterface
    {
        return parent::getTransport();
    }

    /**
     * @inheritDoc
     * @noinspection PhpOverridingMethodVisibilityInspection
     */
    public function createMessage(string $level, string $message): Message
    {
        return parent::createMessage($level, $message);
    }
}
