<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import * as authApi from '@/api/auth';
import { useAuthStore } from '@/stores/auth';
import { ApiError } from '@/api/client';
import AuthBrand from '@/components/common/AuthBrand.vue';
import AuthFooterCredit from '@/components/common/AuthFooterCredit.vue';
import Icon from '@/components/common/Icon.vue';

const route = useRoute();
const auth = useAuthStore();

const status = ref('verifying');
const error = ref('');

onMounted(async () => {
  const token = typeof route.query.token === 'string' ? route.query.token : '';
  if (!token) {
    status.value = 'error';
    error.value = '確認用のリンクが無効です。';
    return;
  }

  try {
    auth.user = await authApi.verifyEmail(token);
    auth.initialized = true;
    status.value = 'done';
  } catch (e) {
    status.value = 'error';
    error.value = e instanceof ApiError ? e.message : '確認に失敗しました。';
  }
});
</script>

<template>
  <div class="auth-page">
    <div class="auth-card">
      <AuthBrand />
      <template v-if="status === 'verifying'">
        <h1>確認しています…</h1>
      </template>
      <template v-else-if="status === 'done'">
        <div class="auth-icon-circle">
          <Icon name="check-circle" :size="30" />
        </div>
        <h1>メールアドレスを確認しました</h1>
        <p class="auth-lead">登録が完了しました。ダッシュボードをご利用いただけます。</p>
        <router-link to="/dashboard" class="auth-submit">
          <Icon name="user" :size="16" />
          ダッシュボードへ
        </router-link>
      </template>
      <template v-else>
        <h1>確認できませんでした</h1>
        <p class="auth-error">{{ error }}</p>
        <div class="auth-links-single">
          <router-link to="/login">ログイン画面へ戻る</router-link>
        </div>
      </template>
    </div>
    <AuthFooterCredit />
  </div>
</template>
