<?php
/**
 * SCR-OPR-211/212: 確認メール送信済み・メール確認待ち画面.
 *
 * "ログイン画面の主見出し、初回案内、空表示および操作完了表示" では
 * 提供元表記を表示しない（UIデザインガイド§6）ため footer credit を隠す。
 *
 * @var \App\View\AppView $this
 * @var string $email
 */
$this->Html->css('auth', ['block' => 'css']);
$this->set('hideFooterCredit', true);
?>
<div class="auth-page">
    <div class="auth-card">
        <h1>確認メールを送信しました</h1>
        <p><?= h($email) ?> 宛に確認メールを送信しました。メール内のリンクを開いて登録を完了してください。</p>
        <p>リンクの有効期限は発行から60分です。メールが届かない場合は、以下から再送できます（前回の送信から60秒後に再送可能になります）。</p>

        <?= $this->Form->create(null, ['url' => ['action' => 'resendVerification']]) ?>
        <?= $this->Form->hidden('email', ['value' => $email]) ?>
        <?= $this->Form->button('確認メールを再送する', ['class' => 'btn-primary']) ?>
        <?= $this->Form->end() ?>

        <div class="auth-links">
            <a href="<?= $this->Url->build(['action' => 'login']) ?>">ログイン画面へ戻る</a>
        </div>
    </div>
</div>
