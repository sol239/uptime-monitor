<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Projects', href: '/projects' }];

const page = usePage();
const auth = computed(() => page.props.auth || {});
const user = computed(() => auth.value.user || null);

// Projects state
const projects = ref<Array<any>>(page.props.projects || []);
const filterLabel = ref('');
const filterTags = ref('');
const sortDirection = ref<'id' | 'asc' | 'desc'>('id');

// Edit modal state
const showEditModal = ref(false);
const editingProject = ref<any>(null);
const editForm = ref({
    label: '',
    tags: [] as string[],
});

// Create Project form state
const showProjectForm = ref(false);
const form = ref({
    label: '',
    description: '',
    tags: [] as string[],
});
const tagsInput = ref('');
const saveSuccess = ref(false);

// Pagination state - now dynamic
const viewportHeight = ref(window.innerHeight);
const currentPage = ref(1);

// Calculate items per page based on viewport height
const ITEMS_PER_PAGE = computed(() => {
    // Estimate available height for table
    const headerHeight = 200; // approximate height of header, filters, etc.
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

// Computed filtered and sorted projects
const filteredProjects = computed(() => {
    let filtered = projects.value;

    if (filterLabel.value) {
        filtered = filtered.filter((p) => p.label && p.label.toLowerCase().includes(filterLabel.value.toLowerCase()));
    }

    if (filterTags.value) {
        filtered = filtered.filter((p) => p.tags && p.tags.some((tag: string) => tag.toLowerCase().includes(filterTags.value.toLowerCase())));
    }

    if (sortDirection.value === 'asc') {
        filtered = filtered.sort((a, b) => {
            if (a.label < b.label) return -1;
            if (a.label > b.label) return 1;
            return 0;
        });
    } else if (sortDirection.value === 'desc') {
        filtered = filtered.sort((a, b) => {
            if (a.label < b.label) return 1;
            if (a.label > b.label) return -1;
            return 0;
        });
    } else {
        // Default sort by id
        filtered = filtered.sort((a, b) => a.id - b.id);
    }

    return filtered;
});

const paginatedProjects = computed(() => {
    const start = (currentPage.value - 1) * ITEMS_PER_PAGE.value;
    return filteredProjects.value.slice(start, start + ITEMS_PER_PAGE.value);
});
const totalPages = computed(() => Math.ceil(filteredProjects.value.length / ITEMS_PER_PAGE.value));

function sortByLabel(direction: 'asc' | 'desc') {
    sortDirection.value = direction;
}

function openEditModal(project: any) {
    editingProject.value = project;
    editForm.value = {
        label: project.label,
        tags: [...(project.tags || [])],
    };
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
    editingProject.value = null;
    editForm.value = { label: '', tags: [] };
}

function updateProject() {
    if (!editingProject.value) return;

    axios
        .put(`/api/v1/projects/${editingProject.value.id}`, editForm.value)
        .then((response) => {
            const index = projects.value.findIndex((p) => p.id === editingProject.value.id);
            if (index !== -1) {
                projects.value[index] = response.data;
            }
            closeEditModal();
        })
        .catch((err) => {
            console.error('Failed to update project', err);
        });
}

function deleteProject(id: number) {
    projects.value = projects.value.filter((p) => p.id !== id);
    axios
        .delete(`/api/v1/projects/${id}`)
        .then(() => {
            console.log('Project successfully deleted.');
        })
        .catch((err) => {
            console.error('Failed to delete project', err);
        });
}

// Save project handler
function resetProjectForm() {
    showProjectForm.value = false;
    form.value = { label: '', description: '', tags: [] };
    tagsInput.value = '';
}

function save() {
    form.value.tags = tagsInput.value
        .split(',')
        .map((t) => t.trim())
        .filter(Boolean);
    console.log('USER:', user.value);
    axios
        .post('/api/v1/projects', {
            label: form.value.label,
            description: form.value.description,
            tags: form.value.tags,
            user_id: user.value?.id, // <-- Added user id to payload
        })
        .then((response) => {
            projects.value.push(response.data);
            // Update labels list if new label
            if (response.data.label && !labels.value.includes(response.data.label)) {
                labels.value.push(response.data.label);
            }
            saveSuccess.value = true;
            resetProjectForm();
        })
        .catch((error) => {
            console.error('Failed to create project:', error);
            resetProjectForm();
            // Optionally show error to user
        });
}

function cycleSortDirection() {
    if (sortDirection.value === 'id') {
        sortDirection.value = 'asc';
    } else if (sortDirection.value === 'asc') {
        sortDirection.value = 'desc';
    } else {
        sortDirection.value = 'id';
    }
}
</script>

<template>
    <Head title="Projects" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto mt-8 max-w-4xl">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold">Projects</h2>
                </div>

                <!-- Collapsible Project Form -->
                <div v-if="showProjectForm" class="rounded-lg border bg-emerald-50 p-4 dark:bg-emerald-900/20">
                    <h3 class="mb-4 text-lg font-semibold">Create Project</h3>
                    <form @submit.prevent="save">
                        <div class="mb-4">
                            <label class="mb-1 block text-sm font-semibold">Label</label>
                            <input type="text" v-model="form.label" class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900" />
                        </div>
                        <div class="mb-4">
                            <label class="mb-1 block text-sm font-semibold">Description</label>
                            <textarea
                                v-model="form.description"
                                class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                                rows="3"
                            ></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="mb-1 block text-sm font-semibold">Tags</label>
                            <input
                                type="text"
                                v-model="tagsInput"
                                placeholder="Comma separated"
                                class="w-full rounded border bg-gray-50 px-3 py-2 dark:bg-zinc-900"
                            />
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span v-for="tag in form.tags" :key="tag" class="inline-block rounded bg-[#262626] px-2 py-1 text-xs">{{ tag }}</span>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button
                                type="button"
                                @click="showProjectForm = false"
                                class="rounded border px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Cancel
                            </button>
                            <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-white transition hover:bg-emerald-700">
                                Save Project
                            </button>
                        </div>
                    </form>
                </div>

                <div class="mb-4 flex flex-wrap items-center gap-4">
                    <input
                        type="text"
                        v-model="filterLabel"
                        placeholder="Filter by label"
                        class="rounded border bg-gray-50 px-2 py-1 dark:bg-zinc-900"
                    />
                    <input
                        type="text"
                        v-model="filterTags"
                        placeholder="Filter by tags"
                        class="rounded border bg-gray-50 px-2 py-1 dark:bg-zinc-900"
                    />
                    <button class="rounded-md bg-green-600 px-4 py-2 text-white transition hover:bg-green-700" @click="showProjectForm = true">
                        Create Project
                    </button>
                </div>
            </div>

            <div
                class="overflow-x-auto rounded-lg border-2 border-white bg-[#171717] shadow dark:bg-[#171717]"
                :style="`min-height: ${ITEMS_PER_PAGE * 56}px`"
            >
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="cycleSortDirection"
                                        class="cursor-pointer text-xs font-semibold text-gray-700 hover:text-blue-500"
                                        :class="{
                                            'text-blue-500': sortDirection !== 'id',
                                        }"
                                        :title="
                                            sortDirection === 'id'
                                                ? 'Sort by ID (default)'
                                                : sortDirection === 'asc'
                                                  ? 'Sort by Label (A-Z)'
                                                  : 'Sort by Label (Z-A)'
                                        "
                                    >
                                        Label {{ sortDirection === 'id' ? '•' : sortDirection === 'asc' ? '▲' : '▼' }}
                                    </button>
                                </div>
                            </th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Tags</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="project in paginatedProjects" :key="project.id">
                            <td class="px-4 py-2">{{ project.label }}</td>
                            <td class="px-4 py-2">
                                <span v-for="tag in project.tags || []" :key="tag" class="mr-1 inline-block rounded bg-[#262626] px-2 py-1 text-xs">
                                    {{ tag }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex flex-col gap-1 sm:flex-row sm:gap-2">
                                    <button
                                        @click="openEditModal(project)"
                                        class="rounded bg-yellow-500 px-2 py-1 text-xs text-white transition hover:bg-yellow-600"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteProject(project.id)"
                                        class="rounded bg-red-500 px-2 py-1 text-xs text-white transition hover:bg-red-600"
                                    >
                                        Delete
                                    </button>
                                    <a
                                        :href="`/projects/${project.id}`"
                                        class="rounded bg-blue-500 px-2 py-1 text-center text-xs text-white transition hover:bg-blue-600"
                                    >
                                        Details
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <!-- Add empty rows to maintain fixed height -->
                        <tr v-for="n in Math.max(0, ITEMS_PER_PAGE - paginatedProjects.length)" :key="'empty-' + n" class="h-14">
                            <td class="px-4 py-2" colspan="3">&nbsp;</td>
                        </tr>
                        <tr v-if="paginatedProjects.length === 0">
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">No projects found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls - Outside table container -->
            <div v-if="totalPages > 1" class="mt-4 flex items-center justify-center gap-2 py-4">
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

            <!-- Edit Modal -->
            <div v-if="showEditModal" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black">
                <div class="w-full max-w-md rounded-lg bg-white p-6 dark:bg-gray-800">
                    <h3 class="mb-4 text-lg font-semibold">Edit Project</h3>

                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium">Label</label>
                        <input v-model="editForm.label" type="text" class="w-full rounded border px-3 py-2" required />
                    </div>

                    <div class="mb-6">
                        <label class="mb-2 block text-sm font-medium">Tags (comma separated)</label>
                        <input
                            :value="editForm.tags.join(', ')"
                            @input="
                                editForm.tags = $event.target.value
                                    .split(',')
                                    .map((t) => t.trim())
                                    .filter((t) => t)
                            "
                            type="text"
                            class="w-full rounded border px-3 py-2"
                            placeholder="tag1, tag2, tag3"
                        />
                    </div>

                    <div class="flex justify-end gap-2">
                        <button @click="closeEditModal" class="rounded border px-4 py-2 hover:bg-gray-100">Cancel</button>
                        <button @click="updateProject" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Update</button>
                    </div>
                </div>
            </div>

            <!-- TODO: Add Pagination Controls Here if needed -->
        </div>
    </AppLayout>
</template>
