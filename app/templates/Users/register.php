<?php
/**
 * SCR-OPR-211 ユーザー登録.
 *
 * @var \App\View\AppView $this
 */
$this->Html->css('auth', ['block' => 'css']);
?>
<div class="auth-page">
    <div class="auth-card">
        <h1>ユーザー登録</h1>

        <?= $this->element('Users/password_requirements') ?>

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
        <?= $this->Form->control('password_confirm', [
            'type' => 'password',
            'label' => 'パスワード（確認）',
            'required' => true,
        ]) ?>
        <?= $this->Form->control('terms_agreed', [
            'type' => 'checkbox',
            'label' => '利用規約・個人情報の取扱いに同意する',
            'required' => true,
        ]) ?>
        <?= $this->Form->button('登録する', ['class' => 'btn-primary']) ?>
        <?= $this->Form->end() ?>

        <div class="auth-links">
            <a href="<?= $this->Url->build(['action' => 'login']) ?>">既にアカウントをお持ちの方はこちら</a>
        </div>
    </div>
</div>
