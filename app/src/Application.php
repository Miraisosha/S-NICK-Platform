<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App;

use App\Middleware\CorsMiddleware;
use App\Middleware\HostHeaderMiddleware;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Event\EventManagerInterface;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        // By default, does not allow fallback classes.
        FactoryLocator::add(
            'Table',
            (new TableLocator())->allowFallbackClass(false),
        );
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error'), $this))

            // Validate Host header to prevent Host Header Injection attacks.
            // In production, ensures App.fullBaseUrl is configured and validates
            // the incoming Host header against it.
            ->add(new HostHeaderMiddleware())

            // CORS for the separately-deployed FRONT calling the /api/v1/*
            // JSON API (see src/Middleware/CorsMiddleware.php). Placed
            // before routing so an OPTIONS preflight is answered even
            // though it never matches a connected route.
            ->add(new CorsMiddleware())

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))

            // Add routing middleware.
            // If you have a large number of routes connected, turning on routes
            // caching in production could improve performance.
            // See https://github.com/CakeDC/cakephp-cached-routing
            ->add(new RoutingMiddleware($this))

            // Parse various types of encoded request bodies so that they are
            // available as array through $request->getData().
            // https://book.cakephp.org/5/en/controllers/middleware.html#body-parser-middleware
            //
            // Must run before AuthenticationMiddleware: FormAuthenticator
            // reads credentials via $request->getData(), which for a JSON
            // body is only populated once this middleware has parsed it.
            // HTML logins (application/x-www-form-urlencoded) worked even
            // with the reversed order because PHP itself populates $_POST
            // for that content type - only the JSON API login was broken by
            // the original skeleton ordering.
            ->add(new BodyParserMiddleware())

            // Adds identification/authentication information to the request.
            // https://book.cakephp.org/authentication/4/en/index.html
            ->add(new AuthenticationMiddleware($this))

            // Cross Site Request Forgery (CSRF) Protection Middleware
            // https://book.cakephp.org/5/en/security/csrf.html#cross-site-request-forgery-csrf-middleware
            //
            // The /api/v1/* JSON API is exempt: it has no HTML forms to
            // carry a CSRF token, and is protected instead by the
            // CorsMiddleware's strict origin allow-list together with
            // SameSite=Lax session cookies (see docs/specifications/
            // 010_SystemArchitecture.md and the FRONT/API migration plan).
            ->add(
                (new CsrfProtectionMiddleware(['httponly' => true]))
                    ->skipCheckCallback(
                        fn(ServerRequestInterface $request): bool => str_starts_with(
                            $request->getUri()->getPath(),
                            '/api/',
                        ),
                    ),
            );

        return $middlewareQueue;
    }

    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/5/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
        // Allow your Tables to be dependency injected
        //$container->delegate(new \Cake\ORM\Locator\TableContainer());
    }

    /**
     * Register custom event listeners here
     *
     * @param \Cake\Event\EventManagerInterface $eventManager
     * @return \Cake\Event\EventManagerInterface
     * @link https://book.cakephp.org/5/en/core-libraries/events.html#registering-listeners
     */
    public function events(EventManagerInterface $eventManager): EventManagerInterface
    {
        // $eventManager->on(new SomeCustomListenerClass());

        return $eventManager;
    }

    /**
     * Returns a service provider instance.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request
     * @return \Authentication\AuthenticationServiceInterface
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService([
            'unauthenticatedRedirect' => '/users/login',
            'queryParam' => 'redirect',
        ]);

        // cakephp/authentication 4.x builds each authenticator's identifier
        // from an `identifier` sub-config (via IdentifierFactory) rather
        // than a shared, service-level loadIdentifier() call.
        $passwordIdentifier = [
            'className' => 'Authentication.Password',
            'fields' => [
                'username' => 'email',
                'password' => 'password_hash',
            ],
            'resolver' => [
                'className' => 'Authentication.Orm',
                'userModel' => 'Users',
                'finder' => 'loginable',
            ],
            'passwordHasher' => [
                'className' => 'Authentication.Default',
                'hashType' => PASSWORD_ARGON2ID,
                'hashOptions' => (array)Configure::read('PasswordHasher.argon2id'),
            ],
        ];

        $formFields = [
            'username' => 'email',
            'password' => 'password',
        ];

        $service->loadAuthenticator('Authentication.Session');

        // Two Form authenticator instances, one per login endpoint.
        // FormAuthenticator's `loginUrl` only ever checks a single URL
        // (Authentication\UrlChecker\DefaultUrlChecker::check() passes it
        // straight to Router::url() as one route/URL, not a list to try in
        // turn - the "or an array of URLs" in its docblock means an
        // array-style route, not multiple alternatives) - so both the HTML
        // (App\Controller\UsersController) and JSON API
        // (App\Controller\Api\V1\UsersController) login actions need their
        // own authenticator instance rather than sharing one.
        $service->loadAuthenticator('Form', [
            'className' => 'Authentication.Form',
            'identifier' => $passwordIdentifier,
            'loginUrl' => '/users/login',
            'fields' => $formFields,
        ]);
        $service->loadAuthenticator('ApiForm', [
            'className' => 'Authentication.Form',
            'identifier' => $passwordIdentifier,
            'loginUrl' => '/api/v1/users/login',
            'fields' => $formFields,
        ]);

        return $service;
    }
}
