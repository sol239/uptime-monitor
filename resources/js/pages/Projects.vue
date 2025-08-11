<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Projects', href: '/projects' },
];

const page = usePage();
const auth = computed(() => page.props.auth || {});
const user = computed(() => auth.value.user || null);

// Projects state
const projects = ref<Array<any>>(page.props.projects || []);
const filterLabel = ref('');
const filterTags = ref('');
const sortDirection = ref<'asc' | 'desc'>('asc');

// Edit modal state
const showEditModal = ref(false);
const editingProject = ref<any>(null);
const editForm = ref({
  label: '',
  tags: [] as string[]
});

// Create Project form state
const showProjectForm = ref(false);
const form = ref({
  label: '',
  description: '',
  tags: [] as string[]
});
const tagsInput = ref('');
const saveSuccess = ref(false);

// Computed filtered and sorted projects
const filteredProjects = computed(() => {
  let filtered = projects.value;

  if (filterLabel.value) {
    filtered = filtered.filter((p) => 
      p.label && p.label.toLowerCase().includes(filterLabel.value.toLowerCase())
    );
  }

  if (filterTags.value) {
    filtered = filtered.filter((p) => 
      p.tags && p.tags.some((tag: string) => 
        tag.toLowerCase().includes(filterTags.value.toLowerCase())
      )
    );
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

function openEditModal(project: any) {
  editingProject.value = project;
  editForm.value = {
    label: project.label,
    tags: [...(project.tags || [])]
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

  axios.put(`/api/v1/projects/${editingProject.value.id}`, editForm.value)
    .then(response => {
      const index = projects.value.findIndex(p => p.id === editingProject.value.id);
      if (index !== -1) {
        projects.value[index] = response.data;
      }
      closeEditModal();
    })
    .catch(err => {
      console.error('Failed to update project', err);
    });
}

function deleteProject(id: number) {
  projects.value = projects.value.filter(p => p.id !== id);
  axios.delete(`/api/v1/projects/${id}`)
    .then(() => {
      console.log("Project successfully deleted.")
    })
    .catch(err => {
      console.error('Failed to delete project', err);
    });
}

// Save project handler
function save() {
  form.value.tags = tagsInput.value.split(',').map(t => t.trim()).filter(Boolean);
  console.log("USER:", user.value)
  axios.post('/api/v1/projects', {
    label: form.value.label,
    description: form.value.description,
    tags: form.value.tags,
    user_id: user.value?.id // <-- Added user id to payload
  })
    .then(response => {
      projects.value.push(response.data);
      // Update labels list if new label
      if (response.data.label && !labels.value.includes(response.data.label)) {
        labels.value.push(response.data.label);
      }
      saveSuccess.value = true;
      showProjectForm.value = false;
      // Reset form
      form.value = { label: '', description: '', tags: [] };
      tagsInput.value = '';
    })
    .catch(error => {
      console.error('Failed to create project:', error);
      // Optionally show error to user
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
          <!-- Open modal to create project -->
          <button
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition"
            @click="showProjectForm = true"
          >
            Create Project
          </button>
        </div>

        <!-- Collapsible Project Form -->
        <div v-if="showProjectForm" class="p-4 border rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
          <h3 class="text-lg font-semibold mb-4">Create Project</h3>
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

        <div class="mb-4 flex gap-4 flex-wrap">
          <input type="text" v-model="filterLabel" placeholder="Filter by label"
            class="px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900" />
          <input type="text" v-model="filterTags" placeholder="Filter by tags"
            class="px-2 py-1 rounded border bg-gray-50 dark:bg-zinc-900" />
        </div>
      </div>

      <div class="overflow-x-auto bg-[#171717] dark:bg-[#171717] rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr>
              <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">
                <div class="flex items-center gap-2">
                  Label
                  <div class="flex flex-col">
                    <button
                      @click="sortByLabel('asc')"
                      class="text-gray-400 hover:text-gray-600 text-[10px] leading-none"
                      :class="{ 'text-blue-500': sortDirection === 'asc' }"
                    >
                      ▲
                    </button>
                    <button
                      @click="sortByLabel('desc')"
                      class="text-gray-400 hover:text-gray-600 text-[10px] leading-none"
                      :class="{ 'text-blue-500': sortDirection === 'desc' }"
                    >
                      ▼
                    </button>
                  </div>
                </div>
              </th>
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
                  @click="openEditModal(project)"
                  class="px-3 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition mr-2"
                >
                  Edit
                </button>
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

      <!-- Edit Modal -->
      <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
          <h3 class="text-lg font-semibold mb-4">Edit Project</h3>
          
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Label</label>
            <input 
              v-model="editForm.label"
              type="text" 
              class="w-full border rounded px-3 py-2"
              required
            />
          </div>
          
          <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Tags (comma separated)</label>
            <input 
              :value="editForm.tags.join(', ')"
              @input="editForm.tags = $event.target.value.split(',').map(t => t.trim()).filter(t => t)"
              type="text" 
              class="w-full border rounded px-3 py-2"
              placeholder="tag1, tag2, tag3"
            />
          </div>
          
          <div class="flex justify-end gap-2">
            <button 
              @click="closeEditModal"
              class="px-4 py-2 border rounded hover:bg-gray-100"
            >
              Cancel
            </button>
            <button 
              @click="updateProject"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
              Update
            </button>
          </div>
        </div>
      </div>

      <!-- TODO: Add Pagination Controls Here if needed -->

    </div>
  </AppLayout>
</template>
