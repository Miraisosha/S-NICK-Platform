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
        <Icon name="mail-check" :size="30" />
      </div>
      <h1>メールを送信しました</h1>
      <p class="auth-lead">
        パスワード再設定用のURLをお送りしました。メールをご確認のうえ、手続きを進めてください。<br />
        該当するアカウントが存在する場合のみ送信されます。リンクの有効期限は発行から60分です。
      </p>

      <div class="auth-info-box">
        <p>メールが見つからない場合</p>
        <ul>
          <li>迷惑メールフォルダをご確認ください。</li>
          <li>ご入力のメールアドレスに誤りがないかご確認ください。</li>
          <li>しばらく時間をおいてから、再度お試しください。</li>
        </ul>
      </div>

      <div class="auth-links-single">
        <router-link to="/login">← ログイン画面に戻る</router-link>
      </div>
    </div>
    <AuthFooterCredit />
  </div>
</template>
