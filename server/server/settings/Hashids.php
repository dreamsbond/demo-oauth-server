<?php namespace Settings;

use Dotenv\Dotenv;
use Limoncello\Contracts\Settings\SettingsInterface;

/**
 * @package App
 */
class Hashids extends HashidsSettings
{
    /**
     * @inheritDoc
     */
    protected function getSettings(): array
    {
        (new Dotenv(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..'])))->load();

        return [
                static::KEY_HASHIDS_SALT    => getenv('HASHIDS_SALT'),
                static::KEY_HASHIDS_PADDING => getenv('HASHIDS_PADDING'),
            ] + parent::getSettings();
    }
}
