<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var string $url
 */
?>
<p>Squash Platformにご登録いただき、ありがとうございます。</p>
<p>以下のリンクを開き、メールアドレスの確認を完了してください。このリンクの有効期限は発行から60分です。</p>
<p><a href="<?= h($url) ?>"><?= h($url) ?></a></p>
<p>このメールに心当たりがない場合は、破棄してください。</p>
