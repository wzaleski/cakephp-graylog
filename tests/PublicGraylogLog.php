<?php

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
    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->_config;
        }
        return Hash::get($this->_config, $key);
    }

    /**
     * @inheritDoc
     */
    public function getPublisher()
    {
        return parent::getPublisher();
    }

    /**
     * @inheritDoc
     */
    public function getTransport()
    {
        return parent::getTransport();
    }

    /**
     * @inheritDoc
     */
    public function createMessage($type, $message)
    {
        return parent::createMessage($type, $message);
    }

    /**
     * @inheritDoc
     */
    public function obscurePasswords(array $data)
    {
        return parent::obscurePasswords($data);
    }
}
