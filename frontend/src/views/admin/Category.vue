<script setup>
import { ref, computed, onMounted } from 'vue'
import Sidebar from '@/components/Sidebar.vue'
import api from '@/services/api'
import Swal from 'sweetalert2'
import { Plus, Pencil, Trash2, ArrowUp, ArrowDown } from 'lucide-vue-next' 
import { useRouter } from 'vue-router'

const router = useRouter()

const categories = ref([])
const loading = ref(true)

const isSidebarOpen = ref(true)
const searchTerm = ref('')
const itemsPerPage = ref(10)
const currentPage = ref(1)
const sortKey = ref('name')
const sortOrder = ref('asc')

const handleSidebarToggle = (isOpen) => {
  isSidebarOpen.value = isOpen
}

const fetchCategories = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/categories')
    categories.value = res.data.data
  } finally {
    loading.value = false
  }
}

const filteredCategories = computed(() => {
  return categories.value.filter(cat =>
    cat.name.toLowerCase().includes(searchTerm.value.toLowerCase())
  )
})

const totalPages = computed(() => {
  return Math.ceil(filteredCategories.value.length / itemsPerPage.value) || 1
})

const paginatedCategories = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredCategories.value.slice(start, end)
})

const pageInfo = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value + 1
  const end = Math.min(currentPage.value * itemsPerPage.value, filteredCategories.value.length)
  return `Showing ${start} to ${end} of ${filteredCategories.value.length} entries`
})

const sortTable = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortOrder.value = 'asc'
  }
}

const handleDelete = async (category) => {
  const confirm = await Swal.fire({
    title: 'Hapus kategori?',
    text: `Kategori "${category.name}" akan dihapus`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Hapus',
    confirmButtonColor: '#ef4444'
  })

  if (!confirm.isConfirmed) return

  try {
    await api.delete(`/admin/categories/${category.id}`)
    await fetchCategories()
    Swal.fire({ icon: 'success', title: 'Berhasil', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 })
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Kategori mungkin masih digunakan.' })
  }
}

onMounted(fetchCategories)
</script>
<template>
  <div class="flex min-h-screen bg-gray-100">
    <Sidebar :current-route="'data-category'" @toggle="handleSidebarToggle" />

    <div :class="['grow bg-gray-100 p-8 pt-10 transition-all duration-300 w-full', isSidebarOpen ? 'ml-70' : 'ml-0']">
      <h1 class="text-3xl font-bold text-slate-800 mb-8">Data Kategori</h1>

      <div class="bg-white rounded-xl shadow-lg p-6">

        <div class="mb-6">
          <router-link :to="{ name: 'TambahCategory' }"
            class="group inline-flex items-center px-4 py-3 bg-linear-to-r from-blue-600 to-blue-800 text-white font-semibold rounded-lg shadow-md hover:shadow-xl hover:from-blue-700 hover:to-blue-900 transition-all duration-300 transform hover:-translate-y-0.5 w-fit">
            <Plus class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" />
            Tambah Data Kategori
          </router-link>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-center mb-4 space-y-3 md:space-y-0">
          <div class="flex items-center space-x-2 text-gray-600">
            <select v-model.number="itemsPerPage"
              class="group p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 hover:border-blue-400 hover:shadow-sm transition-all duration-200 cursor-pointer">
              <option value="5">5</option>
              <option value="10">10</option>
              <option value="20">20</option>
              <option :value="categories.length">All</option>
            </select>
            <span class="text-sm">entries per page</span>
          </div>

          <div class="w-full md:w-auto">
            <label for="search" class="sr-only">Search</label>
            <input type="text" id="search" v-model="searchTerm" placeholder="Cari kategori..."
              class="group w-full md:w-64 p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-blue-400 hover:shadow-sm">
          </div>
        </div>

        <div class="w-full border border-gray-200 rounded-xl shadow-sm bg-white overflow-hidden">
          <table class="w-full table-auto">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr class="text-gray-700 text-[13px] uppercase font-semibold">
                <th class="px-4 py-4 text-left w-12">#</th>
                <th class="px-4 py-4 text-left cursor-pointer hover:bg-blue-100 transition-colors"
                  @click="sortTable('name')">
                  <div class="flex items-center">
                    Nama Kategori
                    <ArrowUp v-if="sortKey === 'name' && sortOrder === 'asc'" class="w-3 h-3 ml-1 text-blue-600" />
                    <ArrowDown v-else-if="sortKey === 'name' && sortOrder === 'desc'"
                      class="w-3 h-3 ml-1 text-blue-600" />
                    <ArrowUp v-else class="w-3 h-3 ml-1 text-gray-300 opacity-50" />
                  </div>
                </th>
                <th class="px-4 py-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="loading">
                <td colspan="3" class="text-center py-6 text-gray-500">Memuat data...</td>
              </tr>

              <tr v-else-if="paginatedCategories.length === 0">
                <td colspan="3" class="text-center py-6 text-gray-500">Tidak ada data kategori</td>
              </tr>

              <tr v-for="(cat, index) in paginatedCategories" :key="cat.id"
                class="hover:bg-blue-50/50 transition-colors">
                <td class="px-4 py-4 text-sm text-gray-500">
                  {{ (currentPage - 1) * itemsPerPage + index + 1 }}
                </td>
                <td class="px-4 py-4 text-sm font-bold text-gray-800">
                  {{ cat.name }}
                </td>
                <td class="px-4 py-4">
                  <div class="flex items-center justify-center gap-2">
                    <button @click="router.push({ name: 'EditCategory', params: { id: cat.id } })"
                      class="inline-flex items-center justify-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded-md text-xs font-medium transition-colors duration-200 shadow-sm min-w-17.5">
                      <Pencil class="w-3.5 h-3.5 mr-1.5" />
                      <span>Ubah</span>
                    </button>

                    <button @click="handleDelete(cat)"
                      class="inline-flex items-center justify-center px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-md text-xs font-medium transition-colors duration-200 shadow-sm min-w-17.5">
                      <Trash2 class="w-3.5 h-3.5 mr-1.5" />
                      <span>Hapus</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col md:flex-row justify-between items-center mt-6 space-y-3 md:space-y-0">
          <p class="text-sm text-gray-600">{{ pageInfo }}</p>

          <div class="flex items-center space-x-1">
            <button @click="currentPage--" :disabled="currentPage === 1"
              class="group p-2.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-linear-to-r hover:from-blue-50 hover:to-blue-100 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 disabled:opacity-50">
              <span class="group-hover:-translate-x-0.5 transition-transform duration-200 inline-block">«</span>
            </button>

            <div v-for="page in totalPages" :key="page">
              <button @click="currentPage = page" :class="{
                'group bg-linear-to-r from-blue-600 to-blue-800 text-white font-semibold shadow-md': page === currentPage,
                'group bg-white text-gray-700 hover:bg-blue-50': page !== currentPage
              }" class="px-4 py-2 border border-gray-300 rounded-lg transition-all duration-200">
                {{ page }}
              </button>
            </div>

            <button @click="currentPage++" :disabled="currentPage === totalPages"
              class="group p-2.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-linear-to-r hover:from-blue-50 hover:to-blue-100 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 disabled:opacity-50">
              <span class="group-hover:translate-x-0.5 transition-transform duration-200 inline-block">»</span>
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
button,
select,
input,
.group {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

select:hover,
input:hover {
  border-color: #60a5fa;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
}

.bg-linear-to-r.from-blue-600.to-blue-800:hover {
  box-shadow: 0 8px 16px -3px rgba(30, 58, 138, 0.4);
}

button:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}
</style>