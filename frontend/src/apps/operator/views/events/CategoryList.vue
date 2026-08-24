<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import * as categoriesApi from '@/api/categories';
import * as eventsApi from '@/api/events';
import { ApiError } from '@/api/client';
import OperatorShell from '@/apps/operator/components/OperatorShell.vue';
import EventTabs from '@/apps/operator/components/events/EventTabs.vue';
import CategoryFormFields from '@/apps/operator/components/events/CategoryFormFields.vue';

const route = useRoute();
const eventId = computed(() => route.params.id);

const event = ref(null);
const categories = ref([]);
const loading = ref(true);
const error = ref('');

const editing = ref(null); // null = closed, 'new' = creating, otherwise the category object being edited

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const [eventData, categoriesData] = await Promise.all([
      eventsApi.getEvent(eventId.value),
      categoriesApi.listCategories(eventId.value),
    ]);
    event.value = eventData.event;
    categories.value = categoriesData.categories;
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : '読み込みに失敗しました。';
  } finally {
    loading.value = false;
  }
}

onMounted(load);

function openCreate() {
  editing.value = 'new';
}

function openEdit(category) {
  editing.value = category;
}

function closeForm() {
  editing.value = null;
}

async function onSaved() {
  closeForm();
  await load();
}

async function onDelete(category) {
  if (!window.confirm(`「${category.name}」を削除します。よろしいですか？`)) {
    return;
  }
  try {
    await categoriesApi.deleteCategory(eventId.value, category.id);
    await load();
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : '削除に失敗しました。';
  }
}
</script>

<template>
  <OperatorShell title="種目・カテゴリー">
    <h1>種目・カテゴリー</h1>
    <EventTabs :event-id="eventId" active="categories" />
    <p v-if="event" class="operator-page-lead">{{ event.name }} のカテゴリ一覧です（SCR-OPR-2405）。</p>

    <p v-if="error" class="auth-error">{{ error }}</p>

    <button
      v-if="editing === null"
      type="button"
      class="operator-button is-primary"
      style="margin-bottom: 1.5rem"
      @click="openCreate"
    >
      + カテゴリを新規登録
    </button>

    <CategoryFormFields
      v-if="editing !== null"
      :event-id="eventId"
      :category="editing === 'new' ? null : editing"
      @saved="onSaved"
      @cancel="closeForm"
    />

    <p v-if="loading">読み込み中…</p>
    <div v-else-if="categories.length === 0" class="operator-empty-state">カテゴリはまだ登録されていません。</div>
    <div v-else class="operator-stat-grid">
      <div v-for="category in categories" :key="category.id" class="operator-card">
        <h3 style="margin-top: 0">{{ category.name }}</h3>
        <p style="color: var(--op-text-muted); margin: 0 0 0.5rem">
          定員{{ category.capacity }}人 / 参加費{{ category.entry_fee }}円 / {{ category.publication_status }}
        </p>
        <p style="margin: 0 0 1rem">予定試合時間: 約{{ Math.round(category.estimated_match_seconds / 60) }}分</p>
        <div class="operator-card-actions">
          <button type="button" class="operator-button" @click="openEdit(category)">編集</button>
          <button type="button" class="operator-button" @click="onDelete(category)">削除</button>
        </div>
      </div>
    </div>
  </OperatorShell>
</template>
