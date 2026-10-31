<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import {
  Users,
  Search,
  KeyRound,
  Trash2,
  Edit,
  Plus,
  CheckCircle2,
  AlertCircle,
  ShieldCheck,
  ShieldAlert,
  Building2,
  Lock,
  User as UserIcon,
  Phone,
  Mail,
  X,
  Camera,
  Upload
} from 'lucide-vue-next'
import { formatImageUrl, handleAvatarError, DEFAULT_USER_AVATAR } from '@/utils/image'
import { useLanguageStore } from '@/stores/language'

const langStore = useLanguageStore()
const users = ref<any[]>([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedRole = ref('all')

const successMessage = ref('')
const errorMessage = ref('')

// Password Reset Modal State
const isPasswordModalOpen = ref(false)
const selectedUserForPassword = ref<any>(null)
const newPassword = ref('')
const isResettingPassword = ref(false)

// Edit User Modal State
const isEditModalOpen = ref(false)
const editAvatarFileRef = ref<HTMLInputElement | null>(null)
const isUploadingEditAvatar = ref(false)
const editForm = ref<any>({
  id: null,
  name: '',
  email: '',
  phone: '',
  role: 'customer',
  status: 'active',
  avatar: ''
})
const isSavingUser = ref(false)

// Create User Modal State
const isCreateModalOpen = ref(false)
const createAvatarFileRef = ref<HTMLInputElement | null>(null)
const isUploadingCreateAvatar = ref(false)
const createForm = ref({
  name: '',
  email: '',
  phone: '',
  role: 'customer',
  password: '',
  avatar: ''
})
const isCreatingUser = ref(false)

const fetchUsers = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const res = await axios.get('/api/v1/admin/users', {
      params: {
        role: selectedRole.value !== 'all' ? selectedRole.value : undefined,
        q: searchQuery.value || undefined
      }
    })
    users.value = res.data.data || []
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Failed to fetch users from database.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchUsers()
})

const handleSearch = () => {
  fetchUsers()
}

// Open Password Modal
const openPasswordModal = (user: any) => {
  selectedUserForPassword.value = user
  newPassword.value = ''
  isPasswordModalOpen.value = true
}

const handleResetPassword = async () => {
  if (!newPassword.value || newPassword.value.length < 6) {
    errorMessage.value = 'Password must be at least 6 characters long.'
    return
  }

  isResettingPassword.value = true
  errorMessage.value = ''
  try {
    const res = await axios.put(`/api/v1/admin/users/${selectedUserForPassword.value.id}/password`, {
      new_password: newPassword.value
    })
    successMessage.value = res.data.message || 'Password changed successfully in database!'
    isPasswordModalOpen.value = false
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Failed to reset password.'
  } finally {
    isResettingPassword.value = false
  }
}

// Open Edit Modal
const openEditModal = (user: any) => {
  editForm.value = {
    id: user.id,
    name: user.name,
    email: user.email,
    phone: user.phone || '',
    role: user.role,
    status: user.status || 'active',
    avatar: user.avatar || ''
  }
  isEditModalOpen.value = true
}

const triggerEditAvatar = () => {
  editAvatarFileRef.value?.click()
}

const handleEditAvatarUpload = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return
  const file = target.files[0]

  isUploadingEditAvatar.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    formData.append('folder', 'avatars')

    const res = await axios.post('/api/v1/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (res.data.url) {
      editForm.value.avatar = formatImageUrl(res.data.url)
    }
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to upload photo.')
  } finally {
    isUploadingEditAvatar.value = false
    if (editAvatarFileRef.value) editAvatarFileRef.value.value = ''
  }
}

const triggerCreateAvatar = () => {
  createAvatarFileRef.value?.click()
}

const handleCreateAvatarUpload = async (e: Event) => {
  const target = e.target as HTMLInputElement
  if (!target.files || target.files.length === 0) return
  const file = target.files[0]

  isUploadingCreateAvatar.value = true
  try {
    const formData = new FormData()
    formData.append('image', file)
    formData.append('folder', 'avatars')

    const res = await axios.post('/api/v1/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (res.data.url) {
      createForm.value.avatar = formatImageUrl(res.data.url)
    }
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to upload photo.')
  } finally {
    isUploadingCreateAvatar.value = false
    if (createAvatarFileRef.value) createAvatarFileRef.value.value = ''
  }
}

const handleSaveUser = async () => {
  isSavingUser.value = true
  errorMessage.value = ''
  try {
    const res = await axios.put(`/api/v1/admin/users/${editForm.value.id}`, editForm.value)
    successMessage.value = res.data.message || 'User updated successfully in database!'
    isEditModalOpen.value = false
    fetchUsers()
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Failed to update user.'
  } finally {
    isSavingUser.value = false
  }
}

// Handle Create User
const handleCreateUser = async () => {
  isCreatingUser.value = true
  errorMessage.value = ''
  try {
    const res = await axios.post('/api/v1/admin/users', createForm.value)
    successMessage.value = res.data.message || 'New user account created successfully in database!'
    isCreateModalOpen.value = false
    createForm.value = { name: '', email: '', phone: '', role: 'customer', password: '' }
    fetchUsers()
    setTimeout(() => { successMessage.value = '' }, 4000)
  } catch (err: any) {
    errorMessage.value = err.response?.data?.message || 'Failed to create user.'
  } finally {
    isCreatingUser.value = false
  }
}

// Handle Delete User
const handleDeleteUser = async (user: any) => {
  if (confirm(`Are you sure you want to permanently delete user "${user.name}" (${user.email}) from the database?`)) {
    try {
      const res = await axios.delete(`/api/v1/admin/users/${user.id}`)
      successMessage.value = res.data.message || 'User deleted successfully.'
      fetchUsers()
      setTimeout(() => { successMessage.value = '' }, 4000)
    } catch (err: any) {
      errorMessage.value = err.response?.data?.message || 'Failed to delete user.'
    }
  }
}
</script>

<template>
  <div class="admin-users-page">
    <div class="admin-page-container">
      <!-- Page Header -->
      <div class="page-top-bar">
        <div>
          <span class="sub-badge">DATABASE SECURITY & USERS</span>
          <h1 class="page-title">{{ langStore.t.admin?.userPasswordControl || 'User & Password Management' }}</h1>
          <p class="page-sub">Manage registered customers, B2B partner workshops, administrators, and reset credentials</p>
        </div>

        <button class="btn-create-user" @click="isCreateModalOpen = true">
          <Plus :size="16" />
          <span>Add New Account</span>
        </button>
      </div>

      <!-- Alerts -->
      <div v-if="successMessage" class="alert-box success-box">
        <CheckCircle2 :size="18" />
        <span>{{ successMessage }}</span>
      </div>

      <div v-if="errorMessage" class="alert-box error-box">
        <AlertCircle :size="18" />
        <span>{{ errorMessage }}</span>
      </div>

      <!-- Filter Bar -->
      <div class="filter-card glass-panel">
        <div class="search-box">
          <Search :size="16" class="search-icon" />
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Search by name, email, or phone..." 
            class="search-input"
            @keyup.enter="handleSearch"
          />
        </div>

        <div class="role-tabs">
          <button 
            class="role-tab" 
            :class="{ active: selectedRole === 'all' }"
            @click="selectedRole = 'all'; fetchUsers()"
          >
            All Accounts
          </button>
          <button 
            class="role-tab" 
            :class="{ active: selectedRole === 'customer' }"
            @click="selectedRole = 'customer'; fetchUsers()"
          >
            B2C Customers
          </button>
          <button 
            class="role-tab" 
            :class="{ active: selectedRole === 'b2b_client' }"
            @click="selectedRole = 'b2b_client'; fetchUsers()"
          >
            B2B Garages
          </button>
          <button 
            class="role-tab" 
            :class="{ active: selectedRole === 'super_admin' }"
            @click="selectedRole = 'super_admin'; fetchUsers()"
          >
            Administrators
          </button>
        </div>
      </div>

      <!-- Users Table -->
      <div class="table-container glass-panel">
        <div v-if="isLoading" class="loading-state">
          <div class="spinner"></div>
          <span>Loading verified database records...</span>
        </div>

        <table v-else class="users-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Member / User</th>
              <th>Email Address</th>
              <th>Phone Number</th>
              <th>Role in Database</th>
              <th>Status</th>
              <th>Registered Date</th>
              <th>Admin Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td><span class="id-tag">#{{ user.id }}</span></td>
              <td>
                <div class="user-meta-cell">
                  <img 
                    v-if="user.avatar" 
                    :src="formatImageUrl(user.avatar, DEFAULT_USER_AVATAR)" 
                    class="user-avatar-initial" 
                    style="object-fit: cover; border: 1.5px solid #ff5500;" 
                    @error="handleAvatarError($event, DEFAULT_USER_AVATAR)"
                  />
                  <div v-else class="user-avatar-initial">{{ user.name?.charAt(0) || 'U' }}</div>
                  <strong>{{ user.name }}</strong>
                </div>
              </td>
              <td><span class="email-text">{{ user.email }}</span></td>
              <td><span class="phone-text">{{ user.phone || '—' }}</span></td>
              <td>
                <span 
                  class="role-chip"
                  :class="{
                    'role-admin': user.role === 'admin' || user.role === 'super_admin',
                    'role-b2b': user.role === 'b2b_client' || user.user_mode === 'b2b',
                    'role-b2c': user.role === 'customer'
                  }"
                >
                  {{ user.role === 'super_admin' ? '👑 SUPER ADMIN' : (user.role === 'b2b_client' ? '🏢 B2B GARAGE' : '👤 B2C BUYER') }}
                </span>
              </td>
              <td>
                <span class="status-pill active-status">Active</span>
              </td>
              <td>
                <span class="date-text">{{ user.created_at?.split(' ')[0] || 'Recently' }}</span>
              </td>
              <td class="actions-cell">
                <button 
                  class="btn-action-icon btn-password" 
                  title="Change / Reset Password"
                  @click="openPasswordModal(user)"
                >
                  <KeyRound :size="15" />
                  <span>Key</span>
                </button>

                <button 
                  class="btn-action-icon btn-edit" 
                  title="Edit User Info"
                  @click="openEditModal(user)"
                >
                  <Edit :size="15" />
                </button>

                <button 
                  class="btn-action-icon btn-delete" 
                  title="Delete User"
                  :disabled="user.id === 1"
                  @click="handleDeleteUser(user)"
                >
                  <Trash2 :size="15" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Password Reset Modal -->
    <div v-if="isPasswordModalOpen" class="modal-backdrop" @click.self="isPasswordModalOpen = false">
      <div class="modal-card glass-panel">
        <div class="modal-head">
          <div class="modal-head-title">
            <KeyRound :size="20" class="text-orange" />
            <h3>Change / Reset Password</h3>
          </div>
          <button class="btn-close" @click="isPasswordModalOpen = false">✕</button>
        </div>

        <p class="modal-desc">
          Set a new database password for <strong>{{ selectedUserForPassword?.name }}</strong> ({{ selectedUserForPassword?.email }}).
        </p>

        <form @submit.prevent="handleResetPassword">
          <div class="form-group">
            <label>New Password (min 6 characters)</label>
            <div class="input-wrapper">
              <Lock :size="16" class="field-icon" />
              <input 
                v-model="newPassword" 
                type="text" 
                placeholder="Enter new strong password" 
                class="custom-input"
                required
              />
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-cancel" @click="isPasswordModalOpen = false">Cancel</button>
            <button type="submit" class="btn-submit" :disabled="isResettingPassword">
              <span>{{ isResettingPassword ? 'Updating Database...' : 'Save New Password' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit User Modal -->
    <div v-if="isEditModalOpen" class="modal-backdrop" @click.self="isEditModalOpen = false">
      <div class="modal-card glass-panel">
        <div class="modal-head">
          <div class="modal-head-title">
            <Edit :size="20" class="text-orange" />
            <h3>Edit User Details</h3>
          </div>
          <button class="btn-close" @click="isEditModalOpen = false">✕</button>
        </div>

        <form @submit.prevent="handleSaveUser" class="modal-form">
          <!-- Avatar Upload -->
          <div class="form-group" style="text-align: center; margin-bottom: 1.25rem;">
            <div style="position: relative; width: 72px; height: 72px; margin: 0 auto 0.5rem;">
              <img 
                :src="formatImageUrl(editForm.avatar, DEFAULT_USER_AVATAR)" 
                style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 2.5px solid #ff5500;" 
                @error="handleAvatarError($event, DEFAULT_USER_AVATAR)"
              />
              <button 
                type="button" 
                @click="triggerEditAvatar"
                style="position: absolute; bottom: 0; right: 0; width: 24px; height: 24px; background: #ff5500; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 1.5px solid #0f172a; cursor: pointer;"
                title="Upload Photo"
              >
                <Camera :size="12" />
              </button>
              <input ref="editAvatarFileRef" type="file" accept="image/*" style="display: none;" @change="handleEditAvatarUpload" />
            </div>
            <button 
              type="button" 
              @click="triggerEditAvatar"
              :disabled="isUploadingEditAvatar"
              style="background: rgba(255,85,0,0.12); border: 1px solid rgba(255,85,0,0.3); color: #ff5500; padding: 0.35rem 0.8rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;"
            >
              <Upload :size="12" />
              <span>{{ isUploadingEditAvatar ? 'Uploading...' : 'Change Profile Picture' }}</span>
            </button>
          </div>

          <div class="form-group">
            <label>Full Name</label>
            <input v-model="editForm.name" type="text" class="custom-input" required />
          </div>

          <div class="form-group">
            <label>Email Address</label>
            <input v-model="editForm.email" type="email" class="custom-input" required />
          </div>

          <div class="form-group">
            <label>Phone Number</label>
            <input v-model="editForm.phone" type="text" class="custom-input" />
          </div>

          <div class="form-group">
            <label>Account Role</label>
            <select v-model="editForm.role" class="custom-select">
              <option value="customer">👤 Retail Consumer (B2C)</option>
              <option value="b2b_client">🏢 B2B Partner Garage</option>
              <option value="admin">⚡ Administrator</option>
              <option value="super_admin">👑 Super Administrator</option>
            </select>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-cancel" @click="isEditModalOpen = false">Cancel</button>
            <button type="submit" class="btn-submit" :disabled="isSavingUser">
              <span>{{ isSavingUser ? 'Saving...' : 'Update User' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Create User Modal -->
    <div v-if="isCreateModalOpen" class="modal-backdrop" @click.self="isCreateModalOpen = false">
      <div class="modal-card glass-panel">
        <div class="modal-head">
          <div class="modal-head-title">
            <Plus :size="20" class="text-orange" />
            <h3>Create New Database Account</h3>
          </div>
          <button class="btn-close" @click="isCreateModalOpen = false">✕</button>
        </div>

        <form @submit.prevent="handleCreateUser" class="modal-form">
          <!-- Avatar Upload -->
          <div class="form-group" style="text-align: center; margin-bottom: 1.25rem;">
            <div style="position: relative; width: 72px; height: 72px; margin: 0 auto 0.5rem;">
              <img 
                :src="formatImageUrl(createForm.avatar, DEFAULT_USER_AVATAR)" 
                style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 2.5px solid #ff5500;" 
                @error="handleAvatarError($event, DEFAULT_USER_AVATAR)"
              />
              <button 
                type="button" 
                @click="triggerCreateAvatar"
                style="position: absolute; bottom: 0; right: 0; width: 24px; height: 24px; background: #ff5500; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 1.5px solid #0f172a; cursor: pointer;"
                title="Upload Photo"
              >
                <Camera :size="12" />
              </button>
              <input ref="createAvatarFileRef" type="file" accept="image/*" style="display: none;" @change="handleCreateAvatarUpload" />
            </div>
            <button 
              type="button" 
              @click="triggerCreateAvatar"
              :disabled="isUploadingCreateAvatar"
              style="background: rgba(255,85,0,0.12); border: 1px solid rgba(255,85,0,0.3); color: #ff5500; padding: 0.35rem 0.8rem; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;"
            >
              <Upload :size="12" />
              <span>{{ isUploadingCreateAvatar ? 'Uploading...' : 'Choose Profile Picture' }}</span>
            </button>
          </div>

          <div class="form-group">
            <label>Full Name *</label>
            <input v-model="createForm.name" type="text" placeholder="e.g. Heng Pisey" class="custom-input" required />
          </div>

          <div class="form-group">
            <label>Email Address *</label>
            <input v-model="createForm.email" type="email" placeholder="e.g. pisey@apexspeed.com" class="custom-input" required />
          </div>

          <div class="form-group">
            <label>Phone Number</label>
            <input v-model="createForm.phone" type="text" placeholder="+855 12 345 678" class="custom-input" />
          </div>

          <div class="form-group">
            <label>Account Role *</label>
            <select v-model="createForm.role" class="custom-select" required>
              <option value="customer">👤 Retail Consumer (B2C)</option>
              <option value="b2b_client">🏢 B2B Partner Garage</option>
              <option value="admin">⚡ Administrator</option>
            </select>
          </div>

          <div class="form-group">
            <label>Initial Password *</label>
            <input v-model="createForm.password" type="text" placeholder="At least 6 characters" class="custom-input" required />
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-cancel" @click="isCreateModalOpen = false">Cancel</button>
            <button type="submit" class="btn-submit" :disabled="isCreatingUser">
              <span>{{ isCreatingUser ? 'Creating...' : 'Create Account' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-users-page {
  padding: 2rem 0 5rem;
  width: 100%;
}

.admin-page-container {
  width: 100% !important;
  max-width: 100% !important;
  margin: 0 !important;
  padding: 0 2rem;
}

.page-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.sub-badge {
  display: inline-block;
  background: rgba(255, 85, 0, 0.12);
  color: #ff7834;
  border: 1px solid rgba(255, 85, 0, 0.3);
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  margin-bottom: 0.4rem;
}

.page-title {
  font-size: 1.85rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 0.35rem;
}

.page-sub {
  color: #94a3b8;
  font-size: 0.92rem;
  margin: 0;
}

.btn-create-user {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #ff5500;
  color: white;
  padding: 0.75rem 1.4rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.9rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-create-user:hover {
  background: #ff6a1f;
  transform: translateY(-2px);
  box-shadow: 0 4px 18px rgba(255, 85, 0, 0.35);
}

.alert-box {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.9rem 1.25rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.88rem;
}

.success-box {
  background: rgba(34, 197, 94, 0.12);
  border: 1px solid rgba(34, 197, 94, 0.3);
  color: #4ade80;
}

.error-box {
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
}

.glass-panel {
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
}

/* Filter Card */
.filter-card {
  padding: 1rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.75rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.search-box {
  position: relative;
  flex: 1;
  max-width: 450px;
}

.search-icon {
  position: absolute;
  left: 0.9rem;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.search-input {
  width: 100%;
  background: rgba(2, 6, 23, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: #ffffff;
  padding: 0.65rem 0.9rem 0.65rem 2.4rem;
  font-size: 0.88rem;
  outline: none;
}

.search-input:focus {
  border-color: #ff5500;
}

.role-tabs {
  display: flex;
  gap: 0.5rem;
}

.role-tab {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #94a3b8;
  padding: 0.5rem 0.9rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.role-tab:hover {
  color: white;
  background: rgba(255, 255, 255, 0.1);
}

.role-tab.active {
  background: rgba(255, 85, 0, 0.15);
  border-color: #ff5500;
  color: #ff7834;
  font-weight: 700;
}

/* Table */
.table-container {
  overflow-x: auto;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.86rem;
  text-align: left;
}

.users-table th {
  background: rgba(2, 6, 23, 0.5);
  color: #94a3b8;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.users-table td {
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  vertical-align: middle;
}

.id-tag {
  font-family: monospace;
  color: #64748b;
  font-size: 0.78rem;
}

.user-meta-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar-initial {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: rgba(255, 85, 0, 0.15);
  color: #ff7834;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
}

.email-text {
  color: #cbd5e1;
}

.phone-text {
  color: #94a3b8;
}

.role-chip {
  font-size: 0.7rem;
  font-weight: 800;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  white-space: nowrap;
}

.role-admin {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.role-b2b {
  background: rgba(2, 132, 199, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.role-b2c {
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-pill {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.18rem 0.55rem;
  border-radius: 9999px;
}

.active-status {
  background: rgba(34, 197, 94, 0.12);
  color: #4ade80;
}

.date-text {
  color: #64748b;
  font-size: 0.75rem;
}

.actions-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-action-icon {
  display: flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.45rem 0.65rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-password {
  background: rgba(255, 85, 0, 0.15);
  color: #ff7834;
  border: 1px solid rgba(255, 85, 0, 0.3);
}

.btn-password:hover {
  background: #ff5500;
  color: white;
}

.btn-edit {
  background: rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
}

.btn-edit:hover {
  background: rgba(255, 255, 255, 0.18);
  color: white;
}

.btn-delete {
  background: rgba(239, 68, 68, 0.1);
  color: #f87171;
}

.btn-delete:hover:not(:disabled) {
  background: #ef4444;
  color: white;
}

.btn-delete:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

/* Modals */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  padding: 1.5rem;
}

.modal-card {
  width: 100%;
  max-width: 480px;
  padding: 2rem;
  border-color: rgba(255, 85, 0, 0.3);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7);
}

.modal-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.modal-head-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-head-title h3 {
  margin: 0;
  color: white;
  font-size: 1.2rem;
}

.btn-close {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.25rem;
  cursor: pointer;
}

.modal-desc {
  font-size: 0.85rem;
  color: #94a3b8;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  margin-bottom: 1.2rem;
}

.form-group label {
  font-size: 0.82rem;
  font-weight: 600;
  color: #cbd5e1;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.field-icon {
  position: absolute;
  left: 0.9rem;
  color: #64748b;
}

.custom-input,
.custom-select {
  width: 100%;
  background: rgba(2, 6, 23, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: #ffffff;
  padding: 0.75rem 0.9rem;
  font-size: 0.88rem;
  outline: none;
  transition: all 0.2s;
}

.input-wrapper .custom-input {
  padding-left: 2.5rem;
}

.custom-input:focus,
.custom-select:focus {
  border-color: #ff5500;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.85rem;
  margin-top: 1.75rem;
}

.btn-cancel {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #cbd5e1;
  padding: 0.65rem 1.2rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-submit {
  background: #ff5500;
  color: white;
  border: none;
  padding: 0.65rem 1.4rem;
  border-radius: 6px;
  font-weight: 700;
  cursor: pointer;
}

.btn-submit:hover {
  background: #ff6a1f;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  gap: 1rem;
  color: #94a3b8;
}

.text-orange { color: #ff5500; }

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(255, 85, 0, 0.2);
  border-top-color: #ff5500;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
