<?php namespace Settings;

use Limoncello\Contracts\Settings\SettingsInterface;

/**
 * Interface HashidsSettingsInterface
 *
 * @package Settings
 */
interface HashidsSettingsInterface extends SettingsInterface
{
    /** Settings key  */
    const KEY_HASHIDS_SALT = 0;

    /** Settings key  */
    const KEY_HASHIDS_PADDING = self::KEY_HASHIDS_SALT + 1;

    /** Settings key  */
    const KEY_LAST = self::KEY_HASHIDS_PADDING;
}
