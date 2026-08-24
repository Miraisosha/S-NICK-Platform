<script setup>
import OperatorShell from '@/apps/operator/components/OperatorShell.vue';
import Icon from '@/components/common/Icon.vue';
</script>

<template>
  <OperatorShell title="ダッシュボード">
    <div class="operator-page-header">
      <div>
        <h1>ダッシュボード</h1>
        <p class="operator-page-lead">大会の状況をまとめて確認できます。</p>
      </div>
    </div>

    <div class="operator-stat-grid">
      <div class="operator-card operator-stat-card">
        <span class="operator-stat-icon is-accent"><Icon name="user-plus" :size="22" /></span>
        <p class="operator-stat-label">エントリー数</p>
        <p class="operator-stat-value">—</p>
      </div>
      <div class="operator-card operator-stat-card">
        <span class="operator-stat-icon is-dark"><Icon name="calendar" :size="22" /></span>
        <p class="operator-stat-label">試合数（予定）</p>
        <p class="operator-stat-value">—</p>
      </div>
      <div class="operator-card operator-stat-card">
        <span class="operator-stat-icon is-accent"><Icon name="grid" :size="22" /></span>
        <p class="operator-stat-label">コート稼働率</p>
        <p class="operator-stat-value">—</p>
      </div>
      <div class="operator-card operator-stat-card">
        <span class="operator-stat-icon is-dark"><Icon name="video" :size="22" /></span>
        <p class="operator-stat-label">ライブ配信</p>
        <p class="operator-stat-value">—</p>
      </div>
    </div>

    <div class="operator-dashboard-grid">
      <div class="operator-card operator-section" style="margin-bottom: 0">
        <div class="operator-card-header">
          <h2>本日のスケジュール</h2>
        </div>
        <div class="operator-empty-state">イベントを作成し、試合を配置するとここに表示されます。</div>
      </div>

      <div class="operator-card operator-section" style="margin-bottom: 0">
        <div class="operator-card-header">
          <h2>大会の進行状況</h2>
        </div>
        <div class="operator-empty-state">現在参加中・担当中のイベントはありません。</div>
      </div>
    </div>

    <div class="operator-dashboard-grid">
      <div class="operator-card operator-section" style="margin-bottom: 0">
        <div class="operator-card-header">
          <h2>クイック操作</h2>
        </div>
        <div class="operator-quick-actions">
          <div class="operator-quick-action"><Icon name="plus-circle" :size="22" /><span>試合を追加</span></div>
          <div class="operator-quick-action"><Icon name="move" :size="22" /><span>試合を移動</span></div>
          <div class="operator-quick-action"><Icon name="megaphone" :size="22" /><span>お知らせを作成</span></div>
          <div class="operator-quick-action"><Icon name="bar-chart" :size="22" /><span>スコア確認</span></div>
          <div class="operator-quick-action"><Icon name="video" :size="22" /><span>ライブ配信設定</span></div>
          <div class="operator-quick-action"><Icon name="download" :size="22" /><span>エクスポート</span></div>
        </div>
      </div>

      <div class="operator-card operator-section" style="margin-bottom: 0">
        <div class="operator-card-header">
          <h2>お知らせ</h2>
        </div>
        <div class="operator-empty-state">お知らせはありません。</div>
      </div>
    </div>
  </OperatorShell>
</template>
