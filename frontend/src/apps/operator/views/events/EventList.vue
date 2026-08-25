<script setup>
import { onMounted, ref } from 'vue';
import * as eventsApi from '@/api/events';
import { ApiError } from '@/api/client';
import OperatorShell from '@/apps/operator/components/OperatorShell.vue';
import Icon from '@/components/common/Icon.vue';

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
    <div class="operator-page-header">
      <div>
        <h1>大会管理</h1>
        <p class="operator-page-lead">自分が所有またはスタッフとして参加するイベントの一覧です（SCR-OPR-2401）。</p>
      </div>
      <div class="operator-page-header-actions">
        <router-link to="/events/new" class="operator-button is-primary">
          <Icon name="plus-circle" :size="18" />
          <span>イベントを新規作成</span>
        </router-link>
      </div>
    </div>

    <p v-if="error" class="auth-error">{{ error }}</p>
    <p v-if="loading">読み込み中…</p>
    <div v-else-if="events.length === 0" class="operator-empty-state">
      イベントはまだありません。
    </div>
    <div v-else class="operator-card" style="overflow-x: auto; padding: 0">
      <table class="operator-table">
        <thead>
          <tr>
            <th style="padding-left: 1.4rem">イベント名</th>
            <th>開催期間</th>
            <th style="text-align: right; padding-right: 1.4rem">操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="event in events" :key="event.id">
            <td style="padding-left: 1.4rem">
              <p style="margin: 0; font-weight: bold">{{ event.name }}</p>
              <p v-if="event.subtitle" style="margin: 0.2rem 0 0; color: var(--op-text-muted); font-size: 0.85rem">
                {{ event.subtitle }}
              </p>
            </td>
            <td>{{ formatDate(event.start_at) }} 〜 {{ formatDate(event.end_at) }}</td>
            <td style="padding-right: 1.4rem">
              <div class="operator-card-actions">
                <router-link :to="`/events/${event.id}/edit`" class="operator-button">編集</router-link>
                <router-link :to="`/events/${event.id}/categories`" class="operator-button">カテゴリ管理</router-link>
                <router-link :to="`/events/${event.id}/courts`" class="operator-button">使用コート・利用時間</router-link>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </OperatorShell>
</template>
