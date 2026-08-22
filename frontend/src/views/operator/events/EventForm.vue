<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import * as eventsApi from '../../../api/events';
import { ApiError } from '../../../api/client';
import OperatorShell from '../../../components/layout/OperatorShell.vue';

const route = useRoute();
const router = useRouter();

const eventId = computed(() => (typeof route.params.id === 'string' ? route.params.id : null));
const isEdit = computed(() => eventId.value !== null);

const form = reactive({
  name: '',
  subtitle: '',
  start_at: '',
  end_at: '',
  registration_start_at: '',
  registration_end_at: '',
  contact_email: '',
  contact_info: '',
  description: '',
  notes: '',
});

const loading = ref(isEdit.value);
const submitting = ref(false);
const error = ref('');
const fieldErrors = ref({});

// API returns full ISO (with timezone/seconds); <input type="datetime-local">
// needs "YYYY-MM-DDTHH:mm".
function toLocalInput(iso) {
  if (!iso) {
    return '';
  }
  return iso.slice(0, 16);
}

async function loadEvent() {
  if (!isEdit.value) {
    return;
  }
  loading.value = true;
  error.value = '';
  try {
    const data = await eventsApi.getEvent(eventId.value);
    const event = data.event;
    form.name = event.name;
    form.subtitle = event.subtitle ?? '';
    form.start_at = toLocalInput(event.start_at);
    form.end_at = toLocalInput(event.end_at);
    form.registration_start_at = toLocalInput(event.registration_start_at);
    form.registration_end_at = toLocalInput(event.registration_end_at);
    form.contact_email = event.contact_email ?? '';
    form.contact_info = event.contact_info ?? '';
    form.description = event.description ?? '';
    form.notes = event.notes ?? '';
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'イベント情報の取得に失敗しました。';
  } finally {
    loading.value = false;
  }
}

onMounted(loadEvent);

async function submitForm() {
  error.value = '';
  fieldErrors.value = {};
  submitting.value = true;
  try {
    const payload = {
      name: form.name,
      subtitle: form.subtitle || null,
      start_at: form.start_at,
      end_at: form.end_at,
      registration_start_at: form.registration_start_at || null,
      registration_end_at: form.registration_end_at || null,
      contact_email: form.contact_email || null,
      contact_info: form.contact_info || null,
      description: form.description || null,
      notes: form.notes || null,
    };

    if (isEdit.value) {
      await eventsApi.updateEvent(eventId.value, payload);
    } else {
      await eventsApi.createEvent(payload);
    }

    router.push('/events');
  } catch (e) {
    if (e instanceof ApiError) {
      error.value = e.message;
      fieldErrors.value = e.extra?.fields ?? {};
    } else {
      error.value = '保存に失敗しました。';
    }
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <OperatorShell :title="isEdit ? 'イベント編集' : 'イベント新規登録'">
    <h1>{{ isEdit ? 'イベント編集' : 'イベント新規登録' }}</h1>

    <p v-if="error" class="auth-error">{{ error }}</p>
    <p v-if="loading">読み込み中…</p>

    <form v-else class="operator-card" style="max-width: 40rem" @submit.prevent="submitForm">
      <div class="auth-field">
        <label>イベント名</label>
        <input v-model="form.name" type="text" required />
        <p v-if="fieldErrors.name" class="auth-error">{{ Object.values(fieldErrors.name)[0] }}</p>
      </div>
      <div class="auth-field">
        <label>サブタイトル</label>
        <input v-model="form.subtitle" type="text" />
      </div>
      <div class="auth-field">
        <label>開始日時</label>
        <input v-model="form.start_at" type="datetime-local" required />
      </div>
      <div class="auth-field">
        <label>終了日時</label>
        <input v-model="form.end_at" type="datetime-local" required />
        <p v-if="fieldErrors.end_at" class="auth-error">{{ Object.values(fieldErrors.end_at)[0] }}</p>
      </div>
      <div class="auth-field">
        <label>申込開始日時</label>
        <input v-model="form.registration_start_at" type="datetime-local" />
      </div>
      <div class="auth-field">
        <label>申込締切日時</label>
        <input v-model="form.registration_end_at" type="datetime-local" />
        <p v-if="fieldErrors.registration_end_at" class="auth-error">
          {{ Object.values(fieldErrors.registration_end_at)[0] }}
        </p>
      </div>
      <div class="auth-field">
        <label>代表メールアドレス</label>
        <input v-model="form.contact_email" type="email" />
      </div>
      <div class="auth-field">
        <label>連絡先</label>
        <input v-model="form.contact_info" type="text" />
      </div>
      <div class="auth-field">
        <label>説明・要項</label>
        <textarea v-model="form.description" rows="4" style="width: 100%; box-sizing: border-box"></textarea>
      </div>
      <div class="auth-field">
        <label>備考</label>
        <textarea v-model="form.notes" rows="3" style="width: 100%; box-sizing: border-box"></textarea>
      </div>

      <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem">
        <button type="submit" class="auth-submit" :disabled="submitting">保存する</button>
        <router-link to="/events">キャンセル</router-link>
      </div>
    </form>
  </OperatorShell>
</template>
