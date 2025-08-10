<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';

const saveSuccess = ref(false);

const page = usePage();
const project = page.props.project;

// Project form state
const form = ref({
  label: project?.label ?? '',
  description: project?.description ?? '',
  tags: Array.isArray(project?.tags) ? project.tags : [],
});
const tagsInput = ref(form.value.tags.join(', '));

// Monitor form state
const monitorLabel = ref('');
const monitorType = ref('ping');
const monitorPeriodicity = ref<number | null>(null);
const monitorBadgeLabel = ref('');
const monitorStatus = ref('succeeded');
const monitorHostname = ref('');
const monitorPort = ref<number | null>(null);
const monitorUrl = ref('');
const monitorCheckStatus = ref(false);
const monitorKeywords = ref('');

// Add missing monitors ref - initialize with data from backend
const monitors = ref(page.props.monitors || []);

// Monitor filters
const filterLabel = ref('');
const filterType = ref('');
const filterStatus = ref('');


// Save project handler
function save() {
  // Simulate save
  form.value.tags = tagsInput.value.split(',').map(t => t.trim()).filter(Boolean);
  saveSuccess.value = true;
}

// Animated success message
watch(saveSuccess, (value) => {
  if (value) {
    setTimeout(() => {
      saveSuccess.value = false;
    }, 1000);
  }
});

// Create monitor handler
function createMonitor() {
  if (!monitorLabel.value || !monitorType.value || !monitorPeriodicity.value) return;
  monitors.value.push({
    id: Date.now(),
    label: monitorLabel.value,
    type: monitorType.value,
    latest_status: 'up',
  });
  monitorLabel.value = '';
  monitorType.value = '';
  monitorPeriodicity.value = null;
  monitorBadgeLabel.value = '';
  monitorStatus.value = 'succeeded';
  monitorHostname.value = '';
  monitorPort.value = null;
  monitorUrl.value = '';
  monitorCheckStatus.value = false;
  monitorKeywords.value = '';
}

// Delete monitor handler
function deleteMonitor(id: number) {

  console.log("Deleting monitor [ID]: ", id );
  monitors.value = monitors.value.filter(m => m.id !== id);
  axios
}

// Computed filtered monitors
const filteredMonitors = computed(() => {
  return monitors.value.filter(m => {
    const labelMatch = !filterLabel.value || m.label.includes(filterLabel.value);
    const typeMatch = !filterType.value || m.type === filterType.value;
    const statusMatch = !filterStatus.value || m.latest_status === filterStatus.value;
    return labelMatch && typeMatch && statusMatch;
  });
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Projects', href: '/projects' },
  { title: 'Project Details', href: '#' },
];
</script>

<template>
  <Head title="Project Details" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen flex items-center justify-center">
      <main class="flex-grow flex items-center justify-center">
        <div class="bg-white dark:bg-[#121212] rounded-xl shadow-lg max-w-7xl w-full p-10" style="border: 1px solid white;">
          <h1 class="text-3xl font-extrabold mb-6 tracking-tight drop-shadow-md text-center">
            Project Details
          </h1>
          <!-- Animated success message -->
          <div
            v-if="saveSuccess"
            class="bg-green-500 text-white p-3 rounded fixed top-5 right-5"
          >
            Saved successfully!
          </div>

          <!-- Project Properties Form -->
          <form @submit.prevent="save" class="mb-8">
            <div class="mb-4">
              <label class="block text-sm font-semibold mb-1">Label</label>
              <input
                type="text"
                v-model="form.label"
                class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
              />
            </div>
            <div class="mb-4">
              <label class="block text-sm font-semibold mb-1">Description</label>
              <textarea
                v-model="form.description"
                class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
              ></textarea>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-semibold mb-1">Tags</label>
              <input
                type="text"
                v-model="tagsInput"
                placeholder="Comma separated"
                class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
              />
              <div class="mt-2 flex flex-wrap gap-2">
                <span
                  v-for="tag in form.tags"
                  :key="tag"
                  class="inline-block bg-[#262626] text-xs rounded px-2 py-1"
                >{{ tag }}</span>
              </div>
              <button
                type="submit"
                class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition"
              >Save</button>
            </div>
          </form>

          <!-- Monitor Management -->
          <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">Monitors</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium mb-1">Label</label>
                <input
                  type="text"
                  v-model="monitorLabel"
                  placeholder="Monitor label"
                  class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Type</label>
                <select
                  v-model="monitorType"
                  class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
                >
                  <option value="ping">Ping Monitor</option>
                  <option value="website">Website Monitor</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Periodicity (seconds)</label>
                <input
                  type="number"
                  v-model.number="monitorPeriodicity"
                  placeholder="60" min="5" max="300"
                  class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
                  :class="monitorPeriodicity && (monitorPeriodicity < 5 || monitorPeriodicity > 300)
                    ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                    : ''"
                />
                <small v-if="monitorPeriodicity && (monitorPeriodicity < 5 || monitorPeriodicity > 300)"
                  class="text-red-500 text-xs">
                  Periodicity must be between 5 and 300 seconds
                </small>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Badge Label</label>
                <input
                  type="text"
                  v-model="monitorBadgeLabel"
                  placeholder="Badge label"
                  class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Status</label>
                <select
                  v-model="monitorStatus"
                  class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
                >
                  <option value="succeeded">Succeeded</option>
                  <option value="failed">Failed</option>
                </select>
              </div>
            </div>

            <!-- Ping Monitor Specific Fields -->
            <div
              v-if="monitorType === 'ping'"
              class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg"
            >
              <div>
                <label class="block text-sm font-medium mb-1">Hostname or IP Address</label>
                <input
                  type="text"
                  v-model="monitorHostname"
                  placeholder="example.com or 192.168.1.1"
                  class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
                />
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Port</label>
                <input
                  type="number"
                  v-model.number="monitorPort"
                  placeholder="80" min="1" max="65535"
                  class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
                />
              </div>
            </div>

            <!-- Website Monitor Specific Fields -->
            <div
              v-if="monitorType === 'website'"
              class="grid grid-cols-1 gap-4 mb-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg"
            >
              <div>
                <label class="block text-sm font-medium mb-1">URL</label>
                <input
                  type="url"
                  v-model="monitorUrl"
                  placeholder="https://example.com/page"
                  class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
                />
              </div>
              <div class="flex items-center gap-2">
                <input
                  type="checkbox"
                  v-model="monitorCheckStatus"
                  id="checkStatus"
                  class="rounded border-gray-300"
                />
                <label for="checkStatus" class="text-sm font-medium">
                  Check Status (Fail if status not in 200-299 range)
                </label>
              </div>
              <div>
                <label class="block text-sm font-medium mb-1">Keywords (comma-separated)</label>
                <textarea
                  v-model="monitorKeywords"
                  placeholder="keyword1, keyword2, keyword3"
                  class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900"
                  rows="2"
                ></textarea>
                <small class="text-gray-500">Monitor fails if any keyword is not found in the response</small>
              </div>
            </div>

            <div class="flex justify-end">
              <button
                @click="createMonitor"
                class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                :disabled="!monitorLabel || !monitorType || !monitorPeriodicity || (monitorPeriodicity < 5 || monitorPeriodicity > 300)"
              >
                Create Monitor
              </button>
            </div>
          </div>

          <!-- Monitor Filters -->
          <div class="mb-4 flex gap-4 flex-wrap">
            <input
              type="text"
              v-model="filterLabel"
              placeholder="Filter by label"
              class="px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900"
            />
            <select
              v-model="filterType"
              class="px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900"
            >
              <option value="">All Types</option>
              <option value="ping">Ping</option>
              <option value="website">Website</option>
            </select>
            <select
              v-model="filterStatus"
              class="px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900"
            >
              <option value="">All Statuses</option>
              <option value="up">Up</option>
              <option value="down">Down</option>
            </select>
          </div>

          <!-- Monitors Table -->
          <div class="overflow-x-auto bg-[#171717] dark:bg-[#171717] rounded-lg shadow mb-6">
            <table class="min-w-full divide-y divide-gray-200">
              <thead>
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Label</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Type</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Latest Status</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="monitor in filteredMonitors" :key="monitor.id">
                  <td class="px-4 py-2">{{ monitor.label }}</td>
                  <td class="px-4 py-2">{{ monitor.type.charAt(0).toUpperCase() + monitor.type.slice(1) }}</td>
                  <td class="px-4 py-2">
                    <span
                      v-if="monitor.latest_status === 'up'"
                      class="inline-block bg-emerald-600 text-white text-xs rounded px-2 py-1"
                    >Up</span>
                    <span
                      v-else
                      class="inline-block bg-red-600 text-white text-xs rounded px-2 py-1"
                    >Down</span>
                  </td>
                  <td class="px-4 py-2">
                    <button
                      @click="deleteMonitor(monitor.id)"
                      class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition"
                    >Delete</button>
                  </td>
                </tr>
                <tr v-if="filteredMonitors.length === 0">
                  <td colspan="4" class="px-4 py-6 text-center text-gray-500">No monitors found.</td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- TODO: Pagination does not work -->
        </div>
      </main>
    </div>
  </AppLayout>
</template>