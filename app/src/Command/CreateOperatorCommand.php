<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\Auth\Exception\WeakPasswordException;
use App\Service\Auth\OperatorAccountService;
use App\Service\Auth\PasswordPolicy;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\Validation\Validation;

/**
 * Creates an already-verified operator account (docs/specifications/
 * 200_Operator.md SCR-OPR-211), skipping the normal email-confirmation
 * flow. For local development and manual testing only - self-registration
 * remains the only user-facing way to create an account, and this
 * shouldn't be exposed to end users or run against production.
 *
 * Like `create_admin`, the password is read from the
 * `OPERATOR_BOOTSTRAP_PASSWORD` environment variable when set, or
 * otherwise prompted for on the console (visible input - see
 * CreateAdminCommand's docblock for why that trade-off is accepted here).
 *
 * Usage: `bin/cake create_operator --email=operator@example.com`
 */
class CreateOperatorCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'create_operator';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to build.
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('確認済みの運営者アカウントを作成します（SCR-OPR-211、開発・確認用）。')
            ->addOption('email', ['required' => true, 'help' => 'ログイン用メールアドレス']);

        return $parser;
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int|null
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $email = (string)$args->getOption('email');

        if (!Validation::email($email)) {
            $io->error('メールアドレスの形式が正しくありません。');

            return static::CODE_ERROR;
        }

        $password = (string)env('OPERATOR_BOOTSTRAP_PASSWORD', '');
        if ($password === '') {
            $password = $io->ask('パスワードを入力してください（6〜64文字、半角英数字と記号 ! # $ % & | _ -）:');
        }

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');
        $service = new OperatorAccountService($usersTable, new PasswordPolicy());

        try {
            $user = $service->create($email, $password);
        } catch (WeakPasswordException $e) {
            $io->error($this->weakPasswordMessage($e));

            return static::CODE_ERROR;
        } catch (PersistenceFailedException $e) {
            $io->error('運営者アカウントを作成できませんでした: ' . $e->getMessage());

            return static::CODE_ERROR;
        }

        $io->success(sprintf('運営者アカウントを作成しました: %s (%s)', $user->email, $user->account_number));

        return static::CODE_SUCCESS;
    }

    /**
     * Mirrors Api\V1\UsersController::weakPasswordError()'s message
     * mapping for the same reason codes.
     *
     * @param \App\Service\Auth\Exception\WeakPasswordException $e The password-policy violation.
     * @return string
     */
    private function weakPasswordMessage(WeakPasswordException $e): string
    {
        $minLength = (string)Configure::read('PasswordPolicy.minLength');
        $maxLength = (string)Configure::read('PasswordPolicy.maxLength');

        $messages = [
            WeakPasswordException::TOO_SHORT => __('パスワードは{0}文字以上で入力してください。', $minLength),
            WeakPasswordException::TOO_LONG => __('パスワードは{0}文字以内で入力してください。', $maxLength),
            WeakPasswordException::INVALID_CHARSET => __('パスワードに使用できるのは半角英数字と記号 !#$%&|_- のみです。'),
            WeakPasswordException::DERIVED_FROM_EMAIL => __('メールアドレスから推測できるパスワードは使用できません。'),
            WeakPasswordException::SEQUENTIAL_OR_REPEATED => __('単純な連番や同一文字だけのパスワードは使用できません。'),
        ];

        return $messages[$e->getReasonCode()] ?? __('入力されたパスワードは使用できません。');
    }
}
