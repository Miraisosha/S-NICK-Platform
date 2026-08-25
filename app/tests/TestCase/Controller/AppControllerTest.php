<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Http\Session;
use Cake\TestSuite\TestCase;

/**
 * Regression coverage for a session-key collision that silently broke
 * every login: UsersController::login() used to write the security
 * stamp under 'Auth.securityStamp', which - because CakePHP's Session
 * treats a dotted key as a nested path - created `$_SESSION['Auth'] =
 * ['securityStamp' => ...]`. Authentication's SessionAuthenticator
 * persists the real identity under the plain top-level key 'Auth', but
 * only if that key isn't already set. Since our stamp write ran first
 * (inside the same request, before the post-controller persistIdentity()
 * call), it always "claimed" the key first, so the real identity was
 * never actually persisted - every login silently failed to stick past
 * the redirect. IntegrationTestTrait can't catch this class of bug
 * (it doesn't carry session state between separate get()/post() calls),
 * so this test exercises the Session object directly instead.
 */
class AppControllerTest extends TestCase
{
    public function testSecurityStampKeyDoesNotCollideWithAuthSessionKey(): void
    {
        $session = new Session();

        // What SessionAuthenticator::persistIdentity() does at login.
        $session->write('Auth', ['id' => 42, 'email' => 'user@example.com']);

        // What UsersController::login() does right after, in the same request.
        $session->write('AuthSecurityStamp', '2026-08-21T00:00:00+00:00');

        $this->assertSame(['id' => 42, 'email' => 'user@example.com'], $session->read('Auth'));
        $this->assertSame('2026-08-21T00:00:00+00:00', $session->read('AuthSecurityStamp'));
    }
}
