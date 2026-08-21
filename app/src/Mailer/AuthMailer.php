<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\User;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;

/**
 * Sends the SCR-OPR-211/213 account emails: registration email confirmation
 * and password reset. The raw token is only ever handled here (embedded in
 * the emailed link) and in the issuing Service\Auth\* class — it is never
 * persisted or logged (SCR-OPR-211 §164 "確認・再設定トークンの保存").
 */
class AuthMailer extends Mailer
{
    /**
     * @param \App\Model\Entity\User $user The user to email.
     * @param string $rawToken The raw (unhashed) verification token.
     * @return static
     */
    public function verificationEmail(User $user, string $rawToken): static
    {
        $url = Router::url([
            'controller' => 'Users',
            'action' => 'verifyEmail',
            '?' => ['token' => $rawToken],
        ], true);

        $this->setTo($user->email)
            ->setSubject(__('【Squash Platform】メールアドレスの確認'))
            ->setViewVars(['user' => $user, 'url' => $url]);
        $this->viewBuilder()->setTemplate('verification');

        return $this;
    }

    /**
     * @param \App\Model\Entity\User $user The user to email.
     * @param string $rawToken The raw (unhashed) reset token.
     * @return static
     */
    public function passwordResetEmail(User $user, string $rawToken): static
    {
        $url = Router::url([
            'controller' => 'Users',
            'action' => 'resetPassword',
            '?' => ['token' => $rawToken],
        ], true);

        $this->setTo($user->email)
            ->setSubject(__('【Squash Platform】パスワード再設定'))
            ->setViewVars(['user' => $user, 'url' => $url]);
        $this->viewBuilder()->setTemplate('password_reset');

        return $this;
    }
}
