<?php namespace App\Container;

use App\Web\Controllers\AuthController;
use App\Web\Controllers\ControllerTrait;
use App\Web\Views;
use Limoncello\Contracts\Application\ApplicationConfigurationInterface;
use Limoncello\Contracts\Application\CacheSettingsProviderInterface;
use Limoncello\Contracts\Application\ContainerConfiguratorInterface;
use Limoncello\Contracts\Container\ContainerInterface as LimoncelloContainerInterface;
use Limoncello\Contracts\Http\RequestStorageInterface;
use Limoncello\Contracts\Passport\PassportAccountManagerInterface;
use Limoncello\Contracts\Routing\RouterInterface;
use Limoncello\Contracts\Session\SessionInterface;
use Limoncello\Contracts\Templates\TemplatesInterface;
use Limoncello\OAuthServer\Contracts\ClientInterface;
use Limoncello\Passport\Adaptors\Generic\Client;
use Limoncello\Passport\Adaptors\Generic\PassportServerIntegration;
use Limoncello\Passport\Contracts\PassportServerIntegrationInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Uri;

/**
 * @package App\Container
 */
class OAuthConfigurator implements ContainerConfiguratorInterface
{
    /** @var callable */
    const CONFIGURATOR = [self::class, self::CONTAINER_METHOD_NAME];

    /**
     * @inheritdoc
     */
    public static function configureContainer(LimoncelloContainerInterface $container): void
    {
        $container[PassportServerIntegrationInterface::class] = function (PsrContainerInterface $container) {
            return new class ($container) extends PassportServerIntegration
            {
                use ControllerTrait;

                public function createAskResourceOwnerForApprovalResponse(
                    string $type,
                    ClientInterface $client,
                    string $redirectUri = null,
                    bool $isScopeModified = false,
                    array $scopeList = null,
                    string $state = null,
                    array $extraParameters = []
                ): ResponseInterface
                {
                    /** @var CacheSettingsProviderInterface $provider */
                    $provider = $this->getContainer()->get(CacheSettingsProviderInterface::class);
                    $originScheme = $provider->getApplicationConfiguration()[ApplicationConfigurationInterface::KEY_APP_ORIGIN_SCHEMA];
                    $originHost = $provider->getApplicationConfiguration()[ApplicationConfigurationInterface::KEY_APP_ORIGIN_HOST];
                    $originPort = $provider->getApplicationConfiguration()[ApplicationConfigurationInterface::KEY_APP_ORIGIN_PORT];

                    $originUri = $originPort === null || $originPort === 80 ? "$originScheme://$host" : "$originScheme://$originHost:$originPort";

                    /** @var PassportAccountManagerInterface $manager */
                    $manager = $this->getContainer()->get(PassportAccountManagerInterface::class);
                    $passport = $manager->getPassport();
                    if ($passport === null) {
                        /** @var RouterInterface $router */
                        $router = $this->getContainer()->get(RouterInterface::class);
                        /** @var UriInterface $signInUri */
                        $signInUri = new Uri($router->get($originUri, AuthController::ROUTE_NAME_SIGN_IN));
                        $newUri = $signInUri->withQuery(http_build_query([
                            'code_login' => true,
                        ]));

                        return new RedirectResponse($newUri);
                    }

                    // remember what was before user's approval
                    /** @var SessionInterface $session */
                    $session = $this->getContainer()->get(SessionInterface::class);
                    $session['oauth-scopes-before-approval'] = [
                        'client-id'           => $client->getIdentifier(),
                        'client-redirect-uri' => $redirectUri,
                        'is-scope-modified'   => $isScopeModified,
                        'initial-scopes'      => $scopeList,
                        'state-from-client'   => $state,
                        'user-id'             => $passport->getUserIdentity(),
                    ];

                    // and show a list of scopes request by the client
                    /** @var Client $client */
                    assert($client instanceof Client);

                    $formatter = static::createFormatter($this->getContainer(), Views::NAMESPACE);
                    $templateName = $formatter->formatMessage(Views::OAUTH_SCOPE_APPROVAL);

                    $parameters = [
                        'clientName'        => $client->getName(),
                        'clientDescription' => $client->getDescription(),
                        'scopes'            => $scopeList,
                    ];

                    /** @var TemplatesInterface $templates */
                    $templates = $this->getContainer()->get(TemplatesInterface::class);
                    $body = $templates->render($templateName, $parameters);

                    return new HtmlResponse($body);
                }
            };
        };
    }
}
