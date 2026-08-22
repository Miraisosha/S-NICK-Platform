<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminAuthStore } from '../../stores/adminAuth';
import { ApiError } from '../../api/client';
import AuthBrand from '../../components/layout/AuthBrand.vue';
import AuthFooterCredit from '../../components/layout/AuthFooterCredit.vue';
import Icon from '../../components/icons/Icon.vue';

const auth = useAdminAuthStore();
const route = useRoute();
const router = useRouter();

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const error = ref('');
const submitting = ref(false);

async function onSubmit() {
  error.value = '';
  submitting.value = true;
  try {
    await auth.login(email.value, password.value);
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/admin/facilities';
    router.push(redirect);
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'ログインに失敗しました。';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-card">
      <AuthBrand />
      <h1>管理者ログイン</h1>
      <p class="auth-lead">プラットフォーム管理者専用のログインです。運営者アカウントではログインできません。</p>

      <p v-if="error" class="auth-error">{{ error }}</p>

      <form @submit.prevent="onSubmit">
        <div class="auth-field">
          <label for="email">メールアドレス</label>
          <input id="email" v-model="email" type="email" required autocomplete="email" placeholder="メールアドレスを入力" />
        </div>
        <div class="auth-field">
          <label for="password">パスワード</label>
          <div class="auth-password-input">
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              required
              autocomplete="current-password"
              placeholder="パスワードを入力"
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
        <button type="submit" class="auth-submit" :disabled="submitting">
          <Icon name="lock" :size="16" />
          ログイン
        </button>
      </form>
    </div>
    <AuthFooterCredit />
  </div>
</template>
