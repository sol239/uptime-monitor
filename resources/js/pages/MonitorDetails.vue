<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();
const monitor = ref(page.props.monitor);
const project = ref(page.props.project);

const saveSuccess = ref(false);
const showMonitorForm = ref(false);

const form = ref({
    label: monitor.value.label,
    periodicity: monitor.value.periodicity,
    type: monitor.value.monitor_type,
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

// Unified logs data - replaces separate data sources
const logsData = ref([]);
const logsLoading = ref(false);
const fetchInterval = 5; // seconds

// Save monitor handler
function saveMonitor() {
    const payload: any = {
        label: form.value.label,
        periodicity: form.value.periodicity,
        type: form.value.monitor_type,
        badge_label: form.value.badge_label,
        status: form.value.status,
    };
    if (form.value.monitor_type === 'ping') {
        payload.hostname = form.value.hostname;
        payload.port = form.value.port;
    } else if (form.value.monitor_type === 'website') {
        payload.url = form.value.url;
        payload.check_status = form.value.check_status;
        payload.keywords = form.value.keywords
            .split(',')
            .map((k) => k.trim())
            .filter(Boolean);
    }
    axios
        .put(`/api/v1/monitors/${monitor.value.id}`, payload)
        .then((res) => {
            monitor.value = res.data;
            saveSuccess.value = true;
            showMonitorForm.value = false;
        })
        .catch(() => {});
}

watch(saveSuccess, (v) => {
    if (v) setTimeout(() => (saveSuccess.value = false), 1000);
});

// Updated unified fetch function
async function fetchSingleLog() {
    logsLoading.value = true;
    try {
        console.log('Fetching data for monitor: ', monitor.value.id);
        const { data } = await axios.get(`/api/v1/monitors/${monitor.value.id}/logs`);
        console.log('Fetched logs:', data);
        logsData.value = data.logs || [];
        return data;
    } catch (e) {
        console.error('Error fetching logs:', e);
        logsData.value = [];
        return null;
    } finally {
        logsLoading.value = false;
    }
}

// Remove old history fetch function and replace with unified approach
onMounted(() => {
    fetchSingleLog();
    intervalId = setInterval(fetchSingleLog, fetchInterval * 1000);
});
onUnmounted(() => {
    clearInterval(intervalId);
});

// History view mode
const historyViewMode = ref('list'); // 'list', 'calendar', 'graph'

// List mode filters & pagination
const listFilters = ref({
    status: '',
    startDate: '',
    endDate: '',
    page: 1,
    perPage: 10,
});

const filteredListHistory = computed(() => {
    let filtered = [...logsData.value];

    // Apply status filter
    if (listFilters.value.status) {
        filtered = filtered.filter((log) => log.status === listFilters.value.status);
    }

    // Apply date filters
    if (listFilters.value.startDate) {
        filtered = filtered.filter((log) => log.started_at >= listFilters.value.startDate);
    }
    if (listFilters.value.endDate) {
        filtered = filtered.filter((log) => log.started_at <= listFilters.value.endDate);
    }

    return filtered;
});

const paginatedListHistory = computed(() => {
    const start = (listFilters.value.page - 1) * listFilters.value.perPage;
    const end = start + listFilters.value.perPage;
    return filteredListHistory.value.slice(start, end);
});

const listTotal = computed(() => filteredListHistory.value.length);

// Updated calendar mode - uses logsData
const calendarData = computed(() => {
    const data = {};
    logsData.value.forEach((log) => {
        const date = log.started_at.split('T')[0]; // Extract YYYY-MM-DD
        if (!data[date]) {
            data[date] = { total: 0, failed: 0, succeeded: 0 };
        }
        data[date].total++;
        if (log.status === 'failed') {
            data[date].failed++;
        } else if (log.status === 'succeeded') {
            data[date].succeeded++;
        }
    });
    return data;
});

// GitHub-style calendar with 3 months of data
const calendarDays = computed(() => {
    const days = [];
    const today = new Date();
    today.setDate(today.getDate() + 1);
    const threeMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 2, 1);

    // Start from the beginning of 3 months ago
    const currentDate = new Date(threeMonthsAgo);

    // Generate all days for 3 months
    while (currentDate <= today) {
        const dateStr = currentDate.toISOString().split('T')[0];
        const summary = calendarData.value[dateStr];

        //console.log("Summary for", dateStr, ":", summary);

        let color = '#2d3748'; // Default gray for no data
        let status = 'No data';

        if (summary && summary.total > 0) {
            const failureRate = summary.failed / summary.total;
            if (failureRate === 0) {
                color = '#22c55e'; // Green - no failures
                status = `Perfect (${summary.total} checks)`;
            } else if (failureRate <= 0.05) {
                color = '#f59e0b'; // Orange - <= 5% failures
                status = `Good (${summary.failed} failed / ${summary.total} total)`;
            } else {
                color = '#ef4444'; // Red - > 5% failures
                status = `Issues (${summary.failed} failed / ${summary.total} total)`;
            }
        }

        const d = new Date(currentDate);
        console.log('Date:', d);
        console.log('Date str:', dateStr);
        console.log('Summary', summary);

        days.push({
            date: d,
            dateStr,
            color,
            status,
            summary,
        });

        currentDate.setDate(currentDate.getDate() + 1);
    }
    console.log(days);
    return days;
});

// Group calendar days into weeks for table display
const calendarWeeks = computed(() => {
    const weeks = [];
    const days = [...calendarDays.value];

    // Add empty cells at the beginning to align with Monday start
    const firstDay = days[0]?.date;
    if (firstDay) {
        const dayOfWeek = (firstDay.getDay() + 6) % 7; // Convert Sunday=0 to Monday=0
        for (let i = 0; i < dayOfWeek; i++) {
            days.unshift(null);
        }
    }

    // Group into weeks (7 days each)
    for (let i = 0; i < days.length; i += 7) {
        weeks.push(days.slice(i, i + 7));
    }

    return weeks;
});

const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

// Date formatting helper
const formatDateTime = (dateString: string) => {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${year}/${month}/${day} ${hours}:${minutes}:${seconds}`;
};

// Graph mode data
const graphData = computed(() => {
    let filtered = [...logsData.value];
    // Apply date filters for graph
    if (listFilters.value.startDate) {
        filtered = filtered.filter((log) => log.started_at >= listFilters.value.startDate);
    }
    if (listFilters.value.endDate) {
        filtered = filtered.filter((log) => log.started_at <= listFilters.value.endDate);
    }
    return filtered.sort((a, b) => new Date(a.started_at) - new Date(b.started_at));
});

// Ensure graph updates when switching to graph mode
watch(historyViewMode, (mode) => {
    if (mode === 'graph') {
        // Force recompute by updating a dummy ref if needed, or just trigger fetchSingleLog
        fetchSingleLog();
    }
});

const calendarSvgRects = computed(() => {
    // Each rect is 3.25px wide, 15px high, 5.9px apart (from your example)
    const rects = [];
    const days = calendarDays.value;
    days.forEach((day, idx) => {
        let fill = '#2d3748';
        let fillOpacity = 1;
        let percent = '';
        if (day.summary && day.summary.total > 0) {
            const rate = (day.summary.total - day.summary.failed) / day.summary.total;
            percent = (rate * 100).toFixed(3) + '%';
            if (day.summary.failed === 0) {
                fill = '#3bd671'; // green{ title: `Project ${project.id}`, href: `/projects/${project.id}`
                fillOpacity = 1;
            } else if (rate >= 0.95) {
                fill = '#f29030'; // orange
                fillOpacity = 1;
            } else {
                fill = '#ef4444'; // red
                fillOpacity = 1;
            }
            // If not perfect, but still green, lower opacity
            if (fill === '#3bd671' && rate < 1) fillOpacity = 0.5;
        }
        rects.push({
            x: idx * 5.9,
            fill,
            fillOpacity,
            date: day.dateStr,
            percent,
        });
    });
    return rects;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: `Project ${project.value.id}`, href: `/projects/${project.value.id}` },
    { title: 'Monitor Details', href: '#' },
];
</script>

<template>
    <Head :title="`Monitor Details - ${form.label}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-screen items-center justify-center">
            <main class="flex flex-grow items-center justify-center">
                <div class="w-full max-w-4xl rounded-xl bg-white p-10 shadow-lg dark:bg-[#121212]" style="border: 1px solid white">
                    <h1 class="mb-6 text-center text-3xl font-extrabold tracking-tight drop-shadow-md">Monitor Details</h1>
                    <div v-if="saveSuccess" class="fixed top-5 right-5 rounded bg-green-500 p-3 text-white">Saved successfully!</div>

                    <!-- Info + History side by side -->
                    <div class="mb-8 flex flex-col gap-8 md:flex-row">
                        <!-- Monitor Info Section -->
                        <div class="flex-1">
                            <div class="mb-4 flex items-center justify-between">
                                <h2 class="text-xl font-bold">Monitor Information</h2>
                                <button
                                    @click="showMonitorForm = !showMonitorForm"
                                    class="rounded bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700"
                                >
                                    {{ showMonitorForm ? 'Cancel' : 'Edit Monitor' }}
                                </button>
                            </div>

                            <!-- Info Display -->
                            <div
                                v-if="!showMonitorForm"
                                class="rounded-lg border p-4"
                                :style="{ background: '#171717', color: '#fff', borderColor: 'white' }"
                            >
                                <div class="mb-3">
                                    <span class="text-sm font-semibold text-gray-300">Label:</span>
                                    <p class="text-lg">{{ monitor.label || 'No label set' }}</p>
                                </div>
                                <div class="mb-3">
                                    <span class="text-sm font-semibold text-gray-300">Type:</span>
                                    <p class="text-lg">{{ monitor.monitor_type }}</p>
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
                                <div v-if="monitor.monitor_type === 'ping'" class="mb-3">
                                    <span class="text-sm font-semibold text-gray-300">Hostname:</span>
                                    <p class="text-lg">{{ monitor.hostname }}</p>
                                    <span class="text-sm font-semibold text-gray-300">Port:</span>
                                    <p class="text-lg">{{ monitor.port }}</p>
                                </div>
                                <div v-if="monitor.monitor_type === 'website'" class="mb-3">
                                    <span class="text-sm font-semibold text-gray-300">URL:</span>
                                    <p class="text-lg">{{ monitor.url }}</p>
                                    <span class="text-sm font-semibold text-gray-300">Check Status:</span>
                                    <p class="text-lg">{{ monitor.check_status ? 'Yes' : 'No' }}</p>
                                    <span class="text-sm font-semibold text-gray-300">Keywords:</span>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span
                                            v-for="kw in monitor.keywords"
                                            :key="kw"
                                            class="inline-block rounded bg-[#262626] px-2 py-1 text-xs text-white"
                                            >{{ kw }}</span
                                        >
                                        <span v-if="!monitor.keywords || monitor.keywords.length === 0" class="text-sm text-gray-400"
                                            >No keywords set</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Collapsible Monitor Form -->
                            <div v-if="showMonitorForm" class="rounded-lg border bg-blue-50 p-4 dark:bg-blue-900/20">
                                <h3 class="mb-4 text-lg font-semibold">Edit Monitor Information</h3>
                                <form @submit.prevent="saveMonitor">
                                    <div class="mb-4">
                                        <label class="mb-1 block text-sm font-semibold">Label</label>
                                        <input
                                            type="text"
                                            v-model="form.label"
                                            class="monitor-label-input w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                        />
                                    </div>
                                    <div class="mb-4">
                                        <label class="mb-1 block text-sm font-semibold">Type</label>
                                        <select v-model="form.monitor_type" class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900">
                                            <option value="ping">Ping Monitor</option>
                                            <option value="website">Website Monitor</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="mb-1 block text-sm font-semibold">Periodicity (seconds)</label>
                                        <input
                                            type="number"
                                            v-model.number="form.periodicity"
                                            placeholder="60"
                                            min="5"
                                            max="300"
                                            class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                            :class="
                                                form.periodicity && (form.periodicity < 5 || form.periodicity > 300)
                                                    ? 'border-red-500 bg-red-50 dark:bg-red-900/20'
                                                    : ''
                                            "
                                        />
                                        <small
                                            v-if="form.periodicity && (form.periodicity < 5 || form.periodicity > 300)"
                                            class="text-xs text-red-500"
                                        >
                                            Periodicity must be between 5 and 300 seconds
                                        </small>
                                    </div>
                                    <div class="mb-4">
                                        <label class="mb-1 block text-sm font-semibold">Badge Label</label>
                                        <input
                                            type="text"
                                            v-model="form.badge_label"
                                            placeholder="Badge label"
                                            class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                        />
                                    </div>
                                    <div class="mb-4">
                                        <label class="mb-1 block text-sm font-semibold">Status</label>
                                        <select v-model="form.status" class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900">
                                            <option value="succeeded">Succeeded</option>
                                            <option value="failed">Failed</option>
                                        </select>
                                    </div>
                                    <!-- Ping Monitor Specific Fields -->
                                    <div
                                        v-if="form.monitor_type === 'ping'"
                                        class="mb-4 grid grid-cols-1 gap-4 rounded-lg bg-blue-50 p-4 md:grid-cols-2 dark:bg-blue-900/20"
                                    >
                                        <div>
                                            <label class="mb-1 block text-sm font-medium">Hostname or IP Address</label>
                                            <input
                                                type="text"
                                                v-model="form.hostname"
                                                placeholder="example.com or 192.168.1.1"
                                                class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                            />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium">Port</label>
                                            <input
                                                type="number"
                                                v-model.number="form.port"
                                                placeholder="80"
                                                min="1"
                                                max="65535"
                                                class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                            />
                                        </div>
                                    </div>
                                    <!-- Website Monitor Specific Fields -->
                                    <div
                                        v-if="form.monitor_type === 'website'"
                                        class="mb-4 grid grid-cols-1 gap-4 rounded-lg bg-green-50 p-4 dark:bg-green-900/20"
                                    >
                                        <div>
                                            <label class="mb-1 block text-sm font-medium">URL</label>
                                            <input
                                                type="url"
                                                v-model="form.url"
                                                placeholder="https://example.com/page"
                                                class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                            />
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" v-model="form.check_status" id="checkStatus" class="rounded border-gray-300" />
                                            <label for="checkStatus" class="text-sm font-medium">
                                                Check Status (Fail if status not in 200-299 range)
                                            </label>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-sm font-medium">Keywords (comma-separated)</label>
                                            <textarea
                                                v-model="form.keywords"
                                                placeholder="keyword1, keyword2, keyword3"
                                                class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                                rows="2"
                                            ></textarea>
                                            <small class="text-gray-500">Monitor fails if any keyword is not found in the response</small>
                                        </div>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="showMonitorForm = false"
                                            class="rounded border px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        >
                                            Cancel
                                        </button>
                                        <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">
                                            Save Monitor
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Monitor History Section -->
                        <div class="flex-1">
                            <div class="mb-4 flex items-center justify-between">
                                <h2 class="text-xl font-bold">Monitor History</h2>
                                <div class="flex items-center gap-2">
                                    <label>View:</label>
                                    <select v-model="historyViewMode" class="rounded border bg-gray-50 px-2 py-1 dark:bg-zinc-900">
                                        <option value="list">List</option>
                                        <option value="calendar">Calendar</option>
                                        <option value="graph">Graph</option>
                                    </select>
                                    <span v-if="logsLoading" class="text-sm text-gray-500">Loading...</span>
                                </div>
                            </div>

                            <!-- List Mode -->
                            <div v-if="historyViewMode === 'list'" class="mb-4">
                                <div class="mb-2 flex items-end gap-4">
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold">Status</label>
                                        <select v-model="listFilters.status" class="rounded border bg-gray-50 px-2 py-1 dark:bg-zinc-900">
                                            <option value="">All</option>
                                            <option value="succeeded">Succeeded</option>
                                            <option value="failed">Failed</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold">Start Date</label>
                                        <input
                                            type="date"
                                            v-model="listFilters.startDate"
                                            class="rounded border bg-gray-50 px-2 py-1 dark:bg-zinc-900"
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold">End Date</label>
                                        <input
                                            type="date"
                                            v-model="listFilters.endDate"
                                            class="rounded border bg-gray-50 px-2 py-1 dark:bg-zinc-900"
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold">Per Page</label>
                                        <input
                                            type="number"
                                            v-model.number="listFilters.perPage"
                                            min="1"
                                            max="100"
                                            class="rounded border bg-gray-50 px-2 py-1 dark:bg-zinc-900"
                                        />
                                    </div>
                                </div>
                                <div class="mb-6 overflow-x-auto rounded-lg bg-[#171717] shadow dark:bg-[#171717]" style="border: 2px solid white">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead>
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Started At</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Status</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Response Time (ms)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in paginatedListHistory" :key="item.id">
                                                <td class="px-4 py-2">{{ formatDateTime(item.started_at) }}</td>
                                                <td class="px-4 py-2">
                                                    <span
                                                        v-if="item.status === 'succeeded'"
                                                        class="inline-block rounded bg-emerald-600 px-2 py-1 text-xs text-white"
                                                        >Succeeded</span
                                                    >
                                                    <span v-else class="inline-block rounded bg-red-600 px-2 py-1 text-xs text-white">Failed</span>
                                                </td>
                                                <td class="px-4 py-2">{{ item.response_time_ms }}</td>
                                            </tr>
                                            <tr v-if="paginatedListHistory.length === 0">
                                                <td colspan="3" class="px-4 py-6 text-center text-gray-500">No history found.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="mt-2 flex items-center justify-end gap-2">
                                        <button :disabled="listFilters.page <= 1" @click="listFilters.page--" class="rounded border px-2 py-1">
                                            Prev
                                        </button>
                                        <span>Page {{ listFilters.page }} ({{ listTotal }} total)</span>
                                        <button
                                            :disabled="listFilters.page * listFilters.perPage >= listTotal"
                                            @click="listFilters.page++"
                                            class="rounded border px-2 py-1"
                                        >
                                            Next
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Calendar Mode -->
                            <div v-if="historyViewMode === 'calendar'" class="mb-4">
                                <div class="rounded-lg bg-[#171717] p-4" style="border: 2px solid white">
                                    <h3 class="mb-4 text-center font-bold text-white">Monitor Activity (Last 3 Months)</h3>
                                    <!-- SVG Horizontal Calendar Bar -->
                                    <div class="mb-6 flex justify-center">
                                        <svg :width="Math.max(calendarSvgRects.length * 5.9, 530)" height="15" xmlns="http://www.w3.org/2000/svg">
                                            <rect
                                                v-for="(rect, idx) in calendarSvgRects"
                                                :key="rect.date"
                                                height="15"
                                                width="3.25"
                                                :x="rect.x"
                                                y="0"
                                                :fill="rect.fill"
                                                :fill-opacity="rect.fillOpacity"
                                                rx="1.625"
                                                ry="1.625"
                                                :title="`${rect.date} ${rect.percent}`"
                                            />
                                        </svg>
                                    </div>

                                    <!-- Calendar Table -->
                                    <div class="overflow-x-auto">
                                        <table class="mx-auto">
                                            <!-- Week day headers -->
                                            <thead>
                                                <tr>
                                                    <th class="w-8"></th>
                                                    <th
                                                        v-for="day in weekDays"
                                                        :key="day"
                                                        class="h-3 w-3 text-center text-xs font-normal text-gray-400"
                                                    >
                                                        {{ day }}
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(week, weekIndex) in calendarWeeks" :key="weekIndex" class="group">
                                                    <!-- Month label for first week of each month -->
                                                    <td class="pr-2 text-right align-top text-xs text-gray-400">
                                                        <span v-if="week.find((day) => day && day.date.getDate() <= 7)" class="block">
                                                            {{
                                                                week
                                                                    .find((day) => day && day.date.getDate() <= 7)
                                                                    ?.date.toLocaleDateString('en-US', {
                                                                        month: 'short',
                                                                    })
                                                            }}
                                                        </span>
                                                    </td>

                                                    <!-- Day cells -->
                                                    <td v-for="(day, dayIndex) in week" :key="dayIndex" class="p-0">
                                                        <div
                                                            v-if="day"
                                                            class="m-0.5 h-3 w-3 cursor-pointer rounded-sm transition-all hover:ring-1 hover:ring-white"
                                                            :style="{ backgroundColor: day.color }"
                                                            :title="`${day.date.toLocaleDateString()} - ${day.status}`"
                                                        ></div>
                                                        <div v-else class="m-0.5 h-3 w-3"></div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Summary stats -->
                                    <div class="mt-4 text-center text-sm text-gray-400">
                                        <div class="flex justify-center gap-6">
                                            <span>Total Days: {{ calendarDays.filter((d) => d.summary?.total > 0).length }}</span>
                                            <span
                                                >Perfect Days:
                                                {{ calendarDays.filter((d) => d.summary?.total > 0 && d.summary.failed === 0).length }}</span
                                            >
                                            <span
                                                >Issues:
                                                {{
                                                    calendarDays.filter((d) => d.summary?.total > 0 && d.summary.failed / d.summary.total > 0.05)
                                                        .length
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Graph Mode -->
                            <div v-if="historyViewMode === 'graph'" class="mb-4">
                                <svg :width="600" :height="300" style="background: #171717; border: 2px solid white">
                                    <g v-if="graphData.length">
                                        <template v-for="(item, idx) in graphData" :key="item.id">
                                            <circle
                                                :cx="40 + idx * (560 / Math.max(graphData.length - 1, 1))"
                                                :cy="260 - (item.response_time_ms / Math.max(...graphData.map((d) => d.response_time_ms), 1)) * 220"
                                                r="3"
                                                fill="#3b82f6"
                                                :title="`${item.started_at}: ${item.response_time_ms}ms`"
                                            />
                                        </template>
                                    </g>
                                    <!-- Axes -->
                                    <line x1="40" y1="260" x2="580" y2="260" stroke="#fff" />
                                    <line x1="40" y1="40" x2="40" y2="260" stroke="#fff" />
                                    <text x="10" y="40" fill="#fff" font-size="12">Response Time (ms)</text>
                                    <text x="500" y="290" fill="#fff" font-size="12">Time</text>
                                </svg>
                                <div v-if="graphData.length === 0" class="p-4 text-gray-500">No data for graph.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Mock fetchSingleLog button and result display -->
                    <div class="mb-8">
                        <h2 class="mb-2 text-lg font-bold">Logs Data (Auto-refreshing every {{ fetchInterval }}s)</h2>
                        <div class="mb-2 flex items-center gap-2">
                            <button @click="fetchSingleLog" class="rounded bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">
                                Refresh Now
                            </button>
                            <span v-if="logsLoading" class="ml-2 text-gray-500">Loading...</span>
                            <span class="ml-2 text-gray-500">{{ logsData.length }} logs loaded</span>
                        </div>
                        <div v-if="logsData.length > 0" class="mt-2 max-h-48 overflow-y-auto rounded border bg-gray-100 p-4 dark:bg-zinc-900">
                            <pre class="text-xs"
                                >{{ JSON.stringify(logsData.slice(0, 3), null, 2) }}...{{
                                    logsData.length > 3
                                        ? ` (showing 3 of
      ${logsData.length})`
                                        : ''
                                }}</pre
                            >
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </AppLayout>
</template>
