<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminAuthStore } from '../../stores/adminAuth';
import logo from '../../assets/squash-platform-logo.svg';

const STORAGE_KEY = 'admin-sidebar-collapsed';
const MOBILE_BREAKPOINT = 1024;

const siteTitle = 'Squash Platform 管理者';

/**
 * Mirrors docs/specifications/500_Admin.md §500 SCR-ADM-5001's menu list.
 * Only facility/court management (SCR-ADM-522) is implemented so far -
 * the rest render as inert placeholders, same pattern as
 * OperatorShell.vue's nav.
 */
const navItems = [
  { label: '施設・コート管理', url: '/admin/facilities' },
  { label: '利用者管理', url: null },
  { label: 'イベント横断確認', url: null },
  { label: '監査ログ', url: null },
  { label: 'システム設定', url: null },
];

const props = defineProps({
  title: { type: String, required: true },
});

const route = useRoute();
const router = useRouter();
const auth = useAdminAuthStore();

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
  router.push('/admin/login');
}
</script>

<template>
  <div class="operator-shell" :class="{ 'sidebar-collapsed': collapsed }">
    <aside class="operator-sidebar">
      <div class="operator-sidebar-logo">
        <router-link to="/admin/facilities">
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
            :class="{ 'is-active': route.path.startsWith(item.url) }"
          >
            {{ item.label }}
          </router-link>
          <span v-else class="operator-nav-item is-disabled">
            {{ item.label }}
            <span class="operator-nav-badge">準備中</span>
          </span>
        </template>
      </nav>
    </aside>

    <div class="operator-main">
      <header class="operator-topbar">
        <button type="button" class="operator-sidebar-toggle" aria-label="メニューの表示・非表示" @click="toggleSidebar">
          <span></span><span></span><span></span>
        </button>
        <div class="operator-topbar-title">{{ props.title }}</div>
        <div class="operator-topbar-actions">
          <div v-if="auth.admin" class="operator-user-menu">
            <span class="operator-user-email">{{ auth.admin.name }}（{{ auth.admin.role }}）</span>
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
