<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var string $url
 */
?>
<p>パスワード再設定のご依頼を受け付けました。</p>
<p>以下のリンクを開き、新しいパスワードを設定してください。このリンクの有効期限は発行から60分です。</p>
<p><a href="<?= h($url) ?>"><?= h($url) ?></a></p>
<p>このメールに心当たりがない場合は、破棄してください。パスワードは変更されません。</p>
