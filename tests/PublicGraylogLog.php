<?php

namespace Tests\kbATeam\CakePhpGraylog;

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
     * @param string $key
     * @return array|mixed
     */
    public function getMyConfig($key = null)
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
    public function createMessage($level, $message)
    {
        return parent::createMessage($level, $message);
    }

    /**
     * @inheritDoc
     */
    public function obscurePasswords(array $data)
    {
        return parent::obscurePasswords($data);
    }
}
