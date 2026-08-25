<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Auth;

use App\Service\Auth\Exception\WeakPasswordException;
use App\Service\Auth\PasswordPolicy;
use Cake\TestSuite\TestCase;

class PasswordPolicyTest extends TestCase
{
    private PasswordPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PasswordPolicy();
    }

    public function testTooShortIsRejected(): void
    {
        $this->assertFalse($this->policy->isLengthValid('abc12'));
    }

    public function testMinimumLengthIsAccepted(): void
    {
        $this->assertTrue($this->policy->isLengthValid('a1B2c3'));
    }

    public function testMaximumLengthIsAccepted(): void
    {
        $this->assertTrue($this->policy->isLengthValid(str_repeat('aB3', 21) . 'x'));
    }

    public function testTooLongIsRejected(): void
    {
        $this->assertFalse($this->policy->isLengthValid(str_repeat('a', 65)));
    }

    public function testAllowedSymbolsAreAccepted(): void
    {
        $this->assertTrue($this->policy->isCharsetValid('Ab1!#$%&|_-'));
    }

    public function testDisallowedSymbolIsRejected(): void
    {
        $this->assertFalse($this->policy->isCharsetValid('Ab1@example'));
    }

    public function testFullWidthCharactersAreRejected(): void
    {
        $this->assertFalse($this->policy->isCharsetValid('パスワード123'));
    }

    public function testPasswordEqualToEmailIsDerived(): void
    {
        $this->assertTrue($this->policy->isDerivedFromEmail('user@example.com', 'user@example.com'));
    }

    public function testPasswordEqualToLocalPartIsDerived(): void
    {
        $this->assertTrue($this->policy->isDerivedFromEmail('User', 'user@example.com'));
    }

    public function testUnrelatedPasswordIsNotDerived(): void
    {
        $this->assertFalse($this->policy->isDerivedFromEmail('Xk7!qpLm', 'user@example.com'));
    }

    public function testRepeatedCharacterIsRejected(): void
    {
        $this->assertTrue($this->policy->isSequentialOrRepeated('aaaaaa'));
    }

    public function testAscendingSequenceIsRejected(): void
    {
        $this->assertTrue($this->policy->isSequentialOrRepeated('123456'));
    }

    public function testDescendingSequenceIsRejected(): void
    {
        $this->assertTrue($this->policy->isSequentialOrRepeated('fedcba'));
    }

    public function testNonSequentialPasswordIsAccepted(): void
    {
        $this->assertFalse($this->policy->isSequentialOrRepeated('Xk7!qpLm'));
    }

    public function testAssertAcceptablePassesForAGoodPassword(): void
    {
        $this->policy->assertAcceptable('Xk7!qpLm', 'user@example.com');
        $this->addToAssertionCount(1);
    }

    public function testAssertAcceptableThrowsForShortPassword(): void
    {
        $this->expectException(WeakPasswordException::class);
        $this->policy->assertAcceptable('a1B2c', 'user@example.com');
    }

    public function testAssertAcceptableReportsTooShortReasonCode(): void
    {
        try {
            $this->policy->assertAcceptable('a1B2c', 'user@example.com');
            $this->fail('Expected exception was not thrown.');
        } catch (WeakPasswordException $e) {
            $this->assertSame(WeakPasswordException::TOO_SHORT, $e->getReasonCode());
        }
    }

    public function testAssertAcceptableThrowsForDerivedPassword(): void
    {
        $this->expectException(WeakPasswordException::class);
        $this->policy->assertAcceptable('user@example.com', 'user@example.com');
    }
}
