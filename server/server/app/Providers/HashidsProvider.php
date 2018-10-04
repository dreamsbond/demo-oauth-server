<?php namespace App\Providers;

use App\Container\HashIdsConfigurator;
use Limoncello\Contracts\Provider\ProvidesContainerConfiguratorsInterface;

/**
 * @package App
 */
class HashidsProvider implements ProvidesContainerConfiguratorsInterface
{
    /**
     * @inheritDoc
     */
    public static function getContainerConfigurators(): array
    {
        return [
            HashIdsConfigurator::class,
        ];
    }
}
