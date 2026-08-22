<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import * as authApi from '../../api/auth';
import { ApiError } from '../../api/client';
import AuthBrand from '../../components/layout/AuthBrand.vue';
import AuthFooterCredit from '../../components/layout/AuthFooterCredit.vue';
import Icon from '../../components/icons/Icon.vue';

const route = useRoute();
const email = computed(() => (typeof route.query.email === 'string' ? route.query.email : ''));

const message = ref('');
const error = ref('');
const submitting = ref(false);

async function resend() {
  error.value = '';
  message.value = '';
  submitting.value = true;
  try {
    await authApi.resendVerification(email.value);
    message.value = '確認メールを再送しました。';
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : '確認メールの再送に失敗しました。';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-card">
      <AuthBrand />
      <div class="auth-icon-circle">
        <Icon name="mail-check" :size="30" />
      </div>
      <h1>確認メールを送信しました</h1>
      <p class="auth-lead">
        {{ email }} 宛に確認メールを送信しました。メール内のリンクを開いて登録を完了してください。<br />
        リンクの有効期限は発行から60分です。
      </p>

      <p v-if="error" class="auth-error">{{ error }}</p>
      <p v-if="message" class="auth-lead">{{ message }}</p>

      <button type="button" class="auth-submit" :disabled="submitting" @click="resend">
        <Icon name="mail" :size="16" />
        確認メールを再送する
      </button>

      <div class="auth-info-box">
        <p>メールが見つからない場合</p>
        <ul>
          <li>迷惑メールフォルダをご確認ください。</li>
          <li>前回の送信から60秒以上経ってから再送してください。</li>
          <li>しばらく時間をおいてから、再度お試しください。</li>
        </ul>
      </div>

      <div class="auth-links-single">
        <router-link to="/login">ログイン画面に戻る</router-link>
      </div>
    </div>
    <AuthFooterCredit />
  </div>
</template>
