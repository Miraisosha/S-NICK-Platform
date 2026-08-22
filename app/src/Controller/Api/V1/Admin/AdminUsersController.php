<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * JSON API for admin authentication (docs/specifications/500_Admin.md
 * §501). There is no self-registration or password-reset flow here yet -
 * admin accounts are provisioned only via `bin/cake create_admin` (see the
 * implementation plan's judgment call on SCR-ADM-550 being out of scope).
 */
class AdminUsersController extends AppController
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Authentication->addUnauthenticatedActions(['login']);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function login(): Response
    {
        $this->request->allowMethod(['post']);

        $result = $this->Authentication->getResult();

        if ($result === null || !$result->isValid()) {
            return $this->jsonError(
                'invalid_credentials',
                __('メールアドレスまたはパスワードが正しくありません。'),
                401,
            );
        }

        $identity = $this->Authentication->getIdentity();
        $adminId = $identity?->getIdentifier();

        /** @var \App\Model\Entity\Admin $admin */
        $admin = $this->adminsTable()->get($adminId);
        $admin->set('last_login_at', DateTime::now(), ['guard' => false]);
        $this->adminsTable()->saveOrFail($admin);

        return $this->json([
            'id' => $admin->id,
            'email' => $admin->email,
            'name' => $admin->name,
            'role' => $admin->role,
        ]);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function logout(): Response
    {
        $this->request->allowMethod(['post']);

        $this->Authentication->logout();

        return $this->json(['status' => 'logged_out']);
    }

    /**
     * @return \Cake\Http\Response
     */
    public function me(): Response
    {
        $this->request->allowMethod(['get']);

        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->jsonError('unauthenticated', __('ログインしていません。'), 401);
        }

        return $this->json([
            'id' => $identity->getIdentifier(),
            'email' => $identity['email'],
            'name' => $identity['name'],
            'role' => $identity['role'],
        ]);
    }
}
