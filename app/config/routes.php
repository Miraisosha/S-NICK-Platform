<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
 * So you can use `$this` to reference the application class instance
 * if required.
 */
return function (RouteBuilder $routes): void {
    /*
     * The default class to use for all routes
     *
     * The following route classes are supplied with CakePHP and are appropriate
     * to set as the default:
     *
     * - Route
     * - InflectedRoute
     * - DashedRoute
     *
     * If no call is made to `Router::defaultRouteClass()`, the class used is
     * `Route` (`Cake\Routing\Route\Route`)
     *
     * Note that `Route` does not do any inflections on URLs which will result in
     * inconsistently cased URLs when used with `{plugin}`, `{controller}` and
     * `{action}` markers.
     */
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        /*
         * Here, we are connecting '/' (base path) to a controller called 'Pages',
         * its action called 'display', and we pass a param to select the view file
         * to use (in this case, templates/Pages/home.php)...
         */
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);

        /*
         * ...and connect the rest of 'Pages' controller's URLs.
         */
        $builder->connect('/pages/*', 'Pages::display');

        $builder->connect('/users/register', ['controller' => 'Users', 'action' => 'register']);
        $builder->connect('/users/register/resend', ['controller' => 'Users', 'action' => 'resendVerification']);
        $builder->connect('/users/verify-email', ['controller' => 'Users', 'action' => 'verifyEmail']);
        $builder->connect('/users/login', ['controller' => 'Users', 'action' => 'login']);
        $builder->connect('/users/logout', ['controller' => 'Users', 'action' => 'logout']);
        $builder->connect('/users/forgot-password', ['controller' => 'Users', 'action' => 'forgotPassword']);
        $builder->connect('/users/reset-password', ['controller' => 'Users', 'action' => 'resetPassword']);

        $builder->connect('/dashboard', ['controller' => 'Dashboard', 'action' => 'index']);

        /*
         * Connect catchall routes for all controllers.
         *
         * The `fallbacks` method is a shortcut for
         *
         * ```
         * $builder->connect('/{controller}', ['action' => 'index']);
         * $builder->connect('/{controller}/{action}/*', []);
         * ```
         *
         * It is NOT recommended to use fallback routes after your initial prototyping phase!
         * See https://book.cakephp.org/5/en/development/routing.html#fallbacks-method for more information
         */
        $builder->fallbacks();
    });

    // /api/v1/*: JSON API for the separately-deployed FRONT. Every action is
    // connected explicitly with its HTTP method per
    // docs/specifications/010_SystemArchitecture.md ("APIはCakePHPの自動
    // フォールバックルートへ依存せず...") rather than relying on fallbacks().
    $routes->scope('/api/v1', function (RouteBuilder $builder): void {
        $builder->setRouteClass(DashedRoute::class);

        $builder->post('/users/register', ['controller' => 'Users', 'action' => 'register', 'prefix' => 'Api/V1']);
        $builder->post(
            '/users/resend-verification',
            ['controller' => 'Users', 'action' => 'resendVerification', 'prefix' => 'Api/V1'],
        );
        $builder->post('/users/verify-email', ['controller' => 'Users', 'action' => 'verifyEmail', 'prefix' => 'Api/V1']);
        $builder->post('/users/login', ['controller' => 'Users', 'action' => 'login', 'prefix' => 'Api/V1']);
        $builder->post('/users/logout', ['controller' => 'Users', 'action' => 'logout', 'prefix' => 'Api/V1']);
        $builder->get('/users/me', ['controller' => 'Users', 'action' => 'me', 'prefix' => 'Api/V1']);
        $builder->post(
            '/users/forgot-password',
            ['controller' => 'Users', 'action' => 'forgotPassword', 'prefix' => 'Api/V1'],
        );
        $builder->post(
            '/users/reset-password',
            ['controller' => 'Users', 'action' => 'resetPassword', 'prefix' => 'Api/V1'],
        );
    });
};
