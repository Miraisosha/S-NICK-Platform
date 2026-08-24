<script setup>
import { reactive, ref, watch } from 'vue';
import * as categoriesApi from '@/api/categories';
import { ApiError } from '@/api/client';

const props = defineProps({
  eventId: { type: [String, Number], required: true },
  category: { type: Object, default: null }, // null = create
});

const emit = defineEmits(['saved', 'cancel']);

function toLocalInput(iso) {
  return iso ? iso.slice(0, 16) : '';
}

function defaultForm() {
  return {
    name: '',
    gender: 'none',
    age_min: '',
    age_max: '',
    level: '',
    squash_association_registration: false,
    eligibility: '',
    entry_fee: 0,
    capacity: 1,
    registration_start_at: '',
    registration_end_at: '',
    waitlist_allowed: false,
    match_format: 'tournament',
    max_games: 3,
    game_end_score: 11,
    required_point_diff: 2,
    estimated_game_minutes: 15,
    min_rest_seconds: 0,
    notes: '',
  };
}

const form = reactive(defaultForm());
const formError = ref('');
const fieldErrors = ref({});
const submitting = ref(false);

function loadFromCategory(category) {
  if (category === null) {
    Object.assign(form, defaultForm());
    return;
  }
  Object.assign(form, {
    ...category,
    age_min: category.age_min ?? '',
    age_max: category.age_max ?? '',
    level: category.level ?? '',
    eligibility: category.eligibility ?? '',
    required_point_diff: category.required_point_diff ?? '',
    notes: category.notes ?? '',
    registration_start_at: toLocalInput(category.registration_start_at),
    registration_end_at: toLocalInput(category.registration_end_at),
  });
}

watch(() => props.category, loadFromCategory, { immediate: true });

function fieldError(name) {
  const err = fieldErrors.value[name];
  return err ? Object.values(err)[0] : '';
}

async function submit() {
  formError.value = '';
  fieldErrors.value = {};
  submitting.value = true;
  try {
    const payload = {
      ...form,
      age_min: form.age_min === '' ? null : Number(form.age_min),
      age_max: form.age_max === '' ? null : Number(form.age_max),
      level: form.level || null,
      eligibility: form.eligibility || null,
      required_point_diff: form.required_point_diff === '' ? null : Number(form.required_point_diff),
      notes: form.notes || null,
      entry_fee: Number(form.entry_fee),
      capacity: Number(form.capacity),
      max_games: Number(form.max_games),
      game_end_score: Number(form.game_end_score),
      estimated_game_minutes: Number(form.estimated_game_minutes),
      min_rest_seconds: Number(form.min_rest_seconds),
    };

    if (props.category === null) {
      await categoriesApi.createCategory(props.eventId, payload);
    } else {
      await categoriesApi.updateCategory(props.eventId, props.category.id, payload);
    }

    emit('saved');
  } catch (e) {
    if (e instanceof ApiError) {
      formError.value = e.message;
      fieldErrors.value = e.extra?.fields ?? {};
    } else {
      formError.value = '保存に失敗しました。';
    }
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <form class="operator-card" style="max-width: 44rem" @submit.prevent="submit">
    <h2 style="margin-top: 0">{{ category === null ? '種目・カテゴリーの新規登録' : '種目・カテゴリーの編集' }}</h2>
    <p v-if="formError" class="auth-error">{{ formError }}</p>

    <div class="auth-field">
      <label>カテゴリ名</label>
      <input v-model="form.name" type="text" required />
      <p v-if="fieldError('name')" class="auth-error">{{ fieldError('name') }}</p>
    </div>
    <div class="auth-field">
      <label>性別</label>
      <select v-model="form.gender">
        <option value="none">なし</option>
        <option value="male">男性</option>
        <option value="female">女性</option>
      </select>
    </div>
    <div style="display: flex; gap: 1rem">
      <div class="auth-field" style="flex: 1">
        <label>年齢・自</label>
        <input v-model="form.age_min" type="number" min="0" />
      </div>
      <div class="auth-field" style="flex: 1">
        <label>年齢・至</label>
        <input v-model="form.age_max" type="number" min="0" />
        <p v-if="fieldError('age_max')" class="auth-error">{{ fieldError('age_max') }}</p>
      </div>
    </div>
    <div class="auth-field">
      <label>レベル</label>
      <input v-model="form.level" type="text" placeholder="例: プロ、選手権、フレンドシップ、新人" />
    </div>
    <div class="auth-checkbox">
      <input id="sq_reg" v-model="form.squash_association_registration" type="checkbox" />
      <label for="sq_reg">スカッシュ協会登録必須</label>
    </div>
    <div class="auth-field">
      <label>参加資格</label>
      <textarea v-model="form.eligibility" rows="2" style="width: 100%; box-sizing: border-box"></textarea>
    </div>
    <div style="display: flex; gap: 1rem">
      <div class="auth-field" style="flex: 1">
        <label>参加費（円）</label>
        <input v-model="form.entry_fee" type="number" min="0" required />
      </div>
      <div class="auth-field" style="flex: 1">
        <label>定員（人）</label>
        <input v-model="form.capacity" type="number" min="1" required />
      </div>
    </div>
    <div style="display: flex; gap: 1rem">
      <div class="auth-field" style="flex: 1">
        <label>申込開始日時</label>
        <input v-model="form.registration_start_at" type="datetime-local" required />
      </div>
      <div class="auth-field" style="flex: 1">
        <label>申込締切日時</label>
        <input v-model="form.registration_end_at" type="datetime-local" required />
        <p v-if="fieldError('registration_end_at')" class="auth-error">{{ fieldError('registration_end_at') }}</p>
      </div>
    </div>
    <div class="auth-checkbox">
      <input id="waitlist" v-model="form.waitlist_allowed" type="checkbox" />
      <label for="waitlist">キャンセル待ちを可能にする</label>
    </div>
    <div class="auth-field">
      <label>試合形式</label>
      <select v-model="form.match_format">
        <option value="tournament">トーナメント</option>
        <option value="round_robin">リーグ戦（総当たり）</option>
      </select>
    </div>
    <div style="display: flex; gap: 1rem">
      <div class="auth-field" style="flex: 1">
        <label>最大ゲーム数</label>
        <select v-model="form.max_games">
          <option :value="1">1</option>
          <option :value="3">3</option>
          <option :value="5">5</option>
        </select>
      </div>
      <div class="auth-field" style="flex: 1">
        <label>ゲーム終了点（0=手動終了）</label>
        <input v-model="form.game_end_score" type="number" min="0" max="100" required />
      </div>
      <div class="auth-field" style="flex: 1">
        <label>必要点差</label>
        <select v-model="form.required_point_diff">
          <option value="">（手動終了のため指定不可）</option>
          <option :value="1">1点差</option>
          <option :value="2">2点差</option>
        </select>
        <p v-if="fieldError('required_point_diff')" class="auth-error">{{ fieldError('required_point_diff') }}</p>
      </div>
    </div>
    <div class="auth-field">
      <label>予想ゲーム時間（分）</label>
      <input v-model="form.estimated_game_minutes" type="number" min="1" required />
    </div>
    <div class="auth-field">
      <label>最低休憩時間（秒）</label>
      <input v-model="form.min_rest_seconds" type="number" min="0" required />
    </div>
    <div class="auth-field">
      <label>備考</label>
      <textarea v-model="form.notes" rows="2" style="width: 100%; box-sizing: border-box"></textarea>
    </div>

    <div class="operator-form-actions">
      <button type="submit" class="auth-submit" :disabled="submitting">保存する</button>
      <button type="button" class="operator-button" @click="emit('cancel')">キャンセル</button>
    </div>
  </form>
</template>
