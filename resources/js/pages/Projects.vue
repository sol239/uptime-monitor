<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Projects', href: '/projects' },
];

const page = usePage();
const auth = computed(() => page.props.auth || {});
const user = computed(() => auth.value.user || null);

// Projects state
const projects = ref<Array<any>>(page.props.projects || []);
const labels = ref<Array<string>>([]);
const filterLabel = ref('');
const sortDirection = ref<'asc' | 'desc'>('asc');

// Extract unique labels from projects for filtering dropdown
const labelSet = new Set<string>();
projects.value.forEach((p) => {
  if (p.label) labelSet.add(p.label);
});
labels.value = Array.from(labelSet);

// Computed filtered and sorted projects
const filteredProjects = computed(() => {
  let filtered = projects.value;

  if (filterLabel.value) {
    filtered = filtered.filter((p) => p.label === filterLabel.value);
  }

  filtered = filtered.sort((a, b) => {
    if (a.label < b.label) return sortDirection.value === 'asc' ? -1 : 1;
    if (a.label > b.label) return sortDirection.value === 'asc' ? 1 : -1;
    return 0;
  });

  return filtered;
});

function sortByLabel(direction: 'asc' | 'desc') {
  sortDirection.value = direction;
}

function deleteProject(id: number) {
  axios.delete(`/api/projects/${id}`)
    .then(() => {
      projects.value = projects.value.filter(p => p.id !== id);
    })
    .catch(err => {
      console.error('Failed to delete project', err);
    });
}
</script>

<template>
  <Head title="Projects" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl mx-auto mt-8">
      <div class="flex flex-col gap-4 mb-6">
        <div class="flex justify-between items-center">
          <h2 class="text-2xl font-bold">Projects</h2>
          <!-- TODO: Open modal to create project -->
          <button
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition"
          >
            Create Project
          </button>
        </div>

        <div class="flex flex-wrap gap-4 items-center">
          <div>
            <label class="text-sm font-medium">Label:</label>
            <select v-model="filterLabel" class="border rounded px-2 py-1">
              <option value="">All</option>
              <option v-for="label in labels" :key="label" :value="label">{{ label }}</option>
            </select>
          </div>

          <div>
            <label class="text-sm font-medium">Sort by Label:</label>
            <button
              @click="sortByLabel('asc')"
              class="px-2 py-1 border rounded hover:bg-gray-100"
            >
              Asc
            </button>
            <button
              @click="sortByLabel('desc')"
              class="px-2 py-1 border rounded hover:bg-gray-100"
            >
              Desc
            </button>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto bg-[#171717] dark:bg-[#171717] rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Label</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Tags</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="project in filteredProjects" :key="project.id">
              <td class="px-4 py-2">{{ project.label }}</td>
              <td class="px-4 py-2">
                <span
                  v-for="tag in project.tags || []"
                  :key="tag"
                  class="inline-block bg-[#262626] text-xs rounded px-2 py-1 mr-1"
                >
                  {{ tag }}
                </span>
              </td>
              <td class="px-4 py-2">
                <button
                  @click="deleteProject(project.id)"
                  class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition"
                >
                  Delete
                </button>
                <a
                  :href="`/projects/${project.id}`"
                  class="ml-2 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition"
                >
                  Details
                </a>
              </td>
            </tr>
            <tr v-if="filteredProjects.length === 0">
              <td colspan="3" class="px-4 py-6 text-center text-gray-500">No projects found.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- TODO: Add Pagination Controls Here if needed -->

    </div>
  </AppLayout>
</template>
