<script setup>
import { onMounted, ref } from 'vue';
import * as eventsApi from '../../../api/events';
import { ApiError } from '../../../api/client';
import OperatorShell from '../../../components/layout/OperatorShell.vue';

const events = ref([]);
const loading = ref(true);
const error = ref('');

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const data = await eventsApi.listEvents();
    events.value = data.events;
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'イベント一覧の取得に失敗しました。';
  } finally {
    loading.value = false;
  }
}

onMounted(load);

function formatDate(iso) {
  if (!iso) {
    return '';
  }
  return new Date(iso).toLocaleString('ja-JP', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>

<template>
  <OperatorShell title="大会管理">
    <h1>大会管理</h1>
    <p class="operator-page-lead">自分が所有またはスタッフとして参加するイベントの一覧です（SCR-OPR-2401）。</p>

    <p v-if="error" class="auth-error">{{ error }}</p>

    <router-link to="/events/new" class="auth-submit" style="max-width: 12rem; margin-bottom: 1.5rem; display: block">
      イベントを新規作成
    </router-link>

    <p v-if="loading">読み込み中…</p>
    <div v-else-if="events.length === 0" class="operator-empty-state">
      イベントはまだありません。
    </div>
    <div v-else class="operator-stat-grid">
      <div v-for="event in events" :key="event.id" class="operator-card">
        <h3 style="margin-top: 0">{{ event.name }}</h3>
        <p v-if="event.subtitle" style="color: var(--op-text-muted); margin: 0 0 0.5rem">{{ event.subtitle }}</p>
        <p style="margin: 0 0 1rem">{{ formatDate(event.start_at) }} 〜 {{ formatDate(event.end_at) }}</p>
        <div style="display: flex; gap: 1rem">
          <router-link :to="`/events/${event.id}/edit`">編集</router-link>
          <router-link :to="`/events/${event.id}/categories`">カテゴリ管理</router-link>
          <router-link :to="`/events/${event.id}/courts`">使用コート・利用時間</router-link>
        </div>
      </div>
    </div>
  </OperatorShell>
</template>
