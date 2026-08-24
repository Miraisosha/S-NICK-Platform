<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import logo from '@/assets/squash-platform-logo.svg';
import Icon from '@/components/common/Icon.vue';

const siteTitle = 'Squash Platform';

/**
 * `url` is null for sections with no implementation yet (OPR-240/250/260/
 * 270/280 etc.) - those render as inert placeholders instead of dead links.
 * Ported from templates/layout/operator.php.
 */
const navItems = [
  { label: 'ダッシュボード', url: '/dashboard', icon: 'home' },
  { label: '大会管理', url: '/events', icon: 'flag' },
  { label: 'エントリー管理', url: null, icon: 'user-plus' },
  { label: '試合・スケジュール管理', url: null, icon: 'calendar' },
  { label: 'コート管理', url: null, icon: 'grid' },
  { label: 'スコア管理（マーカー）', url: null, icon: 'target' },
  { label: 'ライブ配信管理', url: null, icon: 'video' },
  { label: 'ランキング管理', url: null, icon: 'bar-chart' },
  { label: 'ドロー管理', url: null, icon: 'move' },
  { label: '通知管理', url: null, icon: 'bell' },
  { label: 'ユーザー管理', url: null, icon: 'users' },
  { label: 'スポンサー管理', url: null, icon: 'building' },
  { label: 'システム設定', url: null, icon: 'settings' },
  { label: 'ログ管理', url: null, icon: 'file-text' },
];

const props = defineProps({
  title: { type: String, required: true },
});

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

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
        <router-link to="/dashboard">
          <img :src="logo" :alt="siteTitle" />
        </router-link>
      </div>
      <nav class="operator-sidebar-nav">
        <template v-for="item in navItems" :key="item.label">
          <router-link
            v-if="item.url"
            :to="item.url"
            class="operator-nav-item"
            :class="{ 'is-active': route.path === item.url || route.path.startsWith(item.url + '/') }"
          >
            <Icon :name="item.icon" :size="20" />
            <span>{{ item.label }}</span>
          </router-link>
          <span v-else class="operator-nav-item is-disabled">
            <Icon :name="item.icon" :size="20" />
            <span>{{ item.label }}</span>
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
        <div class="operator-topbar-title">{{ props.title }}</div>
        <div class="operator-topbar-actions">
          <span class="operator-icon-button" title="通知（準備中）">
            <Icon name="bell" :size="19" />
          </span>
          <span class="operator-icon-button" title="ヘルプ（準備中）">
            <Icon name="help-circle" :size="19" />
          </span>
          <div v-if="auth.user" ref="userMenuRef" class="operator-user-menu">
            <button type="button" class="operator-user-trigger" @click="userMenuOpen = !userMenuOpen">
              <span class="operator-user-avatar">
                <Icon name="user" :size="18" />
              </span>
              <span class="operator-user-info">
                <span class="operator-user-name">{{ auth.user.email }}</span>
                <span class="operator-user-role">運営者</span>
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
