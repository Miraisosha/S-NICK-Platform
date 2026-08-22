<script setup>
import OperatorShell from '../../components/layout/OperatorShell.vue';
</script>

<template>
  <OperatorShell title="ダッシュボード">
    <h1>ダッシュボード</h1>
    <p class="operator-page-lead">大会の状況をまとめて確認できます。</p>

    <div class="operator-stat-grid">
      <div class="operator-card operator-stat-card">
        <p class="operator-stat-label">エントリー数</p>
        <p class="operator-stat-value">—</p>
      </div>
      <div class="operator-card operator-stat-card">
        <p class="operator-stat-label">試合数（予定）</p>
        <p class="operator-stat-value">—</p>
      </div>
      <div class="operator-card operator-stat-card">
        <p class="operator-stat-label">コート稼働率</p>
        <p class="operator-stat-value">—</p>
      </div>
      <div class="operator-card operator-stat-card">
        <p class="operator-stat-label">ライブ配信</p>
        <p class="operator-stat-value">—</p>
      </div>
    </div>

    <div class="operator-section">
      <h2>本日のスケジュール</h2>
      <div class="operator-empty-state">イベントを作成し、試合を配置するとここに表示されます。</div>
    </div>

    <div class="operator-section">
      <h2>大会の進行状況</h2>
      <div class="operator-empty-state">現在参加中・担当中のイベントはありません。</div>
    </div>

    <div class="operator-section">
      <h2>お知らせ</h2>
      <div class="operator-empty-state">お知らせはありません。</div>
    </div>

    <div class="operator-section">
      <h2>クイック操作</h2>
      <div class="operator-quick-actions">
        <div class="operator-quick-action">試合を追加</div>
        <div class="operator-quick-action">試合を移動</div>
        <div class="operator-quick-action">お知らせを作成</div>
        <div class="operator-quick-action">スコア確認</div>
        <div class="operator-quick-action">ライブ配信設定</div>
        <div class="operator-quick-action">エクスポート</div>
      </div>
    </div>
  </OperatorShell>
</template>
