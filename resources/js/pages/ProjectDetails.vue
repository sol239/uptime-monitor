<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

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
    form.value.tags = tagsInput.value
        .split(',')
        .map((t) => t.trim())
        .filter(Boolean);

    // Call API to update project
    axios
        .put(`/api/v1/projects/${project.id}`, {
            label: form.value.label,
            tags: form.value.tags,
        })
        .then((response) => {
            console.log('Project updated successfully:', response.data);
            saveSuccess.value = true;
        })
        .catch((error) => {
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
        project_id: project.id,
    };

    // Add type-specific fields
    if (monitorType.value === 'ping') {
        newMonitor.hostname = monitorHostname.value;
        newMonitor.port = monitorPort.value;
    } else if (monitorType.value === 'website') {
        newMonitor.url = monitorUrl.value;
        newMonitor.check_status = monitorCheckStatus.value;
        newMonitor.keywords = monitorKeywords.value
            .split(',')
            .map((k) => k.trim())
            .filter((k) => k);
    }

    axios
        .post('/api/v1/monitors', newMonitor)
        .then((response) => {
            monitors.value.push(response.data);
            resetMonitorForm();
            showCreateForm.value = false;
        })
        .catch((error) => {
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
        status: monitorStatus.value,
    };

    // Add type-specific fields
    if (monitorType.value === 'ping') {
        updatedMonitor.hostname = monitorHostname.value;
        updatedMonitor.port = monitorPort.value;
    } else if (monitorType.value === 'website') {
        updatedMonitor.url = monitorUrl.value;
        updatedMonitor.check_status = monitorCheckStatus.value;
        updatedMonitor.keywords = monitorKeywords.value
            .split(',')
            .map((k) => k.trim())
            .filter((k) => k);
    }

    console.log('Updating monitor [ID]: ', editingMonitor.value.id, updatedMonitor);

    axios
        .put(`/api/v1/monitors/${editingMonitor.value.id}`, updatedMonitor)
        .then((response) => {
            const index = monitors.value.findIndex((m) => m.id === editingMonitor.value.id);
            if (index !== -1) {
                monitors.value[index] = response.data;
            }
            resetMonitorForm();
            showEditForm.value = false;
            editingMonitor.value = null;
        })
        .catch((error) => {
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
    monitors.value = monitors.value.filter((m) => m.id !== id);
    axios
        .delete(`/api/v1/monitors/${id}`)
        .then(() => {
            console.log('Monitor successfully deleted.');
        })
        .catch((err) => {
            console.error('Failed to delete monitor', err);
        });
}

// Computed filtered monitors
const filteredMonitors = computed(() => {
    return monitors.value.filter((m) => {
        const labelMatch = !filterLabel.value || m.label.includes(filterLabel.value);
        const typeMatch = typeDirection.value === 'all' || m.monitor_type === typeDirection.value;
        const statusMatch = statusDirection.value === 'all' || m.status === statusDirection.value;
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
        <div class="mx-auto mt-8 w-4xl max-w-4xl">
            <div class="flex flex-col gap-4">
                <h1 class="mb-6 text-center text-3xl font-extrabold tracking-tight drop-shadow-md">Project Details</h1>
                <!-- Animated success message -->
                <div v-if="saveSuccess" class="fixed top-5 right-5 rounded bg-green-500 p-3 text-white">Saved successfully!</div>

                <!-- Project Properties Section -->
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-lg font-bold">Project Information</h2>
                        <button
                            @click="showProjectForm = !showProjectForm"
                            class="rounded bg-emerald-600 px-3 py-1 text-sm text-white transition hover:bg-emerald-700"
                        >
                            {{ showProjectForm ? 'Cancel' : 'Edit' }}
                        </button>
                    </div>

                    <!-- Project Info Display (when not editing) -->
                    <div
                        v-if="!showProjectForm"
                        class="rounded-lg border p-3"
                        :style="{ background: '#171717', color: '#fff', borderColor: 'white' }"
                    >
                        <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
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
                                    <span
                                        v-for="tag in form.tags"
                                        :key="tag"
                                        class="inline-block rounded bg-[#262626] px-1 py-0.5 text-xs text-white"
                                        >{{ tag }}</span
                                    >
                                    <span v-if="form.tags.length === 0" class="text-xs text-gray-400">No tags set</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsible Project Form -->
                    <div v-if="showProjectForm" class="rounded-lg border bg-emerald-50 p-3 dark:bg-emerald-900/20">
                        <h3 class="mb-3 text-base font-semibold">Edit Project Information</h3>

                        <form @submit.prevent="save">
                            <div class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold">Label</label>
                                    <input
                                        type="text"
                                        v-model="form.label"
                                        class="w-full rounded border bg-gray-50 px-2 py-1 text-sm dark:bg-zinc-900"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold">Description</label>
                                    <textarea
                                        v-model="form.description"
                                        class="w-full rounded border bg-gray-50 px-2 py-1 text-sm dark:bg-zinc-900"
                                        rows="2"
                                    ></textarea>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold">Tags</label>
                                    <input
                                        type="text"
                                        v-model="tagsInput"
                                        placeholder="Comma separated"
                                        class="w-full rounded border bg-gray-50 px-2 py-1 text-sm dark:bg-zinc-900"
                                    />
                                </div>
                            </div>

                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    @click="showProjectForm = false"
                                    class="rounded border px-3 py-1 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    Cancel
                                </button>
                                <button type="submit" class="rounded bg-emerald-600 px-3 py-1 text-sm text-white transition hover:bg-emerald-700">
                                    Save Project
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Monitor Management -->
                <div class="mb-8">
                    <!-- Collapsible Create Monitor Form -->
                    <div v-if="showCreateForm" class="mb-6 rounded-lg border bg-gray-50 p-4 dark:bg-gray-800">
                        <h3 class="mb-4 text-lg font-semibold">Create New Monitor</h3>

                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium">Label</label>
                                <input
                                    type="text"
                                    v-model="monitorLabel"
                                    placeholder="Monitor label"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Type</label>
                                <select v-model="monitorType" class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900">
                                    <option value="ping">Ping Monitor</option>
                                    <option value="website">Website Monitor</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Periodicity (seconds)</label>
                                <input
                                    type="number"
                                    v-model.number="monitorPeriodicity"
                                    placeholder="60"
                                    min="5"
                                    max="300"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                    :class="
                                        monitorPeriodicity && (monitorPeriodicity < 5 || monitorPeriodicity > 300)
                                            ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                                            : ''
                                    "
                                />
                                <small v-if="monitorPeriodicity && (monitorPeriodicity < 5 || monitorPeriodicity > 300)" class="text-xs text-red-500">
                                    Periodicity must be between 5 and 300 seconds
                                </small>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Badge Label</label>
                                <input
                                    type="text"
                                    v-model="monitorBadgeLabel"
                                    placeholder="Badge label"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Status</label>
                                <select v-model="monitorStatus" class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900">
                                    <option value="succeeded">Succeeded</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>

                        <!-- Ping Monitor Specific Fields -->
                        <div
                            v-if="monitorType === 'ping'"
                            class="mb-4 grid grid-cols-1 gap-4 rounded-lg bg-blue-50 p-4 md:grid-cols-2 dark:bg-blue-900/20"
                        >
                            <div>
                                <label class="mb-1 block text-sm font-medium">Hostname or IP Address</label>
                                <input
                                    type="text"
                                    v-model="monitorHostname"
                                    placeholder="example.com or 192.168.1.1"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Port</label>
                                <input
                                    type="number"
                                    v-model.number="monitorPort"
                                    placeholder="80"
                                    min="1"
                                    max="65535"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                />
                            </div>
                        </div>

                        <!-- Website Monitor Specific Fields -->
                        <div v-if="monitorType === 'website'" class="mb-4 grid grid-cols-1 gap-4 rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                            <div>
                                <label class="mb-1 block text-sm font-medium">URL</label>
                                <input
                                    type="url"
                                    v-model="monitorUrl"
                                    placeholder="https://example.com/page"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                />
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" v-model="monitorCheckStatus" id="checkStatus" class="rounded border-gray-300" />
                                <label for="checkStatus" class="text-sm font-medium"> Check Status (Fail if status not in 200-299 range) </label>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Keywords (comma-separated)</label>
                                <textarea
                                    v-model="monitorKeywords"
                                    placeholder="keyword1, keyword2, keyword3"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                    rows="2"
                                ></textarea>
                                <small class="text-gray-500">Monitor fails if any keyword is not found in the response</small>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button
                                @click="
                                    showCreateForm = false;
                                    resetMonitorForm();
                                "
                                class="rounded border px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                @click="createMonitor"
                                class="rounded bg-blue-600 px-6 py-2 text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                                :disabled="!monitorLabel || !monitorType || !monitorPeriodicity || monitorPeriodicity < 5 || monitorPeriodicity > 300"
                            >
                                Create Monitor
                            </button>
                        </div>
                    </div>

                    <!-- Collapsible Edit Monitor Form -->
                    <div v-if="showEditForm" class="mb-6 rounded-lg border bg-yellow-50 p-4 dark:bg-yellow-900/20">
                        <h3 class="mb-4 text-lg font-semibold">Edit Monitor</h3>

                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-sm font-medium">Label</label>
                                <input
                                    type="text"
                                    v-model="monitorLabel"
                                    placeholder="Monitor label"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Type</label>
                                <select v-model="monitorType" class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900">
                                    <option value="ping">Ping Monitor</option>
                                    <option value="website">Website Monitor</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Periodicity (seconds)</label>
                                <input
                                    type="number"
                                    v-model.number="monitorPeriodicity"
                                    placeholder="60"
                                    min="5"
                                    max="300"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                    :class="
                                        monitorPeriodicity && (monitorPeriodicity < 5 || monitorPeriodicity > 300)
                                            ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                                            : ''
                                    "
                                />
                                <small v-if="monitorPeriodicity && (monitorPeriodicity < 5 || monitorPeriodicity > 300)" class="text-xs text-red-500">
                                    Periodicity must be between 5 and 300 seconds
                                </small>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Badge Label</label>
                                <input
                                    type="text"
                                    v-model="monitorBadgeLabel"
                                    placeholder="Badge label"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Status</label>
                                <select v-model="monitorStatus" class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900">
                                    <option value="succeeded">Succeeded</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>

                        <!-- Ping Monitor Specific Fields -->
                        <div
                            v-if="monitorType === 'ping'"
                            class="mb-4 grid grid-cols-1 gap-4 rounded-lg bg-blue-50 p-4 md:grid-cols-2 dark:bg-blue-900/20"
                        >
                            <div>
                                <label class="mb-1 block text-sm font-medium">Hostname or IP Address</label>
                                <input
                                    type="text"
                                    v-model="monitorHostname"
                                    placeholder="example.com or 192.168.1.1"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Port</label>
                                <input
                                    type="number"
                                    v-model.number="monitorPort"
                                    placeholder="80"
                                    min="1"
                                    max="65535"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                />
                            </div>
                        </div>

                        <!-- Website Monitor Specific Fields -->
                        <div v-if="monitorType === 'website'" class="mb-4 grid grid-cols-1 gap-4 rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                            <div>
                                <label class="mb-1 block text-sm font-medium">URL</label>
                                <input
                                    type="url"
                                    v-model="monitorUrl"
                                    placeholder="https://example.com/page"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                />
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" v-model="monitorCheckStatus" id="checkStatus" class="rounded border-gray-300" />
                                <label for="checkStatus" class="text-sm font-medium"> Check Status (Fail if status not in 200-299 range) </label>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">Keywords (comma-separated)</label>
                                <textarea
                                    v-model="monitorKeywords"
                                    placeholder="keyword1, keyword2, keyword3"
                                    class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                    rows="2"
                                ></textarea>
                                <small class="text-gray-500">Monitor fails if any keyword is not found in the response</small>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button @click="cancelEdit" class="rounded border px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">Cancel</button>
                            <button
                                @click="updateMonitor"
                                class="rounded bg-yellow-600 px-6 py-2 text-white transition hover:bg-yellow-700 disabled:cursor-not-allowed disabled:bg-gray-400"
                                :disabled="!monitorLabel || !monitorType || !monitorPeriodicity || monitorPeriodicity < 5 || monitorPeriodicity > 300"
                            >
                                Update Monitor
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Monitor Filters with Title and Create Button -->
                <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <h2 class="text-xl font-bold">Monitors</h2>
                        <input
                            type="text"
                            v-model="filterLabel"
                            placeholder="Filter by label"
                            class="rounded border bg-gray-50 px-2 py-1 dark:bg-zinc-900"
                        />
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Pagination Controls -->
                        <div v-if="totalPages > 1" class="flex items-center gap-2">
                            <button
                                @click="currentPage = Math.max(1, currentPage - 1)"
                                :disabled="currentPage === 1"
                                class="rounded border bg-gray-100 px-3 py-1 text-gray-700 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200"
                            >
                                Prev
                            </button>
                            <span class="mx-2 text-sm"> Page {{ currentPage }} of {{ totalPages }} </span>
                            <button
                                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                                :disabled="currentPage === totalPages"
                                class="rounded border bg-gray-100 px-3 py-1 text-gray-700 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200"
                            >
                                Next
                            </button>
                        </div>
                        <button
                            @click="showCreateForm = !showCreateForm"
                            class="rounded bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700"
                        >
                            {{ showCreateForm ? 'Cancel' : 'Create Monitor' }}
                        </button>
                    </div>
                </div>

                <!-- Monitors Table -->
                <div
                    class="overflow-x-auto rounded-lg border-2 border-white bg-[#171717] shadow dark:bg-[#171717]"
                    :style="`min-height: ${pageSize * 56}px`"
                >
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Label</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">
                                    <button
                                        @click="cycleTypeFilter"
                                        class="cursor-pointer text-xs font-semibold text-gray-700 hover:text-blue-500"
                                        :class="{
                                            'text-blue-500': typeDirection !== 'all',
                                        }"
                                        :title="
                                            typeDirection === 'all'
                                                ? 'Show All Types'
                                                : typeDirection === 'ping'
                                                  ? 'Show Ping Only'
                                                  : 'Show Website Only'
                                        "
                                    >
                                        Type {{ typeDirection === 'all' ? '•' : typeDirection === 'ping' ? 'P' : 'W' }}
                                    </button>
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">
                                    <button
                                        @click="cycleStatusFilter"
                                        class="cursor-pointer text-xs font-semibold text-gray-700 hover:text-blue-500"
                                        :class="{
                                            'text-blue-500': statusDirection !== 'all',
                                        }"
                                        :title="
                                            statusDirection === 'all'
                                                ? 'Show All Statuses'
                                                : statusDirection === 'succeeded'
                                                  ? 'Show Succeeded Only'
                                                  : 'Show Failed Only'
                                        "
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
                                    {{
                                        monitor.monitor_type
                                            ? monitor.monitor_type.charAt(0).toUpperCase() + monitor.monitor_type.slice(1)
                                            : 'Unknown'
                                    }}
                                </td>
                                <td class="px-4 py-2">
                                    <span
                                        v-if="monitor.status === 'succeeded'"
                                        class="inline-block rounded bg-emerald-600 px-2 py-1 text-xs text-white"
                                        >Succeeded</span
                                    >
                                    <span v-else class="inline-block rounded bg-red-600 px-2 py-1 text-xs text-white">Failed</span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex flex-col gap-1 sm:flex-row sm:gap-2">
                                        <!--
                                        <button
                                            @click="openEditForm(monitor)"
                                            class="rounded bg-yellow-500 px-2 py-1 text-xs text-white transition hover:bg-yellow-600"
                                        >
                                            Edit
                                        </button>
                                        -->
                                        <button
                                            @click="deleteMonitor(monitor.id)"
                                            class="rounded bg-red-500 px-2 py-1 text-xs text-white transition hover:bg-red-600"
                                        >
                                            Delete
                                        </button>
                                        <button
                                            @click="goToMonitorDetails(monitor.id)"
                                            class="rounded bg-blue-500 px-2 py-1 text-xs text-white transition hover:bg-blue-600"
                                        >
                                            Details
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Add empty rows to maintain fixed height -->
                            <tr v-for="n in Math.max(0, pageSize - paginatedMonitors.length)" :key="'empty-' + n" class="h-14">
                                <td class="px-4 py-2" colspan="4">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
