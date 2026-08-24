<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import * as eventsApi from '@/api/events';
import { ApiError, resolveUploadUrl } from '@/api/client';
import { describeFieldErrors } from '@/utils/apiErrors';
import OperatorShell from '@/apps/operator/components/OperatorShell.vue';
import EventTabs from '@/apps/operator/components/events/EventTabs.vue';
import Icon from '@/components/common/Icon.vue';

const route = useRoute();
const router = useRouter();

const eventId = computed(() => route.params.id);

const form = reactive({
  name: '',
  name_en: '',
  slug: '',
  subtitle: '',
  start_at: '',
  end_at: '',
  registration_start_at: '',
  registration_end_at: '',
  contact_email: '',
  organizer: '',
  contact_info: '',
  description: '',
  notes: '',
});

const currentLogo = ref(null); // server-relative path, or null
const logoFile = ref(null);
const logoPreviewUrl = ref(null);

const loading = ref(true);
const submitting = ref(false);
const deleting = ref(false);
const error = ref('');
const fieldErrors = ref({});
const saved = ref(false);

const slugPrefix = `${window.location.origin}/t/`;

// API returns full ISO (with timezone/seconds); <input type="datetime-local">
// needs "YYYY-MM-DDTHH:mm".
function toLocalInput(iso) {
  if (!iso) {
    return '';
  }
  return iso.slice(0, 16);
}

async function loadEvent() {
  loading.value = true;
  error.value = '';
  try {
    const data = await eventsApi.getEvent(eventId.value);
    const event = data.event;
    form.name = event.name;
    form.name_en = event.name_en ?? '';
    form.slug = event.slug ?? '';
    form.subtitle = event.subtitle ?? '';
    form.start_at = toLocalInput(event.start_at);
    form.end_at = toLocalInput(event.end_at);
    form.registration_start_at = toLocalInput(event.registration_start_at);
    form.registration_end_at = toLocalInput(event.registration_end_at);
    form.contact_email = event.contact_email ?? '';
    form.organizer = event.organizer ?? '';
    form.contact_info = event.contact_info ?? '';
    form.description = event.description ?? '';
    form.notes = event.notes ?? '';
    currentLogo.value = event.logo ?? null;
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'イベント情報の取得に失敗しました。';
  } finally {
    loading.value = false;
  }
}

onMounted(loadEvent);

const logoDisplayUrl = computed(() => logoPreviewUrl.value ?? resolveUploadUrl(currentLogo.value));

function onLogoSelected(file) {
  if (!file) {
    return;
  }
  logoFile.value = file;
  logoPreviewUrl.value = URL.createObjectURL(file);
}

function onFileInputChange(event) {
  onLogoSelected(event.target.files?.[0] ?? null);
}

function onDrop(event) {
  event.preventDefault();
  onLogoSelected(event.dataTransfer.files?.[0] ?? null);
}

async function onRemoveLogo() {
  logoFile.value = null;
  logoPreviewUrl.value = null;
  try {
    await eventsApi.removeEventLogo(eventId.value);
    currentLogo.value = null;
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'ロゴの削除に失敗しました。';
  }
}

function fieldError(name) {
  const err = fieldErrors.value[name];
  return err ? Object.values(err)[0] : '';
}

async function submitForm() {
  error.value = '';
  fieldErrors.value = {};
  saved.value = false;
  submitting.value = true;
  try {
    const payload = {
      name: form.name,
      name_en: form.name_en || null,
      slug: form.slug || null,
      subtitle: form.subtitle || null,
      start_at: form.start_at,
      end_at: form.end_at,
      registration_start_at: form.registration_start_at || null,
      registration_end_at: form.registration_end_at || null,
      contact_email: form.contact_email || null,
      organizer: form.organizer || null,
      contact_info: form.contact_info || null,
      description: form.description || null,
      notes: form.notes || null,
    };

    const data = await eventsApi.updateEvent(eventId.value, payload, logoFile.value);
    currentLogo.value = data.event.logo ?? null;
    logoFile.value = null;
    logoPreviewUrl.value = null;
    saved.value = true;
  } catch (e) {
    if (e instanceof ApiError) {
      const fields = e.extra?.fields ?? {};
      fieldErrors.value = fields;
      error.value = Object.keys(fields).length > 0 ? describeFieldErrors(fields) : e.message;
    } else {
      error.value = '保存に失敗しました。';
    }
  } finally {
    submitting.value = false;
  }
}

async function onDeleteEvent() {
  if (!window.confirm(`「${form.name}」を削除します。よろしいですか？`)) {
    return;
  }
  deleting.value = true;
  try {
    await eventsApi.deleteEvent(eventId.value);
    router.push('/events');
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : '削除に失敗しました。';
    deleting.value = false;
  }
}
</script>

<template>
  <OperatorShell title="大会編集">
    <h1>大会編集</h1>
    <EventTabs :event-id="eventId" active="basic" />
    <p class="operator-page-lead">大会情報を編集してください。</p>

    <p v-if="error" class="auth-error">{{ error }}</p>
    <p v-if="saved" style="color: #2fae5c">保存しました。</p>
    <p v-if="loading">読み込み中…</p>

    <form v-else class="operator-card" style="max-width: 44rem" @submit.prevent="submitForm">
      <div class="auth-field">
        <label>大会名（必須）</label>
        <input v-model="form.name" type="text" required />
        <p v-if="fieldError('name')" class="auth-error">{{ fieldError('name') }}</p>
      </div>
      <div class="auth-field">
        <label>大会名（英語）</label>
        <input v-model="form.name_en" type="text" />
      </div>
      <div class="auth-field">
        <label>大会スラッグ（URL）</label>
        <div style="display: flex; align-items: center; gap: 0.4rem">
          <span style="color: var(--op-text-muted); font-size: 0.85rem; white-space: nowrap">{{ slugPrefix }}</span>
          <input v-model="form.slug" type="text" style="flex: 1" />
        </div>
        <p class="operator-logo-hint">半角英数字とハイフンのみ使用できます。</p>
        <p v-if="fieldError('slug')" class="auth-error">{{ fieldError('slug') }}</p>
      </div>
      <div class="auth-field">
        <label>サブタイトル</label>
        <input v-model="form.subtitle" type="text" />
      </div>
      <div class="auth-field">
        <label>概要</label>
        <textarea v-model="form.description" rows="4" style="width: 100%; box-sizing: border-box"></textarea>
      </div>
      <div class="auth-field">
        <label>大会ロゴ</label>
        <div class="operator-logo-upload">
          <div class="operator-logo-preview">
            <img v-if="logoDisplayUrl" :src="logoDisplayUrl" alt="大会ロゴ" />
            <Icon v-else name="download" :size="24" />
          </div>
          <div>
            <label class="operator-button" style="display: inline-flex">
              変更
              <input type="file" accept="image/jpeg,image/png" style="display: none" @change="onFileInputChange" />
            </label>
            <button
              v-if="logoDisplayUrl"
              type="button"
              class="operator-button"
              style="margin-left: 0.5rem"
              @click="onRemoveLogo"
            >
              削除
            </button>
          </div>
        </div>
        <p class="operator-logo-hint" @dragover.prevent @drop="onDrop">
          推奨サイズ：横1200px × 縦630px（JPG/PNG）／この欄にドラッグ&ドロップも可能です
        </p>
      </div>
      <div class="auth-field">
        <label>開始日時（必須）</label>
        <input v-model="form.start_at" type="datetime-local" required />
      </div>
      <div class="auth-field">
        <label>終了日時（必須）</label>
        <input v-model="form.end_at" type="datetime-local" required />
        <p v-if="fieldError('end_at')" class="auth-error">{{ fieldError('end_at') }}</p>
      </div>
      <div class="auth-field">
        <label>申込開始日時</label>
        <input v-model="form.registration_start_at" type="datetime-local" />
      </div>
      <div class="auth-field">
        <label>申込締切日時</label>
        <input v-model="form.registration_end_at" type="datetime-local" />
        <p v-if="fieldError('registration_end_at')" class="auth-error">
          {{ fieldError('registration_end_at') }}
        </p>
      </div>
      <div class="auth-field">
        <label>主催者</label>
        <input v-model="form.organizer" type="text" />
      </div>
      <div class="auth-field">
        <label>お問い合わせメール</label>
        <input v-model="form.contact_email" type="email" />
      </div>
      <div class="auth-field">
        <label>連絡先</label>
        <input v-model="form.contact_info" type="text" />
      </div>
      <div class="auth-field">
        <label>備考</label>
        <textarea v-model="form.notes" rows="3" style="width: 100%; box-sizing: border-box"></textarea>
      </div>

      <div class="operator-form-actions">
        <button type="submit" class="auth-submit" :disabled="submitting">保存する</button>
        <router-link to="/events" class="operator-button">キャンセル</router-link>
        <button
          type="button"
          class="operator-button"
          style="margin-left: auto; color: #a3241c; border-color: #f3c8c5"
          :disabled="deleting"
          @click="onDeleteEvent"
        >
          削除する
        </button>
      </div>
    </form>
  </OperatorShell>
</template>
