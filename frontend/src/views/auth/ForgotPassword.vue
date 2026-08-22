<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import * as authApi from '../../api/auth';
import { ApiError } from '../../api/client';
import AuthBrand from '../../components/layout/AuthBrand.vue';
import AuthFooterCredit from '../../components/layout/AuthFooterCredit.vue';
import Icon from '../../components/icons/Icon.vue';

const router = useRouter();

const email = ref('');
const error = ref('');
const submitting = ref(false);

async function onSubmit() {
  error.value = '';
  submitting.value = true;
  try {
    await authApi.forgotPassword(email.value);
    router.push({ name: 'forgot-password-sent' });
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : '送信に失敗しました。';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-card">
      <AuthBrand />
      <h1>パスワード再設定</h1>
      <p class="auth-lead">ご登録のメールアドレスを入力してください。パスワード再設定用のURLをお送りします。</p>

      <p v-if="error" class="auth-error">{{ error }}</p>

      <form @submit.prevent="onSubmit">
        <div class="auth-field">
          <label for="email">メールアドレス</label>
          <input id="email" v-model="email" type="email" required autocomplete="email" placeholder="メールアドレスを入力" />
        </div>
        <button type="submit" class="auth-submit" :disabled="submitting">
          <Icon name="mail" :size="16" />
          送信する
        </button>
      </form>

      <div class="auth-links-single">
        <router-link to="/login">← ログイン画面に戻る</router-link>
      </div>
    </div>
    <AuthFooterCredit />
  </div>
</template>
