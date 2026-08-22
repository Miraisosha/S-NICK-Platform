<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\User;
use Cake\Core\Configure;
use Cake\Mailer\Mailer;

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
        $url = $this->frontendUrl('/verify-email', $rawToken);

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
        $url = $this->frontendUrl('/reset-password', $rawToken);

        $this->setTo($user->email)
            ->setSubject(__('【Squash Platform】パスワード再設定'))
            ->setViewVars(['user' => $user, 'url' => $url]);
        $this->viewBuilder()->setTemplate('password_reset');

        return $this;
    }

    /**
     * Builds a link into the separately-deployed FRONT app (Configure
     * `Frontend.baseUrl`) rather than a CakePHP/API route: these links are
     * opened directly from an email client, and FRONT is the domain users
     * expect to land on (SCR-OPR-211/213 assume a browser page, not a raw
     * JSON endpoint).
     */
    private function frontendUrl(string $path, string $rawToken): string
    {
        $baseUrl = (string)Configure::read('Frontend.baseUrl');

        return $baseUrl . $path . '?token=' . rawurlencode($rawToken);
    }
}
