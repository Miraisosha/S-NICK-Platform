<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import * as eventsApi from '@/api/events';
import { listFacilitiesForOperator } from '@/api/facilities';
import { ApiError } from '@/api/client';
import OperatorShell from '@/apps/operator/components/OperatorShell.vue';
import EventTabs from '@/apps/operator/components/events/EventTabs.vue';

const route = useRoute();
const eventId = computed(() => route.params.id);

const event = ref(null);
const facilities = ref([]);
const selectedCourtIds = ref([]);
const usageRows = ref([]); // [{ court_id, usage_date, start_time, end_time }]

const loading = ref(true);
const error = ref('');
const courtsError = ref('');
const usageError = ref('');
const savingCourts = ref(false);
const savingUsage = ref(false);
const courtsSaved = ref(false);

const selectedCourts = computed(() => {
  const all = facilities.value.flatMap((f) => f.courts.map((c) => ({ ...c, facilityName: f.name })));
  return all.filter((c) => selectedCourtIds.value.includes(c.id));
});

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const [eventData, facilitiesData, eventCourtsData, usageTimesData] = await Promise.all([
      eventsApi.getEvent(eventId.value),
      listFacilitiesForOperator(),
      eventsApi.getEventCourts(eventId.value),
      eventsApi.getEventUsageTimes(eventId.value),
    ]);
    event.value = eventData.event;
    facilities.value = facilitiesData.facilities;
    selectedCourtIds.value = eventCourtsData.courts.map((c) => c.court_id);
    usageRows.value = usageTimesData.usageTimes.map((u) => ({
      court_id: u.court_id,
      usage_date: u.usage_date,
      start_time: u.start_time.slice(0, 5),
      end_time: u.end_time.slice(0, 5),
    }));
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : '読み込みに失敗しました。';
  } finally {
    loading.value = false;
  }
}

onMounted(load);

async function saveCourts() {
  courtsError.value = '';
  savingCourts.value = true;
  courtsSaved.value = false;
  try {
    await eventsApi.updateEventCourts(eventId.value, selectedCourtIds.value);
    // Usage-time rows for courts that are no longer selected are invalid now.
    usageRows.value = usageRows.value.filter((r) => selectedCourtIds.value.includes(r.court_id));
    courtsSaved.value = true;
  } catch (e) {
    courtsError.value = e instanceof ApiError ? e.message : '保存に失敗しました。';
  } finally {
    savingCourts.value = false;
  }
}

function addUsageRow() {
  usageRows.value.push({
    court_id: selectedCourts.value[0]?.id ?? null,
    usage_date: '',
    start_time: '',
    end_time: '',
  });
}

function removeUsageRow(index) {
  usageRows.value.splice(index, 1);
}

async function saveUsageTimes() {
  usageError.value = '';
  savingUsage.value = true;
  try {
    await eventsApi.updateEventUsageTimes(eventId.value, usageRows.value);
  } catch (e) {
    usageError.value = e instanceof ApiError ? e.message : '保存に失敗しました。';
  } finally {
    savingUsage.value = false;
  }
}
</script>

<template>
  <OperatorShell title="使用コート・利用時間">
    <h1>使用コート・利用時間</h1>
    <EventTabs :event-id="eventId" active="courts" />
    <p v-if="event" class="operator-page-lead">{{ event.name }} の設定です（SCR-OPR-2402/2404）。</p>

    <p v-if="error" class="auth-error">{{ error }}</p>
    <p v-if="loading">読み込み中…</p>

    <template v-else>
      <div class="operator-card" style="margin-bottom: 1.5rem">
        <h2>使用コートの選択</h2>
        <p class="operator-page-lead" style="margin: 0 0 1rem">
          施設・コートの登録・編集は管理者機能で行います。ここでは、このイベントで使用するコートを選択します。
        </p>
        <p v-if="courtsError" class="auth-error">{{ courtsError }}</p>
        <p v-if="courtsSaved" style="color: #2fae5c">保存しました。</p>

        <div v-for="facility in facilities" :key="facility.id" style="margin-bottom: 1rem">
          <strong>{{ facility.name }}</strong>
          <div v-for="court in facility.courts" :key="court.id" class="auth-checkbox">
            <input :id="`court-${court.id}`" v-model="selectedCourtIds" type="checkbox" :value="court.id" />
            <label :for="`court-${court.id}`">{{ court.name }}</label>
          </div>
        </div>

        <button type="button" class="auth-submit" style="max-width: 12rem" :disabled="savingCourts" @click="saveCourts">
          コート選択を保存
        </button>
      </div>

      <div class="operator-card">
        <h2>利用時間（開催日ごと・コートごと）</h2>
        <p v-if="usageError" class="auth-error">{{ usageError }}</p>

        <div v-if="selectedCourts.length === 0" class="operator-empty-state">
          先に使用コートを選択・保存してください。
        </div>
        <template v-else>
          <div
            v-for="(row, index) in usageRows"
            :key="index"
            style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem"
          >
            <select v-model="row.court_id" style="flex: 1">
              <option v-for="court in selectedCourts" :key="court.id" :value="court.id">
                {{ court.facilityName }} - {{ court.name }}
              </option>
            </select>
            <input v-model="row.usage_date" type="date" required />
            <input v-model="row.start_time" type="time" required />
            <span>〜</span>
            <input v-model="row.end_time" type="time" required />
            <button type="button" @click="removeUsageRow(index)">削除</button>
          </div>
          <button type="button" style="margin-bottom: 1rem" @click="addUsageRow">+ 利用時間を追加</button>
          <br />
          <button type="button" class="auth-submit" style="max-width: 12rem" :disabled="savingUsage" @click="saveUsageTimes">
            利用時間を保存
          </button>
        </template>
      </div>
    </template>
  </OperatorShell>
</template>
