<?php
/**
 * SCR-OPR-213: 受付完了表示（登録の有無にかかわらず同一の文面）.
 *
 * @var \App\View\AppView $this
 */
$this->Html->css('auth', ['block' => 'css']);
$this->set('hideFooterCredit', true);
?>
<div class="auth-page">
    <div class="auth-card">
        <h1>受付が完了しました</h1>
        <p>ご入力いただいたメールアドレス宛に、該当するアカウントが存在する場合のみパスワード再設定用のメールを送信しました。</p>
        <p>リンクの有効期限は発行から60分です。</p>

        <div class="auth-links">
            <a href="<?= $this->Url->build(['action' => 'login']) ?>">ログイン画面へ戻る</a>
        </div>
    </div>
</div>
