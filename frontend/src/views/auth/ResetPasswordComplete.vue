<script setup>
import AuthBrand from '../../components/layout/AuthBrand.vue';
import AuthFooterCredit from '../../components/layout/AuthFooterCredit.vue';
import Icon from '../../components/icons/Icon.vue';
</script>

<template>
  <div class="auth-page">
    <div class="auth-card">
      <AuthBrand />
      <div class="auth-icon-circle">
        <Icon name="check-circle" :size="30" />
      </div>
      <h1>パスワードを変更しました</h1>
      <p class="auth-lead">パスワードの変更が完了しました。新しいパスワードでログインしてください。他の端末のログインは自動的に解除されました。</p>

      <router-link to="/login" class="auth-submit">
        <Icon name="user" :size="16" />
        ログイン画面へ
      </router-link>
    </div>
    <AuthFooterCredit />
  </div>
</template>
