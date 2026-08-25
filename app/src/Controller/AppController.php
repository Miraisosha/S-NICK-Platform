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
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Model\Table\UsersTable;
use Authentication\Authenticator\SessionAuthenticator;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');

        // `requireIdentity` defaults to true (deny unless the action opts
        // out via addUnauthenticatedActions()), which would silently make
        // every controller - including public-facing ones like Pages -
        // require a login. Access control per controller/area is not part
        // of this OPR-210 auth increment, so require it to be opted into
        // explicitly (e.g. a future operator-area controller) rather than
        // opted out of everywhere.
        $this->loadComponent('Authentication.Authentication', [
            'requireIdentity' => false,
        ]);

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/5/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    /**
     * Forces logout when the identity's security stamp (`users.sessions_invalidated_at`)
     * no longer matches the value recorded in the session at login time.
     *
     * This is how a password reset invalidates other active sessions
     * (see SCR-OPR-213) without a full multi-device session store.
     *
     * Only applies to an identity that was resolved from the persisted
     * session (a returning, already-logged-in visitor). A fresh login
     * that just succeeded via the Form authenticator in this very request
     * is deliberately left alone: `UsersController::login()` is the one
     * that writes today's stamp into the session, and it does so only
     * after this method has already run.
     *
     * Does not apply to `/api/v1/admin/*` requests: admins authenticate via
     * a completely separate identity source (the `admins` table, session
     * key `AdminAuth` - see Application::getAdminAuthenticationService())
     * that has no `sessions_invalidated_at` security-stamp concept. Without
     * this guard, an authenticated admin identity would be looked up in
     * `usersTable()`, find no matching row, and get force-logged-out on
     * every request.
     *
     * @param \Cake\Event\EventInterface<static> $event The beforeFilter event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        if (str_starts_with($this->request->getUri()->getPath(), '/api/v1/admin/')) {
            return;
        }

        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return;
        }

        $provider = $this->Authentication->getAuthenticationService()->getAuthenticationProvider();
        if (!$provider instanceof SessionAuthenticator) {
            return;
        }

        $session = $this->request->getSession();
        $sessionStamp = $session->read('AuthSecurityStamp');
        $userId = $identity->getIdentifier();

        $user = $userId === null ? null : $this->usersTable()->find()
            ->select(['id', 'sessions_invalidated_at'])
            ->where(['id' => $userId])
            ->first();

        $currentStamp = $user?->sessions_invalidated_at?->format(DATE_ATOM);

        if ($user === null || ($currentStamp !== null && $currentStamp !== $sessionStamp)) {
            $this->Authentication->logout();
            $session->destroy();

            if (str_starts_with($this->request->getUri()->getPath(), '/api/')) {
                $body = [
                    'error' => [
                        'code' => 'session_invalidated',
                        'message' => __('セッションが無効になりました。再度ログインしてください。'),
                    ],
                ];
                $event->setResult(
                    $this->response
                        ->withStatus(401)
                        ->withType('application/json')
                        ->withStringBody((string)json_encode($body, JSON_UNESCAPED_UNICODE)),
                );

                return;
            }

            $event->setResult($this->redirect(['controller' => 'Users', 'action' => 'login']));
        }
    }

    /**
     * @return \App\Model\Table\UsersTable
     */
    protected function usersTable(): UsersTable
    {
        /** @var \App\Model\Table\UsersTable */
        return $this->fetchTable('Users');
    }
}
