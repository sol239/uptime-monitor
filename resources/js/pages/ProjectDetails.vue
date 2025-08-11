<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const saveSuccess = ref(false);

const page = usePage();
const project = page.props.project;

// Project form state
const showProjectForm = ref(false);
const form = ref({
  label: project?.label ?? '',
  description: project?.description ?? '',
  tags: Array.isArray(project?.tags) ? project.tags : [],
});
const tagsInput = ref(form.value.tags.join(', '));

// Monitor form state
const showCreateForm = ref(false);
const showEditForm = ref(false);
const editingMonitor = ref<any>(null);

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

// Pagination constants and state
const PAGINATION = {
  PAGE_SIZE: 2
};
const currentPage = ref(1);
const pageSize = ref(PAGINATION.PAGE_SIZE);

// Save project handler
function save() {
  // Parse tags from input
  form.value.tags = tagsInput.value.split(',').map(t => t.trim()).filter(Boolean);

  // Call API to update project
  axios.put(`/api/v1/projects/${project.id}`, {
    label: form.value.label,
    tags: form.value.tags
  })
    .then(response => {
      console.log('Project updated successfully:', response.data);
      saveSuccess.value = true;
    })
    .catch(error => {
      console.error('Failed to update project:', error);
      // You might want to show an error message to the user here
    });
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

  const newMonitor = {
    label: monitorLabel.value,
    type: monitorType.value,
    periodicity: monitorPeriodicity.value,
    badge_label: monitorBadgeLabel.value,
    status: monitorStatus.value,
    project_id: project.id
  };

  // Add type-specific fields
  if (monitorType.value === 'ping') {
    newMonitor.hostname = monitorHostname.value;
    newMonitor.port = monitorPort.value;
  } else if (monitorType.value === 'website') {
    newMonitor.url = monitorUrl.value;
    newMonitor.check_status = monitorCheckStatus.value;
    newMonitor.keywords = monitorKeywords.value.split(',').map(k => k.trim()).filter(k => k);
  }

  axios.post('/api/v1/monitors', newMonitor)
    .then(response => {
      monitors.value.push(response.data);
      resetMonitorForm();
      showCreateForm.value = false;
    })
    .catch(error => {
      console.error('Failed to create monitor:', error);
    });
}

function resetMonitorForm() {
  monitorLabel.value = '';
  monitorType.value = 'ping';
  monitorPeriodicity.value = null;
  monitorBadgeLabel.value = '';
  monitorStatus.value = 'succeeded';
  monitorHostname.value = '';
  monitorPort.value = null;
  monitorUrl.value = '';
  monitorCheckStatus.value = false;
  monitorKeywords.value = '';
}

function openEditForm(monitor: any) {
  editingMonitor.value = monitor;
  monitorLabel.value = monitor.label;
  monitorType.value = monitor.monitor_type;
  monitorPeriodicity.value = monitor.periodicity;
  monitorBadgeLabel.value = monitor.badge_label || '';
  monitorStatus.value = monitor.status;

  if (monitor.monitor_type === 'ping') {
    monitorHostname.value = monitor.hostname || '';
    monitorPort.value = monitor.port;
  } else if (monitor.monitor_type === 'website') {
    monitorUrl.value = monitor.url || '';
    monitorCheckStatus.value = monitor.check_status || false;
    monitorKeywords.value = (monitor.keywords || []).join(', ');
  }

  showEditForm.value = true;
}

function updateMonitor() {
  if (!editingMonitor.value) return;

  const updatedMonitor = {
    label: monitorLabel.value,
    type: monitorType.value,
    periodicity: monitorPeriodicity.value,
    badge_label: monitorBadgeLabel.value,
    status: monitorStatus.value
  };

  // Add type-specific fields
  if (monitorType.value === 'ping') {
    updatedMonitor.hostname = monitorHostname.value;
    updatedMonitor.port = monitorPort.value;
  } else if (monitorType.value === 'website') {
    updatedMonitor.url = monitorUrl.value;
    updatedMonitor.check_status = monitorCheckStatus.value;
    updatedMonitor.keywords = monitorKeywords.value.split(',').map(k => k.trim()).filter(k => k);
  }

  console.log("Updating monitor [ID]: ", editingMonitor.value.id, updatedMonitor);

  axios.put(`/api/v1/monitors/${editingMonitor.value.id}`, updatedMonitor)
    .then(response => {
      const index = monitors.value.findIndex(m => m.id === editingMonitor.value.id);
      if (index !== -1) {
        monitors.value[index] = response.data;
      }
      resetMonitorForm();
      showEditForm.value = false;
      editingMonitor.value = null;
    })
    .catch(error => {
      console.error('Failed to update monitor:', error);
    });
}

function cancelEdit() {
  resetMonitorForm();
  showEditForm.value = false;
  editingMonitor.value = null;
}

// Computed filtered monitors
const filteredMonitors = computed(() => {
  return monitors.value.filter(m => {
    const labelMatch = !filterLabel.value || m.label.includes(filterLabel.value);
    const typeMatch = !filterType.value || m.monitor_type === filterType.value;
    const statusMatch = !filterStatus.value || m.latest_status === filterStatus.value;
    return labelMatch && typeMatch && statusMatch;
  });
});

// Paginated monitors
const paginatedMonitors = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return filteredMonitors.value.slice(start, start + pageSize.value);
});
const totalPages = computed(() => Math.ceil(filteredMonitors.value.length / pageSize.value));

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Projects', href: '/projects' },
  { title: `${project.label}`, href: `/projects/${project.id}` },
];

function goToMonitorDetails(monitorId: number) {
  window.location.href = `/projects/${project.id}/monitors/${monitorId}`;
}
</script>

<template>

  <Head title="Project Details" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-screen flex items-center justify-center">
      <main class="flex-grow flex items-center justify-center">
        <div class="bg-white dark:bg-[#121212] rounded-xl shadow-lg max-w-7xl w-full p-10"
          style="border: 1px solid white;">
          <h1 class="text-3xl font-extrabold mb-6 tracking-tight drop-shadow-md text-center">
            Project Details
          </h1>
          <!-- Animated success message -->
          <div v-if="saveSuccess" class="bg-green-500 text-white p-3 rounded fixed top-5 right-5">
            Saved successfully!
          </div>

          <!-- Project Properties Section -->
          <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold">Project Information</h2>
              <button @click="showProjectForm = !showProjectForm"
                class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition">
                {{ showProjectForm ? 'Cancel' : 'Edit Project' }}
              </button>
            </div>

            <!-- Project Info Display (when not editing) -->
            <div v-if="!showProjectForm" class="p-4 border rounded-lg"
              :style="{ background: '#171717', color: '#fff', borderColor: 'white' }">
              <div class="mb-3">
                <span class="text-sm font-semibold text-gray-300">Label:</span>
                <p class="text-lg">{{ form.label || 'No label set' }}</p>
              </div>
              <div class="mb-3">
                <span class="text-sm font-semibold text-gray-300">Description:</span>
                <p class="text-gray-200">{{ form.description || 'No description set' }}</p>
              </div>
              <div>
                <span class="text-sm font-semibold text-gray-300">Tags:</span>
                <div class="mt-2 flex flex-wrap gap-2">
                  <span v-for="tag in form.tags" :key="tag"
                    class="inline-block bg-[#262626] text-xs rounded px-2 py-1 text-white">{{ tag }}</span>
                  <span v-if="form.tags.length === 0" class="text-gray-400 text-sm">No tags set</span>
                </div>
              </div>
            </div>

            <!-- Collapsible Project Form -->
            <div v-if="showProjectForm" class="p-4 border rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
              <h3 class="text-lg font-semibold mb-4">Edit Project Information</h3>

              <form @submit.prevent="save">
                <div class="mb-4">
                  <label class="block text-sm font-semibold mb-1">Label</label>
                  <input type="text" v-model="form.label"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
                <div class="mb-4">
                  <label class="block text-sm font-semibold mb-1">Description</label>
                  <textarea v-model="form.description"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" rows="3"></textarea>
                </div>
                <div class="mb-4">
                  <label class="block text-sm font-semibold mb-1">Tags</label>
                  <input type="text" v-model="tagsInput" placeholder="Comma separated"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                  <div class="mt-2 flex flex-wrap gap-2">
                    <span v-for="tag in form.tags" :key="tag"
                      class="inline-block bg-[#262626] text-xs rounded px-2 py-1">{{ tag }}</span>
                  </div>
                </div>

                <div class="flex justify-end gap-2">
                  <button type="button" @click="showProjectForm = false"
                    class="px-4 py-2 border rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                    Cancel
                  </button>
                  <button type="submit"
                    class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition">
                    Save Project
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Monitor Management -->
          <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-bold">Monitors</h2>
              <button @click="showCreateForm = !showCreateForm"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                {{ showCreateForm ? 'Cancel' : 'Create Monitor' }}
              </button>
            </div>

            <!-- Collapsible Create Monitor Form -->
            <div v-if="showCreateForm" class="mb-6 p-4 border rounded-lg bg-gray-50 dark:bg-gray-800">
              <h3 class="text-lg font-semibold mb-4">Create New Monitor</h3>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Label</label>
                  <input type="text" v-model="monitorLabel" placeholder="Monitor label"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Type</label>
                  <select v-model="monitorType" class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900">
                    <option value="ping">Ping Monitor</option>
                    <option value="website">Website Monitor</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Periodicity (seconds)</label>
                  <input type="number" v-model.number="monitorPeriodicity" placeholder="60" min="5" max="300"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" :class="monitorPeriodicity && (monitorPeriodicity < 5 || monitorPeriodicity > 300)
                      ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                      : ''" />
                  <small v-if="monitorPeriodicity && (monitorPeriodicity < 5 || monitorPeriodicity > 300)"
                    class="text-red-500 text-xs">
                    Periodicity must be between 5 and 300 seconds
                  </small>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Badge Label</label>
                  <input type="text" v-model="monitorBadgeLabel" placeholder="Badge label"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Status</label>
                  <select v-model="monitorStatus" class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900">
                    <option value="succeeded">Succeeded</option>
                    <option value="failed">Failed</option>
                  </select>
                </div>
              </div>

              <!-- Ping Monitor Specific Fields -->
              <div v-if="monitorType === 'ping'"
                class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div>
                  <label class="block text-sm font-medium mb-1">Hostname or IP Address</label>
                  <input type="text" v-model="monitorHostname" placeholder="example.com or 192.168.1.1"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Port</label>
                  <input type="number" v-model.number="monitorPort" placeholder="80" min="1" max="65535"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
              </div>

              <!-- Website Monitor Specific Fields -->
              <div v-if="monitorType === 'website'"
                class="grid grid-cols-1 gap-4 mb-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div>
                  <label class="block text-sm font-medium mb-1">URL</label>
                  <input type="url" v-model="monitorUrl" placeholder="https://example.com/page"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
                <div class="flex items-center gap-2">
                  <input type="checkbox" v-model="monitorCheckStatus" id="checkStatus"
                    class="rounded border-gray-300" />
                  <label for="checkStatus" class="text-sm font-medium">
                    Check Status (Fail if status not in 200-299 range)
                  </label>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Keywords (comma-separated)</label>
                  <textarea v-model="monitorKeywords" placeholder="keyword1, keyword2, keyword3"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" rows="2"></textarea>
                  <small class="text-gray-500">Monitor fails if any keyword is not found in the response</small>
                </div>
              </div>

              <div class="flex justify-end gap-2">
                <button @click="showCreateForm = false; resetMonitorForm()"
                  class="px-4 py-2 border rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                  Cancel
                </button>
                <button @click="createMonitor"
                  class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                  :disabled="!monitorLabel || !monitorType || !monitorPeriodicity || (monitorPeriodicity < 5 || monitorPeriodicity > 300)">
                  Create Monitor
                </button>
              </div>
            </div>

            <!-- Collapsible Edit Monitor Form -->
            <div v-if="showEditForm" class="mb-6 p-4 border rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
              <h3 class="text-lg font-semibold mb-4">Edit Monitor</h3>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Label</label>
                  <input type="text" v-model="monitorLabel" placeholder="Monitor label"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Type</label>
                  <select v-model="monitorType" class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900">
                    <option value="ping">Ping Monitor</option>
                    <option value="website">Website Monitor</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Periodicity (seconds)</label>
                  <input type="number" v-model.number="monitorPeriodicity" placeholder="60" min="5" max="300"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" :class="monitorPeriodicity && (monitorPeriodicity < 5 || monitorPeriodicity > 300)
                      ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                      : ''" />
                  <small v-if="monitorPeriodicity && (monitorPeriodicity < 5 || monitorPeriodicity > 300)"
                    class="text-red-500 text-xs">
                    Periodicity must be between 5 and 300 seconds
                  </small>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Badge Label</label>
                  <input type="text" v-model="monitorBadgeLabel" placeholder="Badge label"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Status</label>
                  <select v-model="monitorStatus" class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900">
                    <option value="succeeded">Succeeded</option>
                    <option value="failed">Failed</option>
                  </select>
                </div>
              </div>

              <!-- Ping Monitor Specific Fields -->
              <div v-if="monitorType === 'ping'"
                class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div>
                  <label class="block text-sm font-medium mb-1">Hostname or IP Address</label>
                  <input type="text" v-model="monitorHostname" placeholder="example.com or 192.168.1.1"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Port</label>
                  <input type="number" v-model.number="monitorPort" placeholder="80" min="1" max="65535"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
              </div>

              <!-- Website Monitor Specific Fields -->
              <div v-if="monitorType === 'website'"
                class="grid grid-cols-1 gap-4 mb-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                <div>
                  <label class="block text-sm font-medium mb-1">URL</label>
                  <input type="url" v-model="monitorUrl" placeholder="https://example.com/page"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" />
                </div>
                <div class="flex items-center gap-2">
                  <input type="checkbox" v-model="monitorCheckStatus" id="checkStatus"
                    class="rounded border-gray-300" />
                  <label for="checkStatus" class="text-sm font-medium">
                    Check Status (Fail if status not in 200-299 range)
                  </label>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Keywords (comma-separated)</label>
                  <textarea v-model="monitorKeywords" placeholder="keyword1, keyword2, keyword3"
                    class="w-full px-3 py-2 rounded border bg-gray-50 dark:bg-zinc-900" rows="2"></textarea>
                  <small class="text-gray-500">Monitor fails if any keyword is not found in the response</small>
                </div>
              </div>

              <div class="flex justify-end gap-2">
                <button @click="cancelEdit" class="px-4 py-2 border rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                  Cancel
                </button>
                <button @click="updateMonitor"
                  class="px-6 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
                  :disabled="!monitorLabel || !monitorType || !monitorPeriodicity || (monitorPeriodicity < 5 || monitorPeriodicity > 300)">
                  Update Monitor
                </button>
              </div>
            </div>
          </div>

          <!-- Monitor Filters -->
          <div class="mb-4 flex gap-4 flex-wrap">
            <input type="text" v-model="filterLabel" placeholder="Filter by label"
              class="px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900" />
            <select v-model="filterType" class="px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900">
              <option value="">All Types</option>
              <option value="ping">Ping</option>
              <option value="website">Website</option>
            </select>
            <select v-model="filterStatus" class="px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900">
              <option value="">All Statuses</option>
              <option value="succeeded">Succeeded</option>
              <option value="failed">Failed</option>
            </select>
          </div>

          <!-- Monitors Table -->
          <div class="overflow-x-auto bg-[#171717] dark:bg-[#171717] rounded-lg shadow mb-6"
            style="border: 2px solid white;">
            <table class="min-w-full divide-y divide-gray-200 ">
              <thead>
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Label</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Type</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Latest Status</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="monitor in paginatedMonitors" :key="monitor.id">
                  <td class="px-4 py-2">{{ monitor.label }}</td>
                  <td class="px-4 py-2">{{ monitor.monitor_type.charAt(0).toUpperCase() + monitor.monitor_type.slice(1) }}</td>
                  <td class="px-4 py-2">
                    <span v-if="monitor.latest_status === 'succeeded'"
                      class="inline-block bg-emerald-600 text-white text-xs rounded px-2 py-1">Succeeded</span>
                    <span v-else class="inline-block bg-red-600 text-white text-xs rounded px-2 py-1">Failed</span>
                  </td>
                  <td class="px-4 py-2">
                    <button @click="openEditForm(monitor)"
                      class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition mr-2">
                      Edit
                    </button>
                    <button @click="deleteMonitor(monitor.id)"
                      class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition mr-2">Delete</button>

                    <button @click="goToMonitorDetails(monitor.id)"
                      class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition">Details</button>
                  </td>
                </tr>
                <tr v-if="paginatedMonitors.length === 0">
                  <td colspan="4" class="px-4 py-6 text-center text-gray-500">No monitors found.</td>
                </tr>
              </tbody>
            </table>
            <!-- Pagination Controls -->
            <div v-if="totalPages > 1" class="flex justify-center items-center gap-2 py-4">
              <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1"
                class="px-3 py-1 rounded border bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 disabled:opacity-50">
                Prev
              </button>
              <span class="mx-2 text-sm">
                Page {{ currentPage }} of {{ totalPages }}
              </span>
              <button @click="currentPage = Math.min(totalPages, currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="px-3 py-1 rounded border bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 disabled:opacity-50">
                Next
              </button>
            </div>
          </div>
          <!-- TODO: Pagination does not work -->
        </div>
      </main>
    </div>
  </AppLayout>
</template>