<script setup>
defineProps({
  eventId: { type: [String, Number], required: true },
  active: { type: String, required: true }, // 'basic' | 'categories' | 'courts'
});
</script>

<template>
  <nav class="operator-tabs">
    <router-link :to="`/events/${eventId}/edit`" class="operator-tab" :class="{ 'is-active': active === 'basic' }">
      基本情報・スケジュール
    </router-link>
    <router-link
      :to="`/events/${eventId}/categories`"
      class="operator-tab"
      :class="{ 'is-active': active === 'categories' }"
    >
      種目・カテゴリー
    </router-link>
    <router-link :to="`/events/${eventId}/courts`" class="operator-tab" :class="{ 'is-active': active === 'courts' }">
      会場・コート
    </router-link>
  </nav>
</template>
