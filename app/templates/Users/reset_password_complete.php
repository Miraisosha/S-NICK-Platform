<?php
/**
 * SCR-OPR-213: 再設定完了画面.
 *
 * @var \App\View\AppView $this
 */
$this->Html->css('auth', ['block' => 'css']);
$this->set('hideFooterCredit', true);
?>
<div class="auth-page">
    <div class="auth-card">
        <h1>パスワードを再設定しました</h1>
        <p>新しいパスワードでログインしてください。他の端末のログインは自動的に解除されました。</p>

        <div class="auth-links">
            <a href="<?= $this->Url->build(['action' => 'login']) ?>">ログイン画面へ</a>
        </div>
    </div>
</div>
