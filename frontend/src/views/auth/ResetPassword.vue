<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import * as authApi from '../../api/auth';
import { ApiError } from '../../api/client';
import AuthBrand from '../../components/layout/AuthBrand.vue';
import AuthFooterCredit from '../../components/layout/AuthFooterCredit.vue';
import Icon from '../../components/icons/Icon.vue';

const route = useRoute();
const router = useRouter();
const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''));

const password = ref('');
const passwordConfirm = ref('');
const showPassword = ref(false);
const error = ref('');
const submitting = ref(false);

async function onSubmit() {
  error.value = '';
  submitting.value = true;
  try {
    await authApi.resetPassword(token.value, password.value, passwordConfirm.value);
    router.push({ name: 'reset-password-complete' });
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : '再設定に失敗しました。';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-card">
      <AuthBrand />
      <h1>新しいパスワードの設定</h1>
      <p class="auth-lead">新しいパスワードを入力してください。</p>

      <p v-if="!token" class="auth-error">再設定用のリンクが無効です。パスワード再設定をやり直してください。</p>
      <p v-if="error" class="auth-error">{{ error }}</p>

      <form @submit.prevent="onSubmit">
        <div class="auth-field">
          <label for="password">新しいパスワード</label>
          <div class="auth-password-input">
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              required
              autocomplete="new-password"
              placeholder="新しいパスワードを入力"
            />
            <button
              type="button"
              class="auth-password-toggle"
              :aria-label="showPassword ? 'パスワードを非表示' : 'パスワードを表示'"
              @click="showPassword = !showPassword"
            >
              <Icon :name="showPassword ? 'eye-off' : 'eye'" />
            </button>
          </div>
        </div>
        <ul class="auth-checklist">
          <li><Icon name="check-circle" :size="15" />6〜64文字で入力してください</li>
          <li><Icon name="check-circle" :size="15" />使用できる記号は ! # $ % &amp; | _ - です</li>
        </ul>
        <div class="auth-field">
          <label for="password_confirm">新しいパスワード（確認）</label>
          <input
            id="password_confirm"
            v-model="passwordConfirm"
            type="password"
            required
            autocomplete="new-password"
            placeholder="もう一度入力してください"
          />
        </div>
        <button type="submit" class="auth-submit" :disabled="submitting || !token">
          <Icon name="lock" :size="16" />
          パスワードを変更する
        </button>
      </form>

      <div class="auth-links-single">
        <router-link to="/login">← ログイン画面に戻る</router-link>
      </div>
    </div>
    <AuthFooterCredit />
  </div>
</template>
