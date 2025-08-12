<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
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

// Monitor filters - now just label
const filterLabel = ref('');
const filterType = ref('');
const filterStatus = ref('');

// Add sorting for type and status
const typeDirection = ref<'all' | 'ping' | 'website'>('all');
const statusDirection = ref<'all' | 'succeeded' | 'failed'>('all');

// Pagination state - now dynamic
const viewportHeight = ref(window.innerHeight);
const currentPage = ref(1);

// Calculate items per page based on viewport height
const pageSize = computed(() => {
  // Estimate available height for table
  const headerHeight = 300; // approximate height of header, project info, filters, etc.
  const paginationHeight = 80; // pagination controls height
  const tableHeaderHeight = 50; // table header height
  const availableHeight = viewportHeight.value - headerHeight - paginationHeight - tableHeaderHeight;
  
  // Each row is approximately 56px (h-14 class)
  const rowHeight = 56;
  const maxRows = Math.floor(availableHeight / rowHeight);
  
  // Minimum 5 rows, maximum 20 rows
  return Math.max(5, Math.min(20, maxRows));
});

// Handle window resize
function handleResize() {
  viewportHeight.value = window.innerHeight;
}

onMounted(() => {
  window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
});

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

// Delete monitor handler
function deleteMonitor(id: number) {
  monitors.value = monitors.value.filter(m => m.id !== id);
  axios.delete(`/api/v1/monitors/${id}`)
    .then(() => {
      console.log("Monitor successfully deleted.");
    })
    .catch(err => {
      console.error('Failed to delete monitor', err);
    });
}

// Computed filtered monitors
const filteredMonitors = computed(() => {
  return monitors.value.filter(m => {
    const labelMatch = !filterLabel.value || m.label.includes(filterLabel.value);
    const typeMatch = typeDirection.value === 'all' || m.monitor_type === typeDirection.value;
    const statusMatch = statusDirection.value === 'all' || m.latest_status === statusDirection.value;
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

function cycleTypeFilter() {
  if (typeDirection.value === 'all') {
    typeDirection.value = 'ping';
  } else if (typeDirection.value === 'ping') {
    typeDirection.value = 'website';
  } else {
    typeDirection.value = 'all';
  }
}

function cycleStatusFilter() {
  if (statusDirection.value === 'all') {
    statusDirection.value = 'succeeded';
  } else if (statusDirection.value === 'succeeded') {
    statusDirection.value = 'failed';
  } else {
    statusDirection.value = 'all';
  }
}
</script>

<template>

  <Head title="Project Details" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl mx-auto mt-8">
      <div class="flex flex-col gap-4 ">

          <h1 class="text-3xl font-extrabold mb-6 tracking-tight drop-shadow-md text-center">
            Project Details
          </h1>
          <!-- Animated success message -->
          <div v-if="saveSuccess" class="bg-green-500 text-white p-3 rounded fixed top-5 right-5">
            Saved successfully!
          </div>

          <!-- Project Properties Section -->
          <div>
            <div class="flex justify-between items-center mb-2">
              <h2 class="text-lg font-bold">Project Information</h2>
              <button @click="showProjectForm = !showProjectForm"
                class="px-3 py-1 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition text-sm">
                {{ showProjectForm ? 'Cancel' : 'Edit' }}
              </button>
            </div>

            <!-- Project Info Display (when not editing) -->
            <div v-if="!showProjectForm" class="p-3 border rounded-lg"
              :style="{ background: '#171717', color: '#fff', borderColor: 'white' }">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                  <span class="text-xs font-semibold text-gray-300">Label:</span>
                  <p class="text-sm">{{ form.label || 'No label set' }}</p>
                </div>
                <div>
                  <span class="text-xs font-semibold text-gray-300">Description:</span>
                  <p class="text-sm text-gray-200">{{ form.description || 'No description set' }}</p>
                </div>
                <div>
                  <span class="text-xs font-semibold text-gray-300">Tags:</span>
                  <div class="mt-1 flex flex-wrap gap-1">
                    <span v-for="tag in form.tags" :key="tag"
                      class="inline-block bg-[#262626] text-xs rounded px-1 py-0.5 text-white">{{ tag }}</span>
                    <span v-if="form.tags.length === 0" class="text-gray-400 text-xs">No tags set</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Collapsible Project Form -->
            <div v-if="showProjectForm" class="p-3 border rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
              <h3 class="text-base font-semibold mb-3">Edit Project Information</h3>

              <form @submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                  <div>
                    <label class="block text-xs font-semibold mb-1">Label</label>
                    <input type="text" v-model="form.label"
                      class="w-full px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900 text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs font-semibold mb-1">Description</label>
                    <textarea v-model="form.description"
                      class="w-full px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900 text-sm" rows="2"></textarea>
                  </div>
                  <div>
                    <label class="block text-xs font-semibold mb-1">Tags</label>
                    <input type="text" v-model="tagsInput" placeholder="Comma separated"
                      class="w-full px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900 text-sm" />
                  </div>
                </div>

                <div class="flex justify-end gap-2">
                  <button type="button" @click="showProjectForm = false"
                    class="px-3 py-1 border rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm">
                    Cancel
                  </button>
                  <button type="submit"
                    class="px-3 py-1 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition text-sm">
                    Save Project
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Monitor Management -->
          <div class="mb-8">
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

          <!-- Monitor Filters with Title and Create Button -->
          <div class="mb-4 flex justify-between items-center gap-4 flex-wrap">
            <div class="flex items-center gap-4">
              <h2 class="text-xl font-bold">Monitors</h2>
              <input type="text" v-model="filterLabel" placeholder="Filter by label"
                class="px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900" />
            </div>
            <div class="flex items-center gap-4">
              <!-- Pagination Controls -->
              <div v-if="totalPages > 1" class="flex items-center gap-2">
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
              <button @click="showCreateForm = !showCreateForm"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                {{ showCreateForm ? 'Cancel' : 'Create Monitor' }}
              </button>
            </div>
          </div>

          <!-- Monitors Table -->
          <div class="overflow-x-auto bg-[#171717] dark:bg-[#171717] rounded-lg shadow border-2 border-white" :style="`min-height: ${pageSize * 56}px`">
            <table class="min-w-full divide-y divide-gray-200">
              <thead>
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Label</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">
                    <button
                      @click="cycleTypeFilter"
                      class="text-gray-700 hover:text-blue-500 text-xs font-semibold cursor-pointer"
                      :class="{ 
                        'text-blue-500': typeDirection !== 'all'
                      }"
                      :title="typeDirection === 'all' ? 'Show All Types' : 
                             typeDirection === 'ping' ? 'Show Ping Only' : 
                             'Show Website Only'"
                    >
                      Type {{ typeDirection === 'all' ? '•' : typeDirection === 'ping' ? 'P' : 'W' }}
                    </button>
                  </th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">
                    <button
                      @click="cycleStatusFilter"
                      class="text-gray-700 hover:text-blue-500 text-xs font-semibold cursor-pointer"
                      :class="{ 
                        'text-blue-500': statusDirection !== 'all'
                      }"
                      :title="statusDirection === 'all' ? 'Show All Statuses' : 
                             statusDirection === 'succeeded' ? 'Show Succeeded Only' : 
                             'Show Failed Only'"
                    >
                      Latest Status {{ statusDirection === 'all' ? '•' : statusDirection === 'succeeded' ? '✓' : '✗' }}
                    </button>
                  </th>
                  <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="monitor in paginatedMonitors" :key="monitor.id">
                  <td class="px-4 py-2">{{ monitor.label }}</td>
                  <td class="px-4 py-2">
                    {{ monitor.monitor_type ? (monitor.monitor_type.charAt(0).toUpperCase() + monitor.monitor_type.slice(1)) : 'Unknown' }}
                  </td>
                  <td class="px-4 py-2">
                    <span v-if="monitor.latest_status === 'succeeded'"
                      class="inline-block bg-emerald-600 text-white text-xs rounded px-2 py-1">Succeeded</span>
                    <span v-else class="inline-block bg-red-600 text-white text-xs rounded px-2 py-1">Failed</span>
                  </td>
                  <td class="px-4 py-2">
                    <div class="flex flex-col sm:flex-row gap-1 sm:gap-2">
                      <button @click="openEditForm(monitor)"
                        class="px-2 py-1 text-xs bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">
                        Edit
                      </button>
                      <button @click="deleteMonitor(monitor.id)"
                        class="px-2 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600 transition">Delete</button>
                      <button @click="goToMonitorDetails(monitor.id)"
                        class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition">Details</button>
                    </div>
                  </td>
                </tr>
                <!-- Add empty rows to maintain fixed height -->
                <tr v-for="n in Math.max(0, pageSize - paginatedMonitors.length)" :key="'empty-' + n" class="h-14">
                  <td class="px-4 py-2" colspan="4">&nbsp;</td>
                </tr>
                <tr v-if="paginatedMonitors.length === 0">
                  <td colspan="4" class="px-4 py-6 text-center text-gray-500">No monitors found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
  </AppLayout>
</template>