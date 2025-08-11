<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const monitor = ref(page.props.monitor);
const project = ref(page.props.project);

const saveSuccess = ref(false);
const showMonitorForm = ref(false);

const form = ref({
  label: monitor.value.label,
  periodicity: monitor.value.periodicity,
  type: monitor.value.type,
  badge_label: monitor.value.badge_label || '',
  status: monitor.value.status,
  hostname: monitor.value.hostname || '',
  port: monitor.value.port || null,
  url: monitor.value.url || '',
  check_status: monitor.value.check_status || false,
  keywords: Array.isArray(monitor.value.keywords) ? monitor.value.keywords.join(', ') : '',
});

const historyMode = ref('status');
const history = ref([]);
const loadingHistory = ref(false);
let intervalId: any = null;

// Save monitor handler
function saveMonitor() {
  const payload: any = {
    label: form.value.label,
    periodicity: form.value.periodicity,
    type: form.value.type,
    badge_label: form.value.badge_label,
    status: form.value.status,
  };
  if (form.value.type === 'ping') {
    payload.hostname = form.value.hostname;
    payload.port = form.value.port;
  } else if (form.value.type === 'website') {
    payload.url = form.value.url;
    payload.check_status = form.value.check_status;
    payload.keywords = form.value.keywords.split(',').map(k => k.trim()).filter(Boolean);
  }
  axios.put(`/api/v1/monitors/${monitor.value.id}`, payload)
    .then(res => {
      monitor.value = res.data;
      saveSuccess.value = true;
      showMonitorForm.value = false;
    })
    .catch(() => {});
}

watch(saveSuccess, (v) => {
  if (v) setTimeout(() => saveSuccess.value = false, 1000);
});

// History fetch and live update
async function fetchHistory() {
  loadingHistory.value = true;
  try {
    const { data } = await axios.get(`/api/v1/monitors/${monitor.value.id}/history`, {
      params: { mode: historyMode.value }
    });
    history.value = data;
  } catch (e) {
    history.value = [];
  }
  loadingHistory.value = false;
}
onMounted(() => {
  fetchHistory();
  intervalId = setInterval(fetchHistory, 5000);
});
onUnmounted(() => {
  clearInterval(intervalId);
});
watch(historyMode, () => {
  fetchHistory();
});
</script>

<template>
  <Head :title="`Monitor Details - ${form.label}`" />
  <AppLayout>
    <div class="min-h-screen flex items-center justify-center">
      <main class="flex-grow flex items-center justify-center">
        <div class="bg-white dark:bg-[#121212] rounded-xl shadow-lg max-w-4xl w-full p-10"
          style="border: 1px solid white;">
          <h1 class="text-3xl font-extrabold mb-6 tracking-tight drop-shadow-md text-center">
            Monitor Details
          </h1>
          <div v-if="saveSuccess" class="bg-green-500 text-white p-3 rounded fixed top-5 right-5">
            Saved successfully!
          </div>

          <!-- Monitor Info Section -->
          <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold">Monitor Information</h2>
              <button @click="showMonitorForm = !showMonitorForm"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                {{ showMonitorForm ? 'Cancel' : 'Edit Monitor' }}
              </button>
            </div>

            <!-- Info Display -->
            <div v-if="!showMonitorForm" class="p-4 border rounded-lg"
              :style="{ background: '#171717', color: '#fff', borderColor: 'white' }">
              <div class="mb-3">
                <span class="text-sm font-semibold text-gray-300">Label:</span>
                <p class="text-lg">{{ monitor.label || 'No label set' }}</p>
              </div>
              <div class="mb-3">
                <span class="text-sm font-semibold text-gray-300">Type:</span>
                <p class="text-lg">{{ monitor.type }}</p>
              </div>
              <div class="mb-3">
                <span class="text-sm font-semibold text-gray-300">Periodicity:</span>
                <p class="text-lg">{{ monitor.periodicity }}s</p>
              </div>
              <div class="mb-3">
                <span class="text-sm font-semibold text-gray-300">Badge Label:</span>
                <p class="text-lg">{{ monitor.badge_label }}</p>
              </div>
              <div class="mb-3">
                <span class="text-sm font-semibold text-gray-300">Status:</span>
                <p class="text-lg">{{ monitor.status }}</p>
              </div>
              <div v-if="monitor.type === 'ping'" class="mb-3">
                <span class="text-sm font-semibold text-gray-300">Hostname:</span>
                <p class="text-lg">{{ monitor.hostname }}</p>
                <span class="text-sm font-semibold text-gray-300">Port:</span>
                <p class="text-lg">{{ monitor.port }}</p>
              </div>
              <div v-if="monitor.type === 'website'" class="mb-3">
                <span class="text-sm font-semibold text-gray-300">URL:</span>
                <p class="text-lg">{{ monitor.url }}</p>
                <span class="text-sm font-semibold text-gray-300">Check Status:</span>
                <p class="text-lg">{{ monitor.check_status ? 'Yes' : 'No' }}</p>
                <span class="text-sm font-semibold text-gray-300">Keywords:</span>
                <div class="mt-2 flex flex-wrap gap-2">
                  <span v-for="kw in monitor.keywords" :key="kw"
                    class="inline-block bg-[#262626] text-xs rounded px-2 py-1 text-white">{{ kw }}</span>
                  <span v-if="!monitor.keywords || monitor.keywords.length === 0" class="text-gray-400 text-sm">No keywords set</span>
                </div>
              </div>
            </div>

            <!-- Collapsible Monitor Form -->
            <div v-if="showMonitorForm" class="p-4 border rounded-lg bg-blue-50 dark:bg-blue-900/20">
              <h3 class="text-lg font-semibold mb-4">Edit Monitor Information</h3>
              <form @submit.prevent="saveMonitor">
                <div class="mb-4">
                  <label class="block text-sm font-semibold mb-1">Label</label>
                  <input type="text" v-model="form.label"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
                <div class="mb-4">
                  <label class="block text-sm font-semibold mb-1">Type</label>
                  <select v-model="form.type" class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900">
                    <option value="ping">Ping Monitor</option>
                    <option value="website">Website Monitor</option>
                  </select>
                </div>
                <div class="mb-4">
                  <label class="block text-sm font-semibold mb-1">Periodicity (seconds)</label>
                  <input type="number" v-model.number="form.periodicity" placeholder="60" min="5" max="300"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
                    :class="form.periodicity && (form.periodicity < 5 || form.periodicity > 300)
                      ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                      : ''" />
                  <small v-if="form.periodicity && (form.periodicity < 5 || form.periodicity > 300)"
                    class="text-red-500 text-xs">
                    Periodicity must be between 5 and 300 seconds
                  </small>
                </div>
                <div class="mb-4">
                  <label class="block text-sm font-semibold mb-1">Badge Label</label>
                  <input type="text" v-model="form.badge_label" placeholder="Badge label"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
                <div class="mb-4">
                  <label class="block text-sm font-semibold mb-1">Status</label>
                  <select v-model="form.status" class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900">
                    <option value="succeeded">Succeeded</option>
                    <option value="failed">Failed</option>
                  </select>
                </div>
                <!-- Ping Monitor Specific Fields -->
                <div v-if="form.type === 'ping'"
                  class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <div>
                    <label class="block text-sm font-medium mb-1">Hostname or IP Address</label>
                    <input type="text" v-model="form.hostname" placeholder="example.com or 192.168.1.1"
                      class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Port</label>
                    <input type="number" v-model.number="form.port" placeholder="80" min="1" max="65535"
                      class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                  </div>
                </div>
                <!-- Website Monitor Specific Fields -->
                <div v-if="form.type === 'website'"
                  class="grid grid-cols-1 gap-4 mb-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                  <div>
                    <label class="block text-sm font-medium mb-1">URL</label>
                    <input type="url" v-model="form.url" placeholder="https://example.com/page"
                      class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                  </div>
                  <div class="flex items-center gap-2">
                    <input type="checkbox" v-model="form.check_status" id="checkStatus"
                      class="rounded border-gray-300" />
                    <label for="checkStatus" class="text-sm font-medium">
                      Check Status (Fail if status not in 200-299 range)
                    </label>
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Keywords (comma-separated)</label>
                    <textarea v-model="form.keywords" placeholder="keyword1, keyword2, keyword3"
                      class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" rows="2"></textarea>
                    <small class="text-gray-500">Monitor fails if any keyword is not found in the response</small>
                  </div>
                </div>
                <div class="flex justify-end gap-2">
                  <button type="button" @click="showMonitorForm = false"
                    class="px-4 py-2 border rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    Cancel
                  </button>
                  <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Save Monitor
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Monitor History Section -->
          <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold">Monitor History</h2>
              <div>
                <label class="mr-2">Mode:</label>
                <select v-model="historyMode" class="px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900">
                  <option value="status">Status</option>
                  <option value="response">Response</option>
                  <option value="latency">Latency</option>
                </select>
              </div>
            </div>
            <div class="overflow-x-auto bg-[#171717] dark:bg-[#171717] rounded-lg shadow mb-6"
              style="border: 2px solid white;">
              <div v-if="loadingHistory" class="text-gray-500 p-4">Loading...</div>
              <div v-else>
                <table v-if="historyMode === 'status'" class="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Timestamp</th>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in history" :key="item.id">
                      <td class="px-4 py-2">{{ item.timestamp }}</td>
                      <td class="px-4 py-2">
                        <span v-if="item.status === 'up' || item.status === 'succeeded'"
                          class="inline-block bg-emerald-600 text-white text-xs rounded px-2 py-1">Up</span>
                        <span v-else class="inline-block bg-red-600 text-white text-xs rounded px-2 py-1">Down</span>
                      </td>
                    </tr>
                    <tr v-if="history.length === 0">
                      <td colspan="2" class="px-4 py-6 text-center text-gray-500">No history found.</td>
                    </tr>
                  </tbody>
                </table>
                <table v-else-if="historyMode === 'response'" class="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Timestamp</th>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Response</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in history" :key="item.id">
                      <td class="px-4 py-2">{{ item.timestamp }}</td>
                      <td class="px-4 py-2">{{ item.response }}</td>
                    </tr>
                    <tr v-if="history.length === 0">
                      <td colspan="2" class="px-4 py-6 text-center text-gray-500">No history found.</td>
                    </tr>
                  </tbody>
                </table>
                <table v-else-if="historyMode === 'latency'" class="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Timestamp</th>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Latency (ms)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in history" :key="item.id">
                      <td class="px-4 py-2">{{ item.timestamp }}</td>
                      <td class="px-4 py-2">{{ item.latency }}</td>
                    </tr>
                    <tr v-if="history.length === 0">
                      <td colspan="2" class="px-4 py-6 text-center text-gray-500">No history found.</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </AppLayout>
</template>
