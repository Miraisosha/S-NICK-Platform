<?php
/**
 * SCR-OPR-213: 新しいパスワード入力.
 *
 * @var \App\View\AppView $this
 * @var string $token
 */
$this->Html->css('auth', ['block' => 'css']);
?>
<div class="auth-page">
    <div class="auth-card">
        <h1>パスワードの再設定</h1>

        <?= $this->element('Users/password_requirements') ?>

        <?= $this->Form->create(null) ?>
        <?= $this->Form->hidden('token', ['value' => $token]) ?>
        <?= $this->Form->control('password', [
            'type' => 'password',
            'label' => '新しいパスワード',
            'required' => true,
        ]) ?>
        <?= $this->Form->control('password_confirm', [
            'type' => 'password',
            'label' => '新しいパスワード（確認）',
            'required' => true,
        ]) ?>
        <?= $this->Form->button('再設定する', ['class' => 'btn-primary']) ?>
        <?= $this->Form->end() ?>
    </div>
</div>
