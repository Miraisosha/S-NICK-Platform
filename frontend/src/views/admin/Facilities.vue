<script setup>
import { onMounted, reactive, ref } from 'vue';
import * as facilitiesApi from '../../api/facilities';
import { ApiError } from '../../api/client';
import AdminShell from '../../components/layout/AdminShell.vue';

const facilities = ref([]);
const loading = ref(true);
const error = ref('');

const editingId = ref(null); // null = closed, 'new' = creating, otherwise facility id
const form = reactive({ name: '', address: '', prefecture: '', website_url: '', notes: '', courts: [] });
const formError = ref('');
const submitting = ref(false);

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const data = await facilitiesApi.listFacilities();
    facilities.value = data.facilities;
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : '施設一覧の取得に失敗しました。';
  } finally {
    loading.value = false;
  }
}

onMounted(load);

function openCreate() {
  editingId.value = 'new';
  formError.value = '';
  Object.assign(form, { name: '', address: '', prefecture: '', website_url: '', notes: '', courts: [] });
}

function openEdit(facility) {
  editingId.value = facility.id;
  formError.value = '';
  Object.assign(form, {
    name: facility.name,
    address: facility.address ?? '',
    prefecture: facility.prefecture ?? '',
    website_url: facility.website_url ?? '',
    notes: facility.notes ?? '',
    courts: facility.courts.map((c) => ({ id: c.id, name: c.name })),
  });
}

function closeForm() {
  editingId.value = null;
}

function addCourtRow() {
  form.courts.push({ id: null, name: '' });
}

function removeCourtRow(index) {
  form.courts.splice(index, 1);
}

async function submitForm() {
  formError.value = '';
  submitting.value = true;
  try {
    const payload = {
      name: form.name,
      address: form.address,
      prefecture: form.prefecture,
      website_url: form.website_url,
      notes: form.notes,
      courts: form.courts
        .filter((c) => c.name.trim() !== '')
        .map((c) => (c.id ? { id: c.id, name: c.name } : { name: c.name })),
    };

    if (editingId.value === 'new') {
      await facilitiesApi.createFacility(payload);
    } else {
      await facilitiesApi.updateFacility(editingId.value, payload);
    }

    closeForm();
    await load();
  } catch (e) {
    formError.value = e instanceof ApiError ? e.message : '保存に失敗しました。';
  } finally {
    submitting.value = false;
  }
}

async function onDelete(facility) {
  if (!window.confirm(`「${facility.name}」を削除します。よろしいですか？`)) {
    return;
  }
  try {
    await facilitiesApi.deleteFacility(facility.id);
    await load();
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : '削除に失敗しました。';
  }
}
</script>

<template>
  <AdminShell title="施設・コート管理">
    <h1>施設・コート管理</h1>
    <p class="operator-page-lead">
      運営者がイベント作成・編集時に選択する施設・コートのマスターデータです（SCR-ADM-522）。
    </p>

    <p v-if="error" class="auth-error">{{ error }}</p>

    <button type="button" class="auth-submit" style="max-width: 12rem; margin-bottom: 1.5rem" @click="openCreate">
      施設を新規登録
    </button>

    <div v-if="editingId !== null" class="operator-card" style="margin-bottom: 1.5rem">
      <h2>{{ editingId === 'new' ? '施設の新規登録' : '施設の編集' }}</h2>

      <p v-if="formError" class="auth-error">{{ formError }}</p>

      <form @submit.prevent="submitForm">
        <div class="auth-field">
          <label>施設名</label>
          <input v-model="form.name" type="text" required />
        </div>
        <div class="auth-field">
          <label>都道府県</label>
          <input v-model="form.prefecture" type="text" />
        </div>
        <div class="auth-field">
          <label>住所</label>
          <input v-model="form.address" type="text" />
        </div>
        <div class="auth-field">
          <label>ホームページURL</label>
          <input v-model="form.website_url" type="text" />
        </div>
        <div class="auth-field">
          <label>備考</label>
          <input v-model="form.notes" type="text" />
        </div>

        <div class="auth-field">
          <label>コート</label>
          <div
            v-for="(court, index) in form.courts"
            :key="index"
            style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem"
          >
            <input v-model="court.name" type="text" placeholder="コート名" style="flex: 1" />
            <button type="button" @click="removeCourtRow(index)">削除</button>
          </div>
          <button type="button" @click="addCourtRow">+ コートを追加</button>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem">
          <button type="submit" class="auth-submit" :disabled="submitting">保存する</button>
          <button type="button" @click="closeForm">キャンセル</button>
        </div>
      </form>
    </div>

    <p v-if="loading">読み込み中…</p>
    <div v-else-if="facilities.length === 0" class="operator-empty-state">
      登録されている施設はありません。
    </div>
    <div v-else class="operator-stat-grid">
      <div v-for="facility in facilities" :key="facility.id" class="operator-card">
        <h3 style="margin-top: 0">{{ facility.name }}</h3>
        <p style="color: var(--op-text-muted); margin: 0 0 0.75rem">
          {{ facility.prefecture }} {{ facility.address }}
        </p>
        <ul style="padding-left: 1.2rem; margin: 0 0 1rem">
          <li v-for="court in facility.courts" :key="court.id">{{ court.name }}</li>
        </ul>
        <div style="display: flex; gap: 0.5rem">
          <button type="button" @click="openEdit(facility)">編集</button>
          <button type="button" @click="onDelete(facility)">削除</button>
        </div>
      </div>
    </div>
  </AdminShell>
</template>
