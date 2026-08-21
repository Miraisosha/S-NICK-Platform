<?php
/**
 * SCR-OPR-213 パスワードを忘れた方.
 *
 * @var \App\View\AppView $this
 */
$this->Html->css('auth', ['block' => 'css']);
?>
<div class="auth-page">
    <div class="auth-card">
        <h1>パスワードを忘れた方</h1>
        <p>登録済みのメールアドレスを入力してください。該当するアカウントが存在する場合のみ、再設定用のメールを送信します。</p>

        <?= $this->Form->create(null) ?>
        <?= $this->Form->control('email', [
            'type' => 'email',
            'label' => 'メールアドレス',
            'required' => true,
        ]) ?>
        <?= $this->Form->button('送信する', ['class' => 'btn-primary']) ?>
        <?= $this->Form->end() ?>

        <div class="auth-links">
            <a href="<?= $this->Url->build(['action' => 'login']) ?>">ログイン画面へ戻る</a>
        </div>
    </div>
</div>
