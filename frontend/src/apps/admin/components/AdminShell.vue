<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAdminAuthStore } from '@/stores/adminAuth';
import logo from '@/assets/squash-platform-logo.svg';
import Icon from '@/components/common/Icon.vue';

const siteTitle = 'Squash Platform 管理者';

/**
 * Mirrors docs/specifications/500_Admin.md §500 SCR-ADM-5001's menu list.
 * Only facility/court management (SCR-ADM-522) is implemented so far -
 * the rest render as inert placeholders, same pattern as
 * OperatorShell.vue's nav.
 */
const navItems = [
  { label: '施設・コート管理', url: '/facilities' },
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

const userMenuOpen = ref(false);
const userMenuRef = ref(null);

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
});

function onDocumentClick(event) {
  if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
    userMenuOpen.value = false;
  }
}

async function onLogout() {
  await auth.logout();
  router.push('/login');
}
</script>

<template>
  <div class="operator-shell">
    <aside class="operator-sidebar">
      <div class="operator-sidebar-logo">
        <router-link to="/facilities">
          <img :src="logo" :alt="siteTitle" />
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
        <div class="operator-topbar-title">{{ props.title }}</div>
        <div class="operator-topbar-actions">
          <span class="operator-icon-button" title="通知（準備中）">
            <Icon name="bell" :size="19" />
          </span>
          <span class="operator-icon-button" title="ヘルプ（準備中）">
            <Icon name="help-circle" :size="19" />
          </span>
          <div v-if="auth.admin" ref="userMenuRef" class="operator-user-menu">
            <button type="button" class="operator-user-trigger" @click="userMenuOpen = !userMenuOpen">
              <span class="operator-user-avatar">
                <Icon name="user" :size="18" />
              </span>
              <span class="operator-user-info">
                <span class="operator-user-name">{{ auth.admin.name }}</span>
                <span class="operator-user-role">{{ auth.admin.role }}</span>
              </span>
              <Icon name="chevron-down" :size="16" />
            </button>
            <div v-if="userMenuOpen" class="operator-user-dropdown">
              <button type="button" @click="onLogout">ログアウト</button>
            </div>
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
@import '@/assets/operator.css';
</style>
