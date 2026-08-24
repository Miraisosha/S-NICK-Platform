<script setup>
import { computed, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import * as categoriesApi from '@/api/categories';
import * as eventsApi from '@/api/events';
import { ApiError } from '@/api/client';
import { describeFieldErrors } from '@/utils/apiErrors';
import OperatorShell from '@/apps/operator/components/OperatorShell.vue';
import CategoryFormFields from '@/apps/operator/components/events/CategoryFormFields.vue';
import Icon from '@/components/common/Icon.vue';

const router = useRouter();

const STEPS = [
  { key: 'basic', label: '基本情報' },
  { key: 'schedule', label: 'スケジュール' },
  { key: 'categories', label: '種目・カテゴリー' },
  { key: 'confirm', label: '確認' },
];

const stepIndex = ref(0);
const eventId = ref(null);
const createdEvent = ref(null);
const submitting = ref(false);
const error = ref('');
const fieldErrors = ref({});

const basicForm = reactive({
  name: '',
  name_en: '',
  slug: '',
  description: '',
  organizer: '',
  contact_email: '',
});
const logoFile = ref(null);
const logoPreviewUrl = ref(null);

const scheduleForm = reactive({
  start_at: '',
  end_at: '',
  registration_start_at: '',
  registration_end_at: '',
});

const categories = ref([]);
const addingCategory = ref(false);

const slugPrefix = `${window.location.origin}/t/`;

const BASIC_STEP_FIELDS = ['name', 'name_en', 'slug', 'description', 'organizer', 'contact_email'];

function fieldError(name) {
  const err = fieldErrors.value[name];
  return err ? Object.values(err)[0] : '';
}

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

async function saveAndAdvance() {
  error.value = '';
  fieldErrors.value = {};
  submitting.value = true;

  const payload = {
    ...basicForm,
    name_en: basicForm.name_en || null,
    slug: basicForm.slug || null,
    description: basicForm.description || null,
    organizer: basicForm.organizer || null,
    contact_email: basicForm.contact_email || null,
    ...(stepIndex.value === 1 ? scheduleForm : {}),
  };

  try {
    if (eventId.value === null) {
      const data = await eventsApi.createEvent(payload, logoFile.value);
      createdEvent.value = data.event;
      eventId.value = data.event.id;
    } else {
      const data = await eventsApi.updateEvent(eventId.value, payload, logoFile.value);
      createdEvent.value = data.event;
    }
    logoFile.value = null;
    stepIndex.value += 1;
    if (stepIndex.value === 2) {
      await loadCategories();
    }
  } catch (e) {
    if (e instanceof ApiError) {
      // Basic-info and schedule fields are submitted together once the
      // event is created, but only one step's fields are visible at a
      // time - jump back to whichever step actually owns the failing
      // field so its inline error isn't stuck off-screen.
      const fields = e.extra?.fields ?? {};
      fieldErrors.value = fields;
      error.value = Object.keys(fields).length > 0 ? describeFieldErrors(fields) : e.message;

      if (Object.keys(fields).some((field) => BASIC_STEP_FIELDS.includes(field))) {
        stepIndex.value = 0;
      }
    } else {
      error.value = '保存に失敗しました。';
    }
  } finally {
    submitting.value = false;
  }
}

function goBasicNext() {
  error.value = '';
  if (!basicForm.name) {
    error.value = '大会名を入力してください。';
    return;
  }
  stepIndex.value = 1;
}

function goBack() {
  if (stepIndex.value > 0) {
    stepIndex.value -= 1;
  }
}

async function loadCategories() {
  const data = await categoriesApi.listCategories(eventId.value);
  categories.value = data.categories;
}

async function onCategoryAdded() {
  addingCategory.value = false;
  await loadCategories();
}

async function onDeleteCategory(category) {
  if (!window.confirm(`「${category.name}」を削除します。よろしいですか？`)) {
    return;
  }
  await categoriesApi.deleteCategory(eventId.value, category.id);
  await loadCategories();
}

function finish() {
  router.push(`/events/${eventId.value}/edit`);
}

const formattedSchedule = computed(() => {
  if (!scheduleForm.start_at || !scheduleForm.end_at) {
    return '';
  }
  return `${scheduleForm.start_at.replace('T', ' ')} 〜 ${scheduleForm.end_at.replace('T', ' ')}`;
});
</script>

<template>
  <OperatorShell title="大会新規作成">
    <h1>大会新規作成</h1>

    <div class="operator-wizard-steps">
      <div
        v-for="(step, index) in STEPS"
        :key="step.key"
        class="operator-wizard-step"
        :class="{ 'is-current': index === stepIndex, 'is-done': index < stepIndex }"
      >
        <span class="operator-wizard-step-circle">{{ index + 1 }}</span>
        <span class="operator-wizard-step-label">{{ step.label }}</span>
      </div>
    </div>

    <p v-if="error" class="auth-error">{{ error }}</p>

    <!-- Step 1: 基本情報 -->
    <div v-if="stepIndex === 0" class="operator-card" style="max-width: 44rem">
      <h2 style="margin-top: 0">基本情報を入力してください</h2>
      <div class="auth-field">
        <label>大会名（必須）</label>
        <input v-model="basicForm.name" type="text" placeholder="例）S-NICK OPEN 2026" />
        <p v-if="fieldError('name')" class="auth-error">{{ fieldError('name') }}</p>
      </div>
      <div class="auth-field">
        <label>大会名（英語）</label>
        <input v-model="basicForm.name_en" type="text" placeholder="例）S-NICK OPEN 2026" />
      </div>
      <div class="auth-field">
        <label>大会スラッグ（URL）</label>
        <div style="display: flex; align-items: center; gap: 0.4rem">
          <span style="color: var(--op-text-muted); font-size: 0.85rem; white-space: nowrap">{{ slugPrefix }}</span>
          <input v-model="basicForm.slug" type="text" placeholder="例）s-nick-open-2026" style="flex: 1" />
        </div>
        <p class="operator-logo-hint">半角英数字とハイフンのみ使用できます。</p>
        <p v-if="fieldError('slug')" class="auth-error">{{ fieldError('slug') }}</p>
      </div>
      <div class="auth-field">
        <label>概要</label>
        <textarea
          v-model="basicForm.description"
          rows="4"
          maxlength="500"
          style="width: 100%; box-sizing: border-box"
          placeholder="大会の概要を入力してください"
        ></textarea>
        <p class="operator-logo-hint" style="text-align: right">{{ basicForm.description.length }} / 500</p>
      </div>
      <div class="auth-field">
        <label>大会ロゴ</label>
        <div class="operator-logo-upload">
          <div class="operator-logo-preview">
            <img v-if="logoPreviewUrl" :src="logoPreviewUrl" alt="大会ロゴプレビュー" />
            <Icon v-else name="download" :size="24" />
          </div>
          <label class="operator-logo-dropzone" @dragover.prevent @drop="onDrop">
            画像をドラッグ&ドロップ<br />または<br />
            <span class="operator-button" style="margin-top: 0.5rem; display: inline-flex">ファイルを選択</span>
            <input type="file" accept="image/jpeg,image/png" style="display: none" @change="onFileInputChange" />
          </label>
        </div>
        <p class="operator-logo-hint">推奨サイズ：横1200px × 縦630px（JPG/PNG）</p>
      </div>
      <div class="auth-field">
        <label>主催者</label>
        <input v-model="basicForm.organizer" type="text" required />
        <p v-if="fieldError('organizer')" class="auth-error">{{ fieldError('organizer') }}</p>
      </div>
      <div class="auth-field">
        <label>お問い合わせメール</label>
        <input v-model="basicForm.contact_email" type="email" required />
        <p v-if="fieldError('contact_email')" class="auth-error">{{ fieldError('contact_email') }}</p>
      </div>

      <div class="operator-wizard-actions">
        <span></span>
        <button type="button" class="auth-submit" style="width: auto; padding: 0 2rem" @click="goBasicNext">次へ →</button>
      </div>
    </div>

    <!-- Step 2: スケジュール -->
    <div v-else-if="stepIndex === 1" class="operator-card" style="max-width: 44rem">
      <h2 style="margin-top: 0">開催期間・申込期間を入力してください</h2>
      <div class="auth-field">
        <label>開始日時（必須）</label>
        <input v-model="scheduleForm.start_at" type="datetime-local" />
        <p v-if="fieldError('start_at')" class="auth-error">{{ fieldError('start_at') }}</p>
      </div>
      <div class="auth-field">
        <label>終了日時（必須）</label>
        <input v-model="scheduleForm.end_at" type="datetime-local" />
        <p v-if="fieldError('end_at')" class="auth-error">{{ fieldError('end_at') }}</p>
      </div>
      <div class="auth-field">
        <label>申込開始日時</label>
        <input v-model="scheduleForm.registration_start_at" type="datetime-local" />
      </div>
      <div class="auth-field">
        <label>申込締切日時</label>
        <input v-model="scheduleForm.registration_end_at" type="datetime-local" />
        <p v-if="fieldError('registration_end_at')" class="auth-error">{{ fieldError('registration_end_at') }}</p>
      </div>

      <div class="operator-wizard-actions">
        <button type="button" class="operator-button" @click="goBack">← 戻る</button>
        <button type="button" class="auth-submit" style="width: auto; padding: 0 2rem" :disabled="submitting" @click="saveAndAdvance">
          次へ →
        </button>
      </div>
    </div>

    <!-- Step 3: 種目・カテゴリー -->
    <div v-else-if="stepIndex === 2">
      <div class="operator-card" style="max-width: 44rem; margin-bottom: 1.5rem">
        <h2 style="margin-top: 0">種目・カテゴリーを設定してください</h2>
        <p class="operator-page-lead" style="margin: 0 0 1rem">
          後からいつでも「種目・カテゴリー」画面で追加・編集できます。ここでは追加しなくても次へ進めます。
        </p>

        <div v-if="categories.length > 0" style="margin-bottom: 1rem">
          <div
            v-for="category in categories"
            :key="category.id"
            style="display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid var(--op-border)"
          >
            <span>{{ category.name }}</span>
            <button type="button" class="operator-button" @click="onDeleteCategory(category)">削除</button>
          </div>
        </div>

        <button
          v-if="!addingCategory"
          type="button"
          class="operator-button is-primary"
          @click="addingCategory = true"
        >
          + 種目を追加
        </button>

        <CategoryFormFields
          v-else
          :event-id="eventId"
          :category="null"
          @saved="onCategoryAdded"
          @cancel="addingCategory = false"
        />
      </div>

      <div class="operator-wizard-actions">
        <button type="button" class="operator-button" @click="goBack">← 戻る</button>
        <button type="button" class="auth-submit" style="width: auto; padding: 0 2rem" @click="stepIndex = 3">次へ →</button>
      </div>
    </div>

    <!-- Step 4: 確認 -->
    <div v-else class="operator-card" style="max-width: 44rem">
      <h2 style="margin-top: 0">内容を確認してください</h2>
      <dl class="operator-summary-card">
        <div class="operator-summary-row">
          <dt>大会名</dt>
          <dd>{{ basicForm.name }}</dd>
        </div>
        <div v-if="basicForm.organizer" class="operator-summary-row">
          <dt>主催者</dt>
          <dd>{{ basicForm.organizer }}</dd>
        </div>
        <div v-if="formattedSchedule" class="operator-summary-row">
          <dt>開催期間</dt>
          <dd>{{ formattedSchedule }}</dd>
        </div>
        <div v-if="basicForm.contact_email" class="operator-summary-row">
          <dt>お問い合わせ</dt>
          <dd>{{ basicForm.contact_email }}</dd>
        </div>
      </dl>
      <p class="operator-logo-hint" style="margin-top: 1rem">
        ここまでの内容はすでに保存されています。「完了」を押すと大会編集画面に移動します。
      </p>

      <div class="operator-wizard-actions">
        <button type="button" class="operator-button" @click="goBack">← 戻る</button>
        <button type="button" class="auth-submit" style="width: auto; padding: 0 2rem" @click="finish">完了</button>
      </div>
    </div>
  </OperatorShell>
</template>
