<script setup>
defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 18 },
});
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <template v-if="name === 'lock'">
      <rect x="4" y="11" width="16" height="10" rx="2" />
      <path d="M8 11V7a4 4 0 0 1 8 0v4" />
    </template>
    <template v-else-if="name === 'mail'">
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <path d="m3 7 9 6 9-6" />
    </template>
    <template v-else-if="name === 'mail-check'">
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <path d="m3 7 9 6 6.5-4.33" />
      <path d="m15.5 16 2 2 3.5-4" />
    </template>
    <template v-else-if="name === 'check-circle'">
      <circle cx="12" cy="12" r="9" />
      <path d="m8.5 12.5 2.3 2.3L16 9.5" />
    </template>
    <template v-else-if="name === 'eye'">
      <path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z" />
      <circle cx="12" cy="12" r="3" />
    </template>
    <template v-else-if="name === 'eye-off'">
      <path d="M3 3l18 18" />
      <path d="M10.6 5.2A10.6 10.6 0 0 1 12 5c7 0 10.5 7 10.5 7a13.6 13.6 0 0 1-3.1 4" />
      <path d="M6.6 6.6C3.7 8.4 1.5 12 1.5 12S5 19 12 19a10.6 10.6 0 0 0 4.1-.8" />
      <path d="M9.5 9.5a3 3 0 0 0 4.2 4.2" />
    </template>
    <template v-else-if="name === 'user'">
      <circle cx="12" cy="8" r="4" />
      <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" />
    </template>
    <template v-else-if="name === 'help-circle'">
      <circle cx="12" cy="12" r="9" />
      <path d="M9.2 9a2.8 2.8 0 0 1 5.4 1c0 1.8-2.6 2-2.6 3.7" />
      <circle cx="12" cy="17" r="0.6" fill="currentColor" stroke="none" />
    </template>
    <template v-else-if="name === 'menu'">
      <path d="M3 6h18M3 12h18M3 18h18" />
    </template>
    <template v-else-if="name === 'home'">
      <path d="M3 11.5 12 4l9 7.5" />
      <path d="M5.5 10v9a1 1 0 0 0 1 1H10v-5.5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1V20h3.5a1 1 0 0 0 1-1v-9" />
    </template>
    <template v-else-if="name === 'flag'">
      <path d="M5 21V4" />
      <path d="M5 4h13l-2.5 4L18 12H5" />
    </template>
    <template v-else-if="name === 'user-plus'">
      <circle cx="9" cy="8" r="4" />
      <path d="M2 21c0-4 3.1-7 7-7s7 3 7 7" />
      <path d="M19 8v6M16 11h6" />
    </template>
    <template v-else-if="name === 'calendar'">
      <rect x="3" y="5" width="18" height="16" rx="2" />
      <path d="M3 10h18M8 3v4M16 3v4" />
    </template>
    <template v-else-if="name === 'grid'">
      <rect x="3" y="3" width="8" height="8" rx="1" />
      <rect x="13" y="3" width="8" height="8" rx="1" />
      <rect x="3" y="13" width="8" height="8" rx="1" />
      <rect x="13" y="13" width="8" height="8" rx="1" />
    </template>
    <template v-else-if="name === 'target'">
      <circle cx="12" cy="12" r="8.5" />
      <circle cx="12" cy="12" r="4.5" />
      <circle cx="12" cy="12" r="0.6" fill="currentColor" stroke="none" />
    </template>
    <template v-else-if="name === 'video'">
      <rect x="2.5" y="6" width="13" height="12" rx="2" />
      <path d="m15.5 10.5 6-3.5v10l-6-3.5" />
    </template>
    <template v-else-if="name === 'bar-chart'">
      <path d="M4 20V10M12 20V4M20 20v-7" />
      <path d="M2 20h20" />
    </template>
    <template v-else-if="name === 'bell'">
      <path d="M6 10a6 6 0 0 1 12 0c0 4.5 1.5 6 1.5 6h-15S6 14.5 6 10Z" />
      <path d="M9.5 19a2.5 2.5 0 0 0 5 0" />
    </template>
    <template v-else-if="name === 'users'">
      <circle cx="9" cy="8" r="4" />
      <path d="M1.5 21c0-4 3.4-7 7.5-7s7.5 3 7.5 7" />
      <path d="M16 4.3a4 4 0 0 1 0 7.4" />
      <path d="M18.5 14.3c2.6.6 4 2.5 4 6.7" />
    </template>
    <template v-else-if="name === 'building'">
      <rect x="4" y="3" width="11" height="18" rx="1" />
      <path d="M15 8h5v13h-5" />
      <path d="M7.5 7h1M7.5 11h1M7.5 15h1M11 7h1M11 11h1M11 15h1" />
    </template>
    <template v-else-if="name === 'settings'">
      <circle cx="12" cy="12" r="3" />
      <path
        d="M19.4 13a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5V19a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"
      />
    </template>
    <template v-else-if="name === 'file-text'">
      <path d="M6 2.5h8l4 4v15H6z" />
      <path d="M14 2.5V7h4M8.5 12h7M8.5 15.5h7M8.5 8.5h3" />
    </template>
    <template v-else-if="name === 'chevron-right'">
      <path d="m9 6 6 6-6 6" />
    </template>
    <template v-else-if="name === 'chevron-down'">
      <path d="m6 9 6 6 6-6" />
    </template>
    <template v-else-if="name === 'external-link'">
      <path d="M18 13.5V19a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5.5" />
      <path d="M14.5 3H21v6.5M21 3l-9.5 9.5" />
    </template>
    <template v-else-if="name === 'trending-up'">
      <path d="m3 16 6.5-6.5 4 4L21 6" />
      <path d="M15 6h6v6" />
    </template>
    <template v-else-if="name === 'move'">
      <path d="M12 3v18M3 12h18" />
      <path d="m5 9-2 3 2 3M19 9l2 3-2 3M9 5l3-2 3 2M9 19l3 2 3-2" />
    </template>
    <template v-else-if="name === 'megaphone'">
      <path d="M3 10v4a1 1 0 0 0 1 1h2l3.5 4v-14L6 9H4a1 1 0 0 0-1 1Z" />
      <path d="M13 8.5v7a4 4 0 0 0 2-7Z" />
      <path d="M13 3.5c3.5 1.2 6 4.6 6 8.5s-2.5 7.3-6 8.5" />
    </template>
    <template v-else-if="name === 'download'">
      <path d="M12 3v12m0 0-4-4m4 4 4-4" />
      <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
    </template>
    <template v-else-if="name === 'plus-circle'">
      <circle cx="12" cy="12" r="9" />
      <path d="M12 8v8M8 12h8" />
    </template>
  </svg>
</template>
