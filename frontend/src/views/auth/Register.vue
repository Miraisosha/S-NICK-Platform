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
const password = ref('');
const passwordConfirm = ref('');
const termsAgreed = ref(false);
const showPassword = ref(false);
const error = ref('');
const submitting = ref(false);

async function onSubmit() {
  error.value = '';
  submitting.value = true;
  try {
    await authApi.register(email.value, password.value, passwordConfirm.value, termsAgreed.value);
    router.push({ name: 'register-pending', query: { email: email.value } });
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : '登録に失敗しました。';
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-card">
      <AuthBrand />
      <h1>ユーザー登録</h1>
      <p class="auth-lead">大会運営に必要なアカウントを作成します。</p>

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
              autocomplete="new-password"
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
        <ul class="auth-checklist">
          <li><Icon name="check-circle" :size="15" />6〜64文字で入力してください</li>
          <li><Icon name="check-circle" :size="15" />使用できる記号は ! # $ % &amp; | _ - です</li>
        </ul>
        <div class="auth-field">
          <label for="password_confirm">パスワード（確認）</label>
          <input
            id="password_confirm"
            v-model="passwordConfirm"
            type="password"
            required
            autocomplete="new-password"
            placeholder="もう一度入力してください"
          />
        </div>
        <div class="auth-checkbox">
          <input id="terms_agreed" v-model="termsAgreed" type="checkbox" required />
          <label for="terms_agreed">利用規約・個人情報の取扱いに同意する</label>
        </div>
        <button type="submit" class="auth-submit" :disabled="submitting">
          <Icon name="user" :size="16" />
          登録する
        </button>
      </form>

      <div class="auth-links-single">
        <router-link to="/login">既にアカウントをお持ちの方はこちら</router-link>
      </div>
    </div>
    <AuthFooterCredit />
  </div>
</template>
