<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Admin;
use App\Service\Admin\AdminAccountService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\Validation\Validation;

/**
 * Creates an admin account (docs/specifications/500_Admin.md §501). There is
 * no self-registration and no SCR-ADM-550 "create another admin" screen yet,
 * so this command is the only way to provision the first (or any later)
 * admin - both for local dev setup and for the one-time production
 * bootstrap over SSH (docs/08_deployment.md).
 *
 * The password is read from the `ADMIN_BOOTSTRAP_PASSWORD` environment
 * variable when set (avoids shell history and works well with
 * `docker compose exec`), or otherwise prompted for on the console. This
 * command has no hidden/masked input available (CakePHP's ConsoleIo has no
 * `askHidden()`), so a visible prompt is an accepted trade-off for a
 * console-only, operator-run bootstrap tool - never expose this over HTTP.
 *
 * Usage: `bin/cake create_admin --email=admin@example.com --name="運営責任者"`
 */
class CreateAdminCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'create_admin';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to build.
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('管理者アカウントを作成します（docs/specifications/500_Admin.md §501）。')
            ->addOption('email', ['required' => true, 'help' => '管理者ログイン用メールアドレス'])
            ->addOption('name', ['required' => true, 'help' => '管理者表示名'])
            ->addOption('role', [
                'default' => Admin::ROLE_SUPER_ADMIN,
                'choices' => [Admin::ROLE_ADMIN, Admin::ROLE_SUPER_ADMIN],
                'help' => '管理者区分（既定: super_admin）',
            ]);

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
        $name = (string)$args->getOption('name');
        $role = (string)$args->getOption('role');

        if (!Validation::email($email)) {
            $io->error('メールアドレスの形式が正しくありません。');

            return static::CODE_ERROR;
        }

        $password = (string)env('ADMIN_BOOTSTRAP_PASSWORD', '');
        if ($password === '') {
            $password = $io->ask('パスワードを入力してください（6〜64文字、半角英数字と記号 ! # $ % & | _ -）:');
        }

        $minLength = (int)Configure::read('PasswordPolicy.minLength');
        $maxLength = (int)Configure::read('PasswordPolicy.maxLength');
        if (mb_strlen($password) < $minLength || mb_strlen($password) > $maxLength) {
            $io->error(sprintf('パスワードは%d〜%d文字で入力してください。', $minLength, $maxLength));

            return static::CODE_ERROR;
        }

        /** @var \App\Model\Table\AdminsTable $adminsTable */
        $adminsTable = $this->fetchTable('Admins');
        $service = new AdminAccountService($adminsTable);

        try {
            $admin = $service->create($email, $name, $password, $role);
        } catch (PersistenceFailedException $e) {
            $io->error('管理者アカウントを作成できませんでした: ' . $e->getMessage());

            return static::CODE_ERROR;
        }

        $io->success(sprintf(
            '管理者アカウントを作成しました: %s (%s, %s)',
            $admin->email,
            $admin->admin_code,
            $admin->role,
        ));

        return static::CODE_SUCCESS;
    }
}
