<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\AccountActionToken;
use App\Model\Table\AccountActionTokensTable;
use App\Model\Table\UsersTable;
use Cake\Chronos\Chronos;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

class AccountActionTokensTableTest extends TestCase
{
    private AccountActionTokensTable $Tokens;
    private UsersTable $Users;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Tokens = $this->fetchTable('AccountActionTokens');
        $this->Users = $this->fetchTable('Users');

        $user = $this->Users->newEntity([
            'account_number' => 'U' . bin2hex(random_bytes(4)),
            'email' => 'token-test-' . bin2hex(random_bytes(4)) . '@example.com',
            'password_hash' => 'x',
        ], ['accessibleFields' => ['*' => true]]);
        $this->userId = $this->Users->saveOrFail($user)->id;
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
        unset($this->Tokens, $this->Users);
        parent::tearDown();
    }

    public function testIssueReturnsRawTokenAndPersistsOnlyItsHash(): void
    {
        $issued = $this->Tokens->issue($this->userId, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);

        $this->assertNotEmpty($issued['token']);
        $this->assertSame(hash('sha256', $issued['token']), $issued['entity']->token_hash);
    }

    public function testFindValidByRawTokenReturnsIssuedToken(): void
    {
        $issued = $this->Tokens->issue($this->userId, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);

        $found = $this->Tokens->findValidByRawToken(
            $issued['token'],
            AccountActionToken::PURPOSE_EMAIL_VERIFICATION,
        );

        $this->assertNotNull($found);
        $this->assertSame($issued['entity']->id, $found->id);
    }

    public function testFindValidByRawTokenReturnsNullForWrongPurpose(): void
    {
        $issued = $this->Tokens->issue($this->userId, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);

        $found = $this->Tokens->findValidByRawToken(
            $issued['token'],
            AccountActionToken::PURPOSE_PASSWORD_RESET,
        );

        $this->assertNull($found);
    }

    public function testFindValidByRawTokenReturnsNullAfterExpiry(): void
    {
        Chronos::setTestNow(DateTime::now());
        $issued = $this->Tokens->issue($this->userId, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);

        Chronos::setTestNow(DateTime::now()->addMinutes(61));

        $found = $this->Tokens->findValidByRawToken(
            $issued['token'],
            AccountActionToken::PURPOSE_EMAIL_VERIFICATION,
        );

        $this->assertNull($found);
    }

    public function testMarkUsedPreventsFurtherLookup(): void
    {
        $issued = $this->Tokens->issue($this->userId, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);
        $this->Tokens->markUsed($issued['entity']);

        $found = $this->Tokens->findValidByRawToken(
            $issued['token'],
            AccountActionToken::PURPOSE_EMAIL_VERIFICATION,
        );

        $this->assertNull($found);
    }

    public function testInvalidateActiveInvalidatesPriorUnusedToken(): void
    {
        $first = $this->Tokens->issue($this->userId, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);
        $this->Tokens->invalidateActive($this->userId, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);

        $found = $this->Tokens->findValidByRawToken(
            $first['token'],
            AccountActionToken::PURPOSE_EMAIL_VERIFICATION,
        );

        $this->assertNull($found);
    }

    public function testInvalidateActiveDoesNotAffectOtherPurpose(): void
    {
        $resetToken = $this->Tokens->issue($this->userId, AccountActionToken::PURPOSE_PASSWORD_RESET);
        $this->Tokens->invalidateActive($this->userId, AccountActionToken::PURPOSE_EMAIL_VERIFICATION);

        $found = $this->Tokens->findValidByRawToken(
            $resetToken['token'],
            AccountActionToken::PURPOSE_PASSWORD_RESET,
        );

        $this->assertNotNull($found);
    }
}
