<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import logo from '../../assets/squash-platform-logo.svg';

const STORAGE_KEY = 'operator-sidebar-collapsed';
const MOBILE_BREAKPOINT = 1024;

const siteTitle = 'Squash Platform';

/**
 * `url` is null for sections with no implementation yet (OPR-240/250/260/
 * 270/280 etc.) - those render as inert placeholders instead of dead links.
 * Ported from templates/layout/operator.php.
 */
const navItems = [
  { label: 'ダッシュボード', url: '/dashboard' },
  { label: '大会管理', url: null },
  { label: 'エントリー管理', url: null },
  { label: '試合・スケジュール管理', url: null },
  { label: 'コート管理', url: null },
  { label: 'スコア管理（マーカー）', url: null },
  { label: 'ライブ配信管理', url: null },
  { label: 'ランキング管理', url: null },
  { label: 'ドロー管理', url: null },
  { label: '通知管理', url: null },
  { label: 'ユーザー管理', url: null },
  { label: 'スポンサー管理', url: null },
  { label: 'システム設定', url: null },
  { label: 'ログ管理', url: null },
];

const props = defineProps({
  title: { type: String, required: true },
});

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const collapsed = ref(false);

onMounted(() => {
  const saved = window.localStorage.getItem(STORAGE_KEY);
  collapsed.value = saved === 'true' || saved === 'false' ? saved === 'true' : window.innerWidth < MOBILE_BREAKPOINT;
});

function toggleSidebar() {
  collapsed.value = !collapsed.value;
  window.localStorage.setItem(STORAGE_KEY, String(collapsed.value));
}

async function onLogout() {
  await auth.logout();
  router.push('/login');
}
</script>

<template>
  <div class="operator-shell" :class="{ 'sidebar-collapsed': collapsed }">
    <aside class="operator-sidebar">
      <div class="operator-sidebar-logo">
        <router-link to="/dashboard">
          <img :src="logo" :alt="siteTitle" />
          <span>{{ siteTitle }}</span>
        </router-link>
      </div>
      <nav class="operator-sidebar-nav">
        <template v-for="item in navItems" :key="item.label">
          <router-link
            v-if="item.url"
            :to="item.url"
            class="operator-nav-item"
            :class="{ 'is-active': route.path === item.url }"
          >
            {{ item.label }}
          </router-link>
          <span v-else class="operator-nav-item is-disabled">
            {{ item.label }}
            <span class="operator-nav-badge">準備中</span>
          </span>
        </template>
      </nav>
      <div class="operator-sidebar-footer">
        <p class="operator-current-event-label">現在の大会</p>
        <p class="operator-current-event-empty">大会未選択（イベント管理は準備中です）</p>
      </div>
    </aside>

    <div class="operator-main">
      <header class="operator-topbar">
        <button type="button" class="operator-sidebar-toggle" aria-label="メニューの表示・非表示" @click="toggleSidebar">
          <span></span><span></span><span></span>
        </button>
        <div class="operator-topbar-title">{{ props.title }}</div>
        <div class="operator-topbar-actions">
          <span class="operator-icon-button" title="通知（準備中）" aria-hidden="true">🔔</span>
          <span class="operator-icon-button" title="ヘルプ（準備中）" aria-hidden="true">?</span>
          <div v-if="auth.user" class="operator-user-menu">
            <span class="operator-user-email">{{ auth.user.email }}</span>
            <button type="button" class="operator-logout-button" @click="onLogout">ログアウト</button>
          </div>
        </div>
      </header>
      <main class="operator-content">
        <slot />
      </main>
    </div>
  </div>
</template>

<style>
@import '../../assets/operator.css';
</style>
