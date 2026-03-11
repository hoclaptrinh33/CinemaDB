<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/UI/Button.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import ConfirmModal from '@/Components/UI/ConfirmModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    badges:  { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search    = ref(props.filters.search ?? '');
const confirmId = ref(null);
let debounce    = null;

const tierColor = {
    WOOD:     'bg-orange-900/20 text-orange-300 border-orange-700/40',
    IRON:     'bg-stone-500/20 text-stone-300 border-stone-400/40',
    BRONZE:   'bg-amber-600/20 text-amber-500 border-amber-600/40',
    SILVER:   'bg-slate-400/20 text-slate-300 border-slate-400/40',
    GOLD:     'bg-yellow-400/20 text-yellow-400 border-yellow-400/40',
    PLATINUM: 'bg-cyan-300/20 text-cyan-300 border-cyan-300/40',
    DIAMOND:  'bg-sky-300/20 text-sky-300 border-sky-300/40',
};

function onSearch() {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(route('admin.badges.index'), { search: search.value }, { preserveState: true, replace: true });
    }, 350);
}

function destroy(id) {
    router.delete(route('admin.badges.destroy', id), {
        preserveScroll: true,
        onSuccess: () => (confirmId.value = null),
    });
}
</script>

<template>
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">Huy hiệu</h1>
            <Link :href="route('admin.badges.create')">
                <Button variant="primary" size="sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Thêm huy hiệu
                </Button>
            </Link>
        </div>

        <!-- Search -->
        <div class="flex items-center gap-2">
            <div class="relative flex-1 min-w-48">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--color-text-muted)] pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
                <input v-model="search" type="search" placeholder="Tìm theo tên hoặc slug..."
                       class="input-base pl-9" @input="onSearch" />
            </div>
            <p class="text-[var(--color-text-muted)] text-sm ml-auto shrink-0">{{ badges.total }} huy hiệu</p>
        </div>

        <!-- Table -->
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Icon</th>
                        <th>Tên / Slug</th>
                        <th>Mô tả</th>
                        <th>Tier</th>
                        <th class="text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in badges.data" :key="b.badge_id">
                        <!-- Icon -->
                        <td class="w-14">
                            <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center overflow-hidden"
                                 :class="b.tier === 'WOOD' ? 'border-orange-800'
                                     : b.tier === 'IRON' ? 'border-stone-500'
                                     : b.tier === 'BRONZE' ? 'border-amber-600'
                                     : b.tier === 'SILVER' ? 'border-slate-400'
                                     : b.tier === 'GOLD'   ? 'border-yellow-400'
                                     : b.tier === 'DIAMOND' ? 'border-sky-300'
                                     :                        'border-cyan-300'">
                                <img v-if="b.icon_path" :src="b.icon_path" :alt="b.name"
                                     class="w-full h-full object-cover"
                                     @error="(e) => e.target.style.display = 'none'" />
                                <span v-else class="text-lg">🏅</span>
                            </div>
                        </td>
                        <!-- Name + slug -->
                        <td>
                            <p class="font-semibold text-sm text-[var(--color-text-primary)]">{{ b.name }}</p>
                            <p class="text-xs text-[var(--color-text-muted)] font-mono">{{ b.slug }}</p>
                        </td>
                        <!-- Description -->
                        <td class="text-sm text-[var(--color-text-muted)] max-w-xs truncate">
                            {{ b.description ?? '—' }}
                        </td>
                        <!-- Tier -->
                        <td>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border"
                                  :class="tierColor[b.tier]">
                                {{ b.tier }}
                            </span>
                        </td>
                        <!-- Actions -->
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="route('admin.badges.edit', b.badge_id)">
                                    <Button variant="ghost" size="sm">Sửa</Button>
                                </Link>
                                <Button variant="danger" size="sm" @click="confirmId = b.badge_id">Xoá</Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!badges.data.length">
                        <td colspan="5" class="text-center py-8 text-[var(--color-text-muted)]">
                            Chưa có huy hiệu nào.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination v-if="badges.last_page > 1" :meta="badges" />

        <ConfirmModal
            :show="confirmId !== null"
            title="Xoá huy hiệu"
            message="Hành động này không thể hoàn tác. Bạn chắc chắn muốn xoá huy hiệu này?"
            @confirm="destroy(confirmId)"
            @close="confirmId = null"
        />
    </div>
</template>
