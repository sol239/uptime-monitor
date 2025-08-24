<script setup lang="ts">
/* 1. Imports */
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';

/* 2. Stores */
// No Pinia stores used

/* 3. Context hooks */
const page = usePage();

/* 4. Constants (non-reactive) */
/** Interval for fetching logs (seconds) */
const fetchInterval = 5;
/** Breadcrumbs for navigation */
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: `${page.props.project.label}`, href: `/projects/${page.props.project.id}` },
    { title: 'Monitor Details', href: '#' },
];

/* 5. Props */
// No props defined

/* 6. Emits */
// No emits defined

/* 7. Template refs */
// No template refs used

/* 8. State (ref, reactive) */
/** Monitor data */
const monitor = ref(page.props.monitor);
/** Success flag for save */
const saveSuccess = ref(false);
/** Show monitor form flag */
const showMonitorForm = ref(false);
/** Monitor form state */
const form = reactive({
    label: monitor.value.label,
    periodicity: monitor.value.periodicity,
    monitor_type: monitor.value.monitor_type,
    badge_label: monitor.value.badge_label || '',
    status: monitor.value.status,
    hostname: monitor.value.hostname || '',
    port: monitor.value.port || null,
    url: monitor.value.url || '',
    check_status: monitor.value.check_status || false,
    keywords: Array.isArray(monitor.value.keywords) ? monitor.value.keywords.join(', ') : '',
});
/** Viewport height for dynamic sizing */
const viewportHeight = ref(window.innerHeight);
/** Logs data */
const logsData = ref([]);
const logsLoading = ref(false);
/** Interval ID for polling */
let intervalId: any = null;
/** History view mode */
const historyViewMode = ref('list'); // 'list', 'calendar', 'graph'
/** List mode filters & pagination */
const listFilters = reactive({
    status: '',
    startDate: '',
    endDate: '',
    page: 1,
});
/** Sorting state for history table */
const sortState = reactive({
    column: '', // 'started_at', 'status', 'response_time_ms'
    direction: '', // '', 'asc', 'desc'
});

/* 9. Computed */
/**
 * Calculate dynamic page size for list view
 * @returns {number}
 */
const listPageSize = computed(() => {
    const headerHeight = 200;
    const monitorInfoHeight = showMonitorForm.value ? 400 : 200;
    const filtersHeight = 60;
    const paginationHeight = 40;
    const tableHeaderHeight = 40;
    const availableHeight = viewportHeight.value - headerHeight - monitorInfoHeight - filtersHeight - paginationHeight - tableHeaderHeight;
    const rowHeight = 48;
    const maxRows = Math.floor(availableHeight / rowHeight);
    return Math.max(3, Math.min(15, maxRows));
});

/**
 * Filtered and sorted monitor history logs
 * @returns {Array<any>}
 */
const filteredListHistory = computed(() => {
    let filtered = [...logsData.value];
    if (listFilters.startDate) {
        filtered = filtered.filter((log) => log.started_at >= listFilters.startDate);
    }
    if (listFilters.endDate) {
        filtered = filtered.filter((log) => log.started_at <= listFilters.endDate);
    }
    if (sortState.column) {
        filtered.sort((a, b) => {
            let valA = a[sortState.column];
            let valB = b[sortState.column];
            if (sortState.column === 'started_at') {
                valA = new Date(valA);
                valB = new Date(valB);
            }
            if (sortState.direction === 'asc') {
                return valA > valB ? 1 : valA < valB ? -1 : 0;
            } else if (sortState.direction === 'desc') {
                return valA < valB ? 1 : valA > valB ? -1 : 0;
            }
            return 0;
        });
    }
    return filtered;
});

/**
 * Paginated history logs for current page
 * @returns {Array<any>}
 */
const paginatedListHistory = computed(() => {
    const start = (listFilters.page - 1) * listPageSize.value;
    const end = start + listPageSize.value;
    return filteredListHistory.value.slice(start, end);
});

/**
 * Total pages for list history
 * @returns {number}
 */
const listTotalPages = computed(() => Math.ceil(filteredListHistory.value.length / listPageSize.value));

/**
 * Calendar data summary by day
 * @returns {Record<string, {total:number,failed:number,succeeded:number}>}
 */
const calendarData = computed(() => {
    const data = {};
    logsData.value.forEach((log) => {
        const date = log.started_at.split('T')[0];
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

/**
 * Calendar days for selected range or last 90 days
 * @returns {Array<any>}
 */
const calendarDays = computed(() => {
    let startDate, endDate;
    if (listFilters.startDate && listFilters.endDate) {
        startDate = new Date(listFilters.startDate);
        endDate = new Date(listFilters.endDate);
    } else {
        endDate = new Date();
        endDate.setHours(23, 59, 59, 999); // ensure today is included
        startDate = new Date(endDate);
        startDate.setDate(endDate.getDate() - 89); // last 90 days
    }
    const days = [];
    const currentDate = new Date(startDate);
    while (currentDate <= endDate) {
        // include endDate itself
        const dateStr = currentDate.toISOString().split('T')[0];
        const summary = calendarData.value[dateStr];
        let color = '#2d3748';
        let status = 'No data';
        if (summary && summary.total > 0) {
            const failureRate = summary.failed / summary.total;
            if (failureRate === 0) {
                color = '#22c55e';
                status = `Perfect (${summary.total} checks)`;
            } else if (failureRate <= 0.05) {
                color = '#f59e0b';
                status = `Good (${summary.failed} failed / ${summary.total} total)`;
            } else {
                color = '#ef4444';
                status = `Issues (${summary.failed} failed / ${summary.total} total)`;
            }
        }
        days.push({
            date: new Date(currentDate),
            dateStr,
            color,
            status,
            summary,
        });
        currentDate.setDate(currentDate.getDate() + 1);
    }
    return days;
});

/**
 * Calendar SVG rectangles for each day
 * @returns {Array<any>}
 */
const calendarSvgRects = computed(() => {
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
                fill = '#3bd671';
                fillOpacity = 1;
            } else if (rate >= 0.95) {
                fill = '#f29030';
                fillOpacity = 1;
            } else {
                fill = '#ef4444';
                fillOpacity = 1;
            }
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

/**
 * Dynamic graph width based on viewport
 * @returns {number}
 */
const graphWidth = computed(() => {
    const availableWidth = Math.min(viewportHeight.value * 0.8, 800);
    return Math.max(500, availableWidth);
});

/**
 * Dynamic graph height based on viewport
 * @returns {number}
 */
const graphHeight = computed(() => {
    const availableHeight = viewportHeight.value * 0.3;
    return Math.max(200, Math.min(400, availableHeight));
});

/**
 * Graph data for response time
 * @returns {Array<any>}
 */
const graphData = computed(() => {
    let filtered = [...logsData.value];
    if (listFilters.startDate) {
        filtered = filtered.filter((log) => log.started_at >= listFilters.startDate);
    }
    if (listFilters.endDate) {
        filtered = filtered.filter((log) => log.started_at <= listFilters.endDate);
    }
    filtered = filtered.sort((a, b) => new Date(a.started_at) - new Date(b.started_at));
    if (!listFilters.startDate && !listFilters.endDate) {
        return filtered.slice(-30);
    }
    return filtered;
});

/* 10. Watchers */
/** Watch for window resize to update viewport height */
onMounted(() => {
    window.addEventListener('resize', handleResize);
});
onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});
/** Watch saveSuccess to auto-hide message */
watch(saveSuccess, (v) => {
    if (v) setTimeout(() => (saveSuccess.value = false), 1000);
});
/** Watch historyViewMode to refresh graph */
watch(historyViewMode, (mode) => {
    if (mode === 'graph') {
        fetchSingleLog();
    }
});

/* 11. Methods */
/**
 * Handle window resize event
 */
function handleResize() {
    viewportHeight.value = window.innerHeight;
}

/**
 * Save monitor via API
 */
function saveMonitor() {
    const payload: any = {
        label: form.label,
        periodicity: form.periodicity,
        monitor_type: form.monitor_type,
        badge_label: form.badge_label,
    };
    if (form.monitor_type === 'ping') {
        payload.hostname = form.hostname;
        payload.port = form.port;
    } else if (form.monitor_type === 'website') {
        payload.url = form.url;
        payload.check_status = form.check_status;
        payload.keywords = form.keywords
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
    console.log('Form data:', payload);
}

/**
 * Fetch logs for monitor
 * @returns {Promise<any>}
 */
async function fetchSingleLog() {
    logsLoading.value = true;
    try {
        const { data } = await axios.get(`/api/v1/monitors/${monitor.value.id}/logs`);
        logsData.value = data.logs || [];
        if (logsData.value.length > 0) {
            monitor.value.status = logsData.value[0].status;
        }
        return data;
    } catch (e) {
        console.error('Error fetching logs:', e);
        logsData.value = [];
        return null;
    } finally {
        logsLoading.value = false;
    }
}

/**
 * Format date/time string as UTC
 * @param {string} dateString
 * @returns {string}
 */
function formatDateTime(dateString: string) {
    const date = new Date(dateString);
    const year = date.getUTCFullYear();
    const month = String(date.getUTCMonth() + 1).padStart(2, '0');
    const day = String(date.getUTCDate()).padStart(2, '0');
    const hours = String(date.getUTCHours()).padStart(2, '0');
    const minutes = String(date.getUTCMinutes()).padStart(2, '0');
    const seconds = String(date.getUTCSeconds()).padStart(2, '0');
    return `${year}/${month}/${day} ${hours}:${minutes}:${seconds} UTC`;
}

/**
 * Split and trim badge labels
 * @param {string} badgeLabel
 * @returns {string[]}
 */
function getBadgeLabels(badgeLabel: string): string[] {
    if (!badgeLabel) return [];
    return badgeLabel
        .split(',')
        .map((l) => l.trim())
        .filter(Boolean);
}

/**
 * Cycle sort direction for history table
 * @param {string} column
 */
function cycleSort(column: string) {
    if (sortState.column !== column) {
        sortState.column = column;
        sortState.direction = 'asc';
    } else if (sortState.direction === 'asc') {
        sortState.direction = 'desc';
    } else if (sortState.direction === 'desc') {
        sortState.column = '';
        sortState.direction = '';
    } else {
        sortState.direction = 'asc';
    }
}

/**
 * Reset monitor form to current monitor values
 */
function resetMonitorForm() {
    form.label = monitor.value.label;
    form.periodicity = monitor.value.periodicity;
    form.monitor_type = monitor.value.monitor_type;
    form.badge_label = monitor.value.badge_label || '';
    form.hostname = monitor.value.hostname || '';
    form.port = monitor.value.port || null;
    form.url = monitor.value.url || '';
    form.check_status = monitor.value.check_status || false;
    form.keywords = Array.isArray(monitor.value.keywords) ? monitor.value.keywords.join(', ') : '';
}

/**
 * Group calendar SVG rects by month
 * @param {any[]} rects
 * @returns {Array<{label:string,rects:any[]}>}
 */
function groupRectsByMonth(rects: any[]) {
    const months: any[] = [];
    let currentMonth = '';
    let currentYear = '';
    let monthRects: any[] = [];
    let monthLabel = '';
    rects.forEach((rect) => {
        const dateObj = new Date(rect.date);
        const month = dateObj.getMonth();
        const year = dateObj.getFullYear();
        if (currentMonth === '' || month !== currentMonth || year !== currentYear) {
            if (monthRects.length > 0) {
                months.push({ label: monthLabel, rects: monthRects });
            }
            currentMonth = month;
            currentYear = year;
            monthLabel = dateObj.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            monthRects = [];
        }
        monthRects.push(rect);
    });
    if (monthRects.length > 0) {
        months.push({ label: monthLabel, rects: monthRects });
    }
    return months;
}

/* 12. Lifecycle */
onMounted(() => {
    fetchSingleLog();
    intervalId = setInterval(fetchSingleLog, fetchInterval * 1000);
});
onUnmounted(() => {
    clearInterval(intervalId);
});

/* 13. defineExpose */
// No expose needed for this page
</script>

<template>
    <Head :title="`Monitor Details - ${form.label}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto mt-8 w-4xl max-w-4xl">
            <div class="flex flex-col gap-4">
                <h1 class="mb-4 text-center text-3xl font-extrabold tracking-tight drop-shadow-md">Monitor Details</h1>

                <!-- Animated success message -->
                <div v-if="saveSuccess" class="fixed top-5 right-5 rounded bg-green-500 p-3 text-white">Saved successfully!</div>

                <!-- Info + History side by side -->
                <div>
                    <!-- Monitor Info Section -->
                    <div class="mb-8">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-xl font-bold">Monitor Information</h2>
                            <button
                                @click="showMonitorForm = !showMonitorForm"
                                class="rounded bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700"
                            >
                                {{ showMonitorForm ? 'Cancel' : 'Edit Monitor' }}
                            </button>
                        </div>

                        <div>
                            <!-- Info Display -->
                            <div
                                v-if="!showMonitorForm"
                                class="rounded-lg border p-3"
                                :style="{ background: '#171717', color: '#fff', borderColor: 'white' }"
                            >
                                <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-3">
                                    <div>
                                        <span class="text-xs font-semibold text-gray-300">Label:</span>
                                        <p class="text-sm">{{ monitor.label || 'No label set' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold text-gray-300">Type:</span>
                                        <p class="text-sm">{{ monitor.monitor_type }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold text-gray-300">Periodicity:</span>
                                        <p class="text-sm">{{ monitor.periodicity }}s</p>
                                    </div>
                                    <div>
                                        <span class="mb-2 block text-xs font-semibold text-gray-300">Badge Labels:</span>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <span
                                                v-for="label in getBadgeLabels(monitor.badge_label)"
                                                :key="label"
                                                class="inline-block rounded bg-[#262626] px-1 py-0.5 text-xs text-white"
                                            >
                                                {{ label }}
                                            </span>
                                            <span v-if="getBadgeLabels(monitor.badge_label).length === 0" class="text-xs text-gray-400"
                                                >No badge labels set</span
                                            >
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-xs font-semibold text-gray-300">Status:</span>
                                        <p class="text-sm font-bold" :class="monitor.status === 'succeeded' ? 'text-green-500' : 'text-red-500'">
                                            {{ monitor.status }}
                                        </p>
                                    </div>
                                    <div v-if="monitor.monitor_type === 'ping'">
                                        <span class="text-xs font-semibold text-gray-300">Hostname:</span>
                                        <p class="text-sm">{{ monitor.hostname }}</p>
                                        <span class="text-xs font-semibold text-gray-300">Port:</span>
                                        <p class="text-sm">{{ monitor.port }}</p>
                                    </div>
                                    <div v-if="monitor.monitor_type === 'website'">
                                        <span class="text-xs font-semibold text-gray-300">URL:</span>
                                        <p class="text-sm">{{ monitor.url }}</p>
                                        <span class="text-xs font-semibold text-gray-300">Check Status:</span>
                                        <p class="text-sm">{{ monitor.check_status ? 'Yes' : 'No' }}</p>
                                    </div>
                                    <div v-if="monitor.monitor_type === 'website'">
                                        <span class="text-xs font-semibold text-gray-300">Keywords:</span>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <span
                                                v-for="kw in monitor.keywords"
                                                :key="kw"
                                                class="inline-block rounded bg-[#262626] px-1 py-0.5 text-xs text-white"
                                            >
                                                {{ kw }}
                                            </span>
                                            <span v-if="!monitor.keywords || monitor.keywords.length === 0" class="text-xs text-gray-400"
                                                >No keywords set</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Collapsible Monitor Form -->
                            <div v-if="showMonitorForm" class="rounded-lg border bg-blue-50 p-4 dark:bg-blue-900/20">
                                <h3 class="mb-4 text-lg font-semibold">Edit Monitor Information</h3>
                                <form @submit.prevent="saveMonitor" class="space-y-3">
                                    <div>
                                        <label class="mb-1 block text-sm font-semibold">Label</label>
                                        <input
                                            type="text"
                                            v-model="form.label"
                                            class="monitor-label-input w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                        />
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="mb-1 block text-sm font-semibold">Type</label>
                                            <select v-model="form.monitor_type" class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900">
                                                <option value="ping">Ping Monitor</option>
                                                <option value="website">Website Monitor</option>
                                            </select>
                                        </div>
                                        <div>
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
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="mb-1 block text-sm font-semibold">Badge Label</label>
                                            <input
                                                type="text"
                                                v-model="form.badge_label"
                                                placeholder="Badge label"
                                                class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                            />
                                        </div>
                                    </div>

                                    <!-- Type-specific fields -->
                                    <div
                                        v-if="form.monitor_type === 'ping'"
                                        class="grid grid-cols-2 gap-3 rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20"
                                    >
                                        <div>
                                            <label class="mb-1 block text-sm font-medium">Hostname or IP</label>
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

                                    <div v-if="form.monitor_type === 'website'" class="space-y-3 rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
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
                                        </div>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="
                                                resetMonitorForm();
                                                showMonitorForm = false;
                                            "
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
                    </div>

                    <!-- Monitor History Section -->
                    <div class="flex w-4xl flex-col">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-xl font-bold">Monitor History</h2>
                            <div class="flex items-center gap-2">
                                <label>View:</label>
                                <select v-model="historyViewMode" class="rounded border bg-gray-50 px-2 py-1 dark:bg-zinc-900">
                                    <option value="list">List</option>
                                    <option value="calendar">Calendar</option>
                                    <option value="graph">Graph</option>
                                </select>
                                <!-- <span v-if="logsLoading" class="text-sm text-gray-500">Loading...</span>-->
                            </div>
                            <!-- Date filters for all modes -->
                            <div class="mb-3 flex flex-wrap items-end gap-2">
                                <div class="flex items-center gap-1">
                                    <label class="mb-1 block text-xs font-semibold">Start Date</label>
                                    <input
                                        type="date"
                                        v-model="listFilters.startDate"
                                        class="rounded border bg-gray-50 px-2 py-1 text-sm dark:bg-zinc-900"
                                    />
                                    <!-- Reset button -->
                                    <button
                                        type="button"
                                        @click="
                                            listFilters.startDate = '';
                                            listFilters.endDate = '';
                                        "
                                        class="ml-1 rounded p-1 hover:bg-gray-200 dark:hover:bg-gray-700"
                                        title="Reset date filters"
                                    >
                                        <!-- Simple reset SVG icon -->
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="18"
                                            height="18"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 4v5h.582M20 20v-5h-.581M5.5 8A7.003 7.003 0 0112 5c3.866 0 7 3.134 7 7 0 1.657-.672 3.156-1.764 4.236M18.5 16A7.003 7.003 0 0112 19c-3.866 0-7-3.134-7-7 0-1.657.672-3.156 1.764-4.236"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold">End Date</label>
                                    <input
                                        type="date"
                                        v-model="listFilters.endDate"
                                        class="rounded border bg-gray-50 px-2 py-1 text-sm dark:bg-zinc-900"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="flex-1 overflow-auto">
                            <!-- List Mode -->
                            <div v-if="historyViewMode === 'list'">
                                <div
                                    class="mb-4 overflow-auto rounded-lg bg-[#171717] shadow"
                                    :style="`min-height: ${listPageSize * 48}px; border: 2px solid white`"
                                >
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="sticky top-0 bg-[#171717]">
                                            <tr>
                                                <th
                                                    class="cursor-pointer px-4 py-2 text-left text-xs font-semibold text-gray-700 select-none"
                                                    @click="cycleSort('started_at')"
                                                    :class="{ 'text-blue-500': sortState.column === 'started_at' }"
                                                    :title="'Sort by Started At'"
                                                >
                                                    Started At
                                                    <span v-if="sortState.column === 'started_at'">
                                                        <span v-if="sortState.direction === 'asc'">&#9650;</span>
                                                        <span v-else-if="sortState.direction === 'desc'">&#9660;</span>
                                                    </span>
                                                </th>
                                                <th
                                                    class="cursor-pointer px-4 py-2 text-left text-xs font-semibold text-gray-700 select-none"
                                                    @click="cycleSort('status')"
                                                    :class="{ 'text-blue-500': sortState.column === 'status' }"
                                                    :title="'Sort by Status'"
                                                >
                                                    Status
                                                    <span v-if="sortState.column === 'status'">
                                                        <span v-if="sortState.direction === 'asc'">&#9650;</span>
                                                        <span v-else-if="sortState.direction === 'desc'">&#9660;</span>
                                                    </span>
                                                </th>
                                                <th
                                                    class="cursor-pointer px-4 py-2 text-left text-xs font-semibold text-gray-700 select-none"
                                                    @click="cycleSort('response_time_ms')"
                                                    :class="{ 'text-blue-500': sortState.column === 'response_time_ms' }"
                                                    :title="'Sort by Response Time'"
                                                >
                                                    Response Time (ms)
                                                    <span v-if="sortState.column === 'response_time_ms'">
                                                        <span v-if="sortState.direction === 'asc'">&#9650;</span>
                                                        <span v-else-if="sortState.direction === 'desc'">&#9660;</span>
                                                    </span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in paginatedListHistory" :key="item.id" class="h-12">
                                                <td class="px-4 py-2 text-sm">{{ formatDateTime(item.started_at) }}</td>
                                                <td class="px-4 py-2">
                                                    <span
                                                        v-if="item.status === 'succeeded'"
                                                        class="inline-block rounded bg-emerald-600 px-2 py-1 text-xs text-white"
                                                    >
                                                        Succeeded
                                                    </span>
                                                    <span v-else class="inline-block rounded bg-red-600 px-2 py-1 text-xs text-white"> Failed </span>
                                                </td>
                                                <td class="px-4 py-2 text-sm">{{ item.response_time_ms }}</td>
                                            </tr>
                                            <!-- Add empty rows to maintain fixed height -->
                                            <tr v-for="n in Math.max(0, listPageSize - paginatedListHistory.length)" :key="'empty-' + n" class="h-12">
                                                <td class="px-4 py-2" colspan="3">&nbsp;</td>
                                            </tr>
                                            <tr v-if="paginatedListHistory.length === 0">
                                                <td colspan="3" class="px-4 py-6 text-center text-gray-500">No history found.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination Controls -->
                                <div v-if="listTotalPages > 1" class="flex items-center justify-center gap-2">
                                    <button
                                        @click="listFilters.page = Math.max(1, listFilters.page - 1)"
                                        :disabled="listFilters.page === 1"
                                        class="rounded border bg-gray-100 px-3 py-1 text-gray-700 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200"
                                    >
                                        Prev
                                    </button>
                                    <span class="mx-2 text-sm">Page {{ listFilters.page }} of {{ listTotalPages }}</span>
                                    <button
                                        @click="listFilters.page = Math.min(listTotalPages, listFilters.page + 1)"
                                        :disabled="listFilters.page === listTotalPages"
                                        class="rounded border bg-gray-100 px-3 py-1 text-gray-700 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200"
                                    >
                                        Next
                                    </button>
                                </div>
                            </div>

                            <!-- Calendar Mode -->
                            <div v-if="historyViewMode === 'calendar'">
                                <div class="rounded-lg bg-[#171717] p-4" style="border: 2px solid white">
                                    <h3 class="mb-4 text-center font-bold text-white">Monitor Activity</h3>

                                    <!-- SVG Horizontal Calendar Bar -->
                                    <div class="mb-6 flex flex-col gap-2">
                                        <div v-for="(month, idx) in groupRectsByMonth(calendarSvgRects)" :key="idx" class="flex items-center gap-2">
                                            <!-- Month label on the left -->
                                            <div class="w-12 text-right text-xs font-semibold text-gray-300">
                                                {{ month.label }}
                                            </div>

                                            <!-- SVG bar -->
                                            <svg :width="Math.max(month.rects.length * 24, 400)" height="30" xmlns="http://www.w3.org/2000/svg">
                                                <g v-for="(rect, rIdx) in month.rects" :key="rect.date + '-' + rIdx">
                                                    <!-- Rectangle -->
                                                    <rect
                                                        height="18"
                                                        width="18"
                                                        :x="rIdx * 24"
                                                        y="0"
                                                        :fill="rect.fill"
                                                        :fill-opacity="rect.fillOpacity"
                                                        rx="2.75"
                                                        ry="2.75"
                                                    >
                                                        <title>{{ rect.date }} {{ rect.percent }}</title>
                                                    </rect>

                                                    <!-- Day number inside rectangle -->
                                                    <text
                                                        :x="rIdx * 24 + 9"
                                                        y="10.5"
                                                        font-size="10"
                                                        font-weight="bold"
                                                        fill="#ffffff"
                                                        text-anchor="middle"
                                                        alignment-baseline="middle"
                                                        :key="'text-' + rect.date + '-' + rIdx"
                                                    >
                                                        {{ new Date(rect.date).getDate() }}
                                                    </text>
                                                </g>
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Summary stats -->
                                    <div class="mt-4 text-center text-sm text-gray-400">
                                        <div class="flex justify-center gap-4 text-xs">
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
                            <div v-if="historyViewMode === 'graph'">
                                <div class="rounded-lg bg-[#171717] p-4" style="border: 2px solid white">
                                    <svg
                                        :width="graphWidth"
                                        :height="graphHeight"
                                        class="w-full"
                                        viewBox="0 0 650 300"
                                        preserveAspectRatio="xMidYMid meet"
                                    >
                                        <!-- Grid lines -->
                                        <g>
                                            <!-- Horizontal grid lines (y-axis ticks) -->
                                            <template v-for="tick in 5" :key="tick">
                                                <line
                                                    x1="40"
                                                    :y1="260 - (tick - 1) * (220 / 4)"
                                                    x2="580"
                                                    :y2="260 - (tick - 1) * (220 / 4)"
                                                    stroke="#444"
                                                    stroke-dasharray="2,2"
                                                />
                                            </template>

                                            <!-- Vertical grid lines (for each data point) -->
                                            <template v-if="graphData.length">
                                                <line
                                                    v-for="(item, idx) in graphData"
                                                    :key="item.id + '-' + idx"
                                                    :x1="40 + idx * (560 / Math.max(graphData.length - 1, 1))"
                                                    y1="40"
                                                    :x2="40 + idx * (560 / Math.max(graphData.length - 1, 1))"
                                                    y2="260"
                                                    stroke="#444"
                                                    stroke-dasharray="2,2"
                                                />
                                            </template>
                                        </g>
                                        <g v-if="graphData.length">
                                            <template v-for="(item, idx) in graphData" :key="item.id + '-' + idx">
                                                <circle
                                                    :cx="40 + idx * (560 / Math.max(graphData.length - 1, 1))"
                                                    :cy="
                                                        260 - (item.response_time_ms / Math.max(...graphData.map((d) => d.response_time_ms), 1)) * 220
                                                    "
                                                    r="3"
                                                    fill="#3b82f6"
                                                    :title="`${item.started_at}: ${item.response_time_ms}ms`"
                                                />
                                            </template>
                                        </g>
                                        <!-- Axes -->
                                        <line x1="40" y1="260" x2="580" y2="260" stroke="#fff" />
                                        <line x1="40" y1="40" x2="40" y2="260" stroke="#fff" />
                                        <text x="10" y="30" fill="#fff" font-size="12">Response Time (ms)</text>
                                        <text x="500" y="280" fill="#fff" font-size="12">Time</text>
                                        <!-- Y-axis labels -->
                                        <g>
                                            <template v-for="tick in 5" :key="'yaxis-tick-' + tick">
                                                <line
                                                    :x1="35"
                                                    :y1="260 - (tick - 1) * (220 / 4)"
                                                    :x2="40"
                                                    :y2="260 - (tick - 1) * (220 / 4)"
                                                    stroke="#fff"
                                                />
                                                <text :x="0" :y="264 - (tick - 1) * (220 / 4)" fill="#fff" font-size="11">
                                                    {{ Math.round((Math.max(...graphData.map((d) => d.response_time_ms), 1) * (tick - 1)) / 4) }}
                                                </text>
                                            </template>
                                        </g>
                                    </svg>
                                    <div v-if="graphData.length === 0" class="p-4 text-center text-gray-500">No data available for graph.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
