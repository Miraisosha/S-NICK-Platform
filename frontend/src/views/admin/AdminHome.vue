<script setup>
import { useRouter } from 'vue-router';
import { useAdminAuthStore } from '../../stores/adminAuth';

const auth = useAdminAuthStore();
const router = useRouter();

async function onLogout() {
  await auth.logout();
  router.push('/admin/login');
}
</script>

<template>
  <div style="padding: 2rem">
    <h1>管理者ダッシュボード</h1>
    <p v-if="auth.admin">{{ auth.admin.name }}（{{ auth.admin.email }} / {{ auth.admin.role }}）としてログイン中です。</p>
    <p>施設・コート管理などの画面は準備中です。</p>
    <button type="button" @click="onLogout">ログアウト</button>
  </div>
</template>
