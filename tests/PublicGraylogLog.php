<?php

use Gelf\Publisher;
use Gelf\Transport\TransportInterface;
use Gelf\Message as GelfMessage;

/**
 * Class PublicGraylogLog
 * Class with the sole purpose to make the config array public for testing.
 */
class PublicGraylogLog extends GraylogLog
{
    /**
     * Get log engine config key or the whole config array in case key is null.
     * @param string $key
     * @return array|mixed
     */
    public function getConfig(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->_config;
        }
        return Hash::get($this->_config, $key);
    }

    /**
     * @inheritDoc
     */
    public function getPublisher(): Publisher
    {
        return parent::getPublisher();
    }

    /**
     * @inheritDoc
     */
    public function getTransport(): TransportInterface
    {
        return parent::getTransport();
    }

    /**
     * @inheritDoc
     */
    public function createMessage(string $type, string $message): GelfMessage
    {
        return parent::createMessage($type, $message);
    }
}
