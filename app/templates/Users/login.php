<?php
/**
 * SCR-OPR-214 ログイン認証.
 *
 * "ログイン画面の主見出し...では提供元表記を表示しない"（UIデザインガイド§6）。
 *
 * @var \App\View\AppView $this
 */
$this->Html->css('auth', ['block' => 'css']);
$this->set('hideFooterCredit', true);
?>
<div class="auth-page">
    <div class="auth-card">
        <h1>ログイン</h1>

        <?= $this->Form->create(null) ?>
        <?= $this->Form->control('email', [
            'type' => 'email',
            'label' => 'メールアドレス',
            'required' => true,
        ]) ?>
        <?= $this->Form->control('password', [
            'type' => 'password',
            'label' => 'パスワード',
            'required' => true,
        ]) ?>
        <?= $this->Form->button('ログイン', ['class' => 'btn-primary']) ?>
        <?= $this->Form->end() ?>

        <div class="auth-links">
            <a href="<?= $this->Url->build(['action' => 'register']) ?>">ユーザー登録はこちら</a><br>
            <a href="<?= $this->Url->build(['action' => 'forgotPassword']) ?>">パスワードを忘れた方はこちら</a>
        </div>
    </div>
</div>
