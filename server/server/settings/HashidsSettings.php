<?php namespace Settings;

/**
 * @package App
 */
class HashidsSettings implements HashidsSettingsInterface
{
    /**
     * @inheritDoc
     */
    final public function get(array $appConfig): array
    {
        return $this->getSettings();
    }

    /**
     * @return array
     */
    protected function getSettings(): array
    {
        return [
            static::KEY_HASHIDS_SALT    => 'Limoncello',
            static::KEY_HASHIDS_PADDING => 5,
        ];
    }
}
