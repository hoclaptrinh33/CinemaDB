<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import RankBadge from '@/Components/User/RankBadge.vue';
import { getLevel, getRank, LEVELS } from '@/composables/useRank.js';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    users:   { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search  = ref(props.filters.search ?? '');
const roleFilter = ref(props.filters.role ?? '');
let debounce  = null;

// Reputation edit modal state
const editRepUser = ref(null);
const repForm     = useForm({ reputation: 0 });

function openRepModal(user) {
    editRepUser.value = user;
    repForm.reputation = user.reputation ?? 0;
}
function closeRepModal() {
    editRepUser.value = null;
    repForm.reset();
}
function submitReputation() {
    repForm.patch(route('admin.users.reputation', editRepUser.value.id), {
        preserveScroll: true,
        onSuccess: () => closeRepModal(),
    });
}

function onSearch() {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        const params = {};
        if (search.value)     params.search = search.value;
        if (roleFilter.value) params.role   = roleFilter.value;
        router.get(route('admin.users.index'), params, { preserveState: true, replace: true });
    }, 350);
}

function applyRoleFilter() {
    const params = {};
    if (search.value)     params.search = search.value;
    if (roleFilter.value) params.role   = roleFilter.value;
    router.get(route('admin.users.index'), params, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value     = '';
    roleFilter.value = '';
    router.get(route('admin.users.index'), {}, { preserveState: true, replace: true });
}

function updateRole(userId, role) {
    router.patch(route('admin.users.update', userId), { role }, { preserveScroll: true });
}

function fmtDate(d) { return new Date(d).toLocaleDateString('vi-VN'); }

const roleOptions = ['USER', 'MODERATOR', 'ADMIN'];
const roleBadge   = { ADMIN: 'accent', MODERATOR: 'warning', USER: 'default' };
</script>

<template>
    <div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="font-display font-bold text-2xl text-text-primary">Users</h1>
    </div>
        <div class="flex items-center gap-2 flex-wrap">
            <div class="relative flex-1 min-w-48">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-muted pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                <input v-model="search" @input="onSearch" type="search" placeholder="Tìm username / email..." class="input-base pl-9" />
            </div>

            <!-- Role filter -->
            <div class="w-36 shrink-0">
                <select v-model="roleFilter" class="input-base" @change="applyRoleFilter">
                    <option value="">Tất cả role</option>
                    <option value="ADMIN">ADMIN</option>
                    <option value="MODERATOR">MODERATOR</option>
                    <option value="USER">USER</option>
                </select>
            </div>

            <!-- Clear filters -->
            <button
                v-if="search || roleFilter"
                type="button"
                class="shrink-0 flex items-center gap-1.5 text-xs px-3 py-1.5 rounded-lg border border-border text-text-muted hover:text-text-primary hover:border-border-light transition-colors"
                @click="clearFilters"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
                Xoá bộ lọc
            </button>

            <p class="text-text-muted text-sm ml-auto shrink-0">{{ users.total }} người dùng</p>
        </div>

        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Reputation / Rank</th>
                        <th>Ngày đăng ký</th>
                        <th class="text-right">Đổi role</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in users.data" :key="u.id">
                        <td class="font-semibold text-text-primary text-sm">{{ u.username }}</td>
                        <td class="text-text-muted text-sm">{{ u.email }}</td>
                        <td><Badge :variant="roleBadge[u.role]">{{ u.role }}</Badge></td>

                        <!-- Reputation + Rank -->
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm text-text-muted">{{ u.reputation ?? 0 }}</span>
                                <RankBadge :reputation="u.reputation ?? 0" size="sm" show-label />
                                <button
                                    class="ml-1 text-text-muted hover:text-accent transition-colors"
                                    title="Điều chỉnh reputation"
                                    @click="openRepModal(u)"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                    </svg>
                                </button>
                            </div>
                        </td>

                        <td class="font-mono text-xs text-text-muted">{{ fmtDate(u.created_at) }}</td>
                        <td class="text-right">
                            <select
                                :value="u.role"
                                class="input-base py-1! px-2! text-xs w-32"
                                @change="updateRole(u.id, $event.target.value)"
                            >
                                <option v-for="r in roleOptions" :key="r" :value="r">{{ r }}</option>
                            </select>
                        </td>
                    </tr>
                    <tr v-if="!users.data.length">
                        <td colspan="6" class="text-center text-text-muted py-12 text-sm">Không có dữ liệu</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination v-if="users.last_page > 1" :meta="users" />
    </div>

    <!-- ── Reputation Edit Modal ── -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition-opacity duration-150"
            leave-to-class="opacity-0"
        >
            <div v-if="editRepUser" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closeRepModal" />
                <div class="relative w-full max-w-sm bg-bg-surface border border-border rounded-2xl shadow-2xl p-6 space-y-4">
                    <!-- Header -->
                    <div>
                        <h3 class="font-display font-bold text-lg text-text-primary">
                            Điều chỉnh Reputation
                        </h3>
                        <p class="text-sm text-text-muted mt-0.5">
                            User: <span class="font-semibold text-text-secondary">{{ editRepUser.username }}</span>
                        </p>
                    </div>

                    <!-- Rank preview -->
                    <div class="flex items-center gap-3 p-3 rounded-xl border"
                         :class="[getRank(repForm.reputation).bgColor, getRank(repForm.reputation).borderColor]">
                        <span class="text-2xl">{{ getRank(repForm.reputation).icon }}</span>
                        <div>
                            <p class="font-semibold text-sm" :class="getRank(repForm.reputation).color">
                                {{ getRank(repForm.reputation).label }} · Lv {{ getLevel(repForm.reputation).level }}
                            </p>
                            <p class="text-xs text-text-muted">{{ repForm.reputation }} điểm</p>
                        </div>
                    </div>

                    <!-- Input -->
                    <div>
                        <label class="block text-xs font-semibold text-text-muted uppercase tracking-wider mb-1.5">
                            Điểm mới
                        </label>
                        <input
                            v-model.number="repForm.reputation"
                            type="number"
                            min="0"
                            max="999999"
                            class="input-base w-full"
                            placeholder="Nhập điểm reputation..."
                        />
                        <p v-if="repForm.errors.reputation" class="text-xs text-red-400 mt-1">{{ repForm.errors.reputation }}</p>
                    </div>

                    <!-- Quick set: level thresholds -->
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-text-muted mb-1.5">Đặt nhanh theo mốc cấp</p>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="level in LEVELS.slice().reverse()"
                                :key="level.level"
                                type="button"
                                class="flex items-center gap-1 text-xs px-2 py-1 rounded-md border transition-colors"
                                :class="repForm.reputation === level.min
                                    ? [level.bgColor, level.borderColor, level.color]
                                    : 'border-border text-text-muted hover:border-border-light'"
                                @click="repForm.reputation = level.min"
                            >
                                <span>{{ level.icon }}</span>
                                <span>Lv {{ level.level }}</span>
                                <span>{{ level.min.toLocaleString() }} XP</span>
                            </button>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button
                            type="button"
                            class="btn btn-ghost text-sm"
                            @click="closeRepModal"
                        >
                            Huỷ
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary text-sm"
                            :disabled="repForm.processing"
                            @click="submitReputation"
                        >
                            <svg v-if="repForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Lưu thay đổi
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
