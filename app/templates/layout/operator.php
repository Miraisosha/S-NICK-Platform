<?php
/**
 * Shared shell for the logged-in operator area: fixed top bar + collapsible
 * left sidebar. Used by DashboardController and (going forward) any other
 * operator-authenticated controller.
 *
 * @var \App\View\AppView $this
 */

$siteTitle = 'Squash Platform';
$identity = $this->request->getAttribute('identity');

/**
 * Sidebar navigation. `url` is null for sections that have no controller
 * yet (OPR-240/250/260/270/280 etc.) - those render as inert placeholders
 * instead of dead links. `ランキング管理`/`ユーザー管理`/`システム設定`/`ログ管理`
 * map to ADM-* functions in docs/specifications/500_Admin.md rather than
 * operator ones; they're included here to match the shared design mock,
 * but role-based separation from platform-admin login is not implemented
 * yet and should be revisited when permissions are designed.
 */
$navItems = [
    ['label' => 'ダッシュボード', 'url' => '/dashboard'],
    ['label' => '大会管理', 'url' => null],
    ['label' => 'エントリー管理', 'url' => null],
    ['label' => '試合・スケジュール管理', 'url' => null],
    ['label' => 'コート管理', 'url' => null],
    ['label' => 'スコア管理（マーカー）', 'url' => null],
    ['label' => 'ライブ配信管理', 'url' => null],
    ['label' => 'ランキング管理', 'url' => null],
    ['label' => 'ドロー管理', 'url' => null],
    ['label' => '通知管理', 'url' => null],
    ['label' => 'ユーザー管理', 'url' => null],
    ['label' => 'スポンサー管理', 'url' => null],
    ['label' => 'システム設定', 'url' => null],
    ['label' => 'ログ管理', 'url' => null],
];
$currentUrl = $this->request->getRequestTarget();
?>
<!DOCTYPE html>
<html>
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $siteTitle ?>:
        <?= $this->fetch('title') ?>
    </title>
    <link rel="icon" type="image/svg+xml" href="/img/squash-platform-logo.svg">
    <?= $this->Html->meta('icon') ?>

    <?= $this->Html->css(['normalize.min', 'operator']) ?>

    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <div class="operator-shell" id="operator-shell">
        <aside class="operator-sidebar">
            <div class="operator-sidebar-logo">
                <a href="/dashboard">
                    <?= $this->Html->image('squash-platform-logo.svg', ['alt' => $siteTitle]) ?>
                    <span><?= $siteTitle ?></span>
                </a>
            </div>
            <nav class="operator-sidebar-nav">
                <?php foreach ($navItems as $item): ?>
                    <?php if ($item['url'] !== null): ?>
                        <a
                            href="<?= h($item['url']) ?>"
                            class="operator-nav-item<?= $currentUrl === $item['url'] ? ' is-active' : '' ?>"
                        ><?= h($item['label']) ?></a>
                    <?php else: ?>
                        <span class="operator-nav-item is-disabled">
                            <?= h($item['label']) ?>
                            <span class="operator-nav-badge">準備中</span>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <div class="operator-sidebar-footer">
                <p class="operator-current-event-label">現在の大会</p>
                <p class="operator-current-event-empty">大会未選択（イベント管理は準備中です）</p>
            </div>
        </aside>

        <div class="operator-main">
            <header class="operator-topbar">
                <button type="button" class="operator-sidebar-toggle" id="operator-sidebar-toggle" aria-label="メニューの表示・非表示">
                    <span></span><span></span><span></span>
                </button>
                <div class="operator-topbar-title"><?= $this->fetch('title') ?></div>
                <div class="operator-topbar-actions">
                    <span class="operator-icon-button" title="通知（準備中）" aria-hidden="true">🔔</span>
                    <span class="operator-icon-button" title="ヘルプ（準備中）" aria-hidden="true">?</span>
                    <?php if ($identity !== null): ?>
                        <div class="operator-user-menu">
                            <span class="operator-user-email"><?= h($identity->get('email')) ?></span>
                            <?= $this->Form->create(null, ['url' => '/users/logout', 'class' => 'operator-logout-form']) ?>
                            <?= $this->Form->button('ログアウト', ['class' => 'operator-logout-button']) ?>
                            <?= $this->Form->end() ?>
                        </div>
                    <?php endif; ?>
                </div>
            </header>
            <main class="operator-content">
                <?= $this->Flash->render() ?>
                <?= $this->fetch('content') ?>
            </main>
        </div>
    </div>
    <?= $this->Html->script('operator') ?>
</body>
</html>
