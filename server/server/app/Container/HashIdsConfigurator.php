<?php namespace App\Container;

use Hashids\Hashids;
use Hashids\HashidsInterface;
use Limoncello\Contracts\Commands\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Settings\SettingsInterface;
use Limoncello\Contracts\Settings\SettingsProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Settings\HashidsSettings as C;

/**
 * @package App
 */
class HashIdsConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const CONFIGURATOR = [self::class, self::CONTAINER_METHOD_NAME];

    /**
     * @inheritDoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $container[HashidsInterface::class] = function (PsrContainerInterface $container): HashidsInterface {
            /** @var SettingsProviderInterface $provider */
            $provider = $container->get(SettingsProviderInterface::class);

            /** @var SettingsInterface $settings */
            $settings = $provider->get(C::class);
            $hashids = new Hashids($settings[C::KEY_HASHIDS_SALT], $settings[C::KEY_HASHIDS_PADDING]);

            return $hashids;
        };
    }
}
