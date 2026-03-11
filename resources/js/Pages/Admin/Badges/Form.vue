<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/UI/Button.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    badge:          { type: Object, default: null },
    conditionTypes: { type: Object, default: () => ({}) },
});

const isEdit = !!props.badge;

const form = useForm({
    slug:            props.badge?.slug            ?? '',
    name:            props.badge?.name            ?? '',
    description:     props.badge?.description     ?? '',
    icon_path:       props.badge?.icon_path       ?? '',
    tier:            props.badge?.tier            ?? 'BRONZE',
    condition_type:  props.badge?.condition_type  ?? 'manual',
    condition_value: props.badge?.condition_value ?? null,
});

// Condition types that require a numeric threshold value
const needsValue = computed(() =>
    ['review_count', 'helpful_votes', 'collections_count', 'early_bird', 'distinct_types', 'collection_nominations_received', 'follower_count', 'following_count', 'activity_streak', 'login_streak']
        .includes(form.condition_type)
);

const conditionValueLabel = computed(() => {
    const labels = {
        review_count:      'Số reviews tối thiểu',
        helpful_votes:     'Số helpful votes tối thiểu',
        collections_count: 'Số bộ sưu tập tối thiểu',
        early_bird:        'Số ngày sau khi phim ra mắt (mặc định 7)',
        distinct_types:    'Số loại nội dung khác nhau (tối đa 3)',
        collection_nominations_received: 'Số lượt đề cử list tối thiểu',
        follower_count:    'Số người theo dõi tối thiểu',
        following_count:   'Số người bạn đã theo dõi tối thiểu',
        activity_streak:   'Số ngày hoạt động liên tiếp tối thiểu',
        login_streak:      'Số ngày đăng nhập liên tiếp tối thiểu',
    };
    return labels[form.condition_type] ?? 'Giá trị ngưỡng';
});

const conditionValuePlaceholder = computed(() => {
    const defaults = {
        review_count:      '10',
        helpful_votes:     '100',
        collections_count: '5',
        early_bird:        '7',
        distinct_types:    '3',
        collection_nominations_received: '10',
        follower_count:    '10',
        following_count:   '25',
        activity_streak:   '7',
        login_streak:      '7',
    };
    return defaults[form.condition_type] ?? '0';
});

function submit() {
    if (isEdit) {
        form.patch(route('admin.badges.update', props.badge.badge_id));
    } else {
        form.post(route('admin.badges.store'));
    }
}
</script>

<template>
    <div class="max-w-2xl space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <button class="btn btn-ghost !px-2 !py-2" @click="router.get(route('admin.badges.index'))">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </button>
            <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">
                {{ isEdit ? 'Sửa huy hiệu' : 'Tạo huy hiệu mới' }}
            </h1>
        </div>

        <form class="card p-6 space-y-5" @submit.prevent="submit">
            <!-- Slug -->
            <div>
                <InputLabel for="slug" value="Slug (định danh duy nhất)" />
                <TextInput
                    id="slug"
                    v-model="form.slug"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="vd: first-review"
                    :disabled="isEdit"
                />
                <InputError :message="form.errors.slug" class="mt-1" />
                <p v-if="isEdit" class="text-xs text-[var(--color-text-muted)] mt-1">
                    Slug không thể thay đổi sau khi tạo.
                </p>
            </div>

            <!-- Name -->
            <div>
                <InputLabel for="name" value="Tên hiển thị" />
                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" placeholder="vd: Nhà phê bình mới" />
                <InputError :message="form.errors.name" class="mt-1" />
            </div>

            <!-- Description -->
            <div>
                <InputLabel for="description" value="Mô tả điều kiện" />
                <textarea
                    id="description"
                    v-model="form.description"
                    class="input-base mt-1 block w-full resize-none"
                    rows="2"
                    placeholder="vd: Viết review đầu tiên của bạn."
                />
                <InputError :message="form.errors.description" class="mt-1" />
            </div>

            <!-- Icon path -->
            <div>
                <InputLabel for="icon_path" value="Đường dẫn icon (SVG/PNG)" />
                <TextInput id="icon_path" v-model="form.icon_path" type="text" class="mt-1 block w-full"
                           placeholder="vd: /images/badges/first-review.svg" />
                <InputError :message="form.errors.icon_path" class="mt-1" />
            </div>

            <!-- Tier -->
            <div>
                <InputLabel for="tier" value="Cấp độ (Tier)" />
                <select id="tier" v-model="form.tier" class="input-base mt-1 block w-full">
                    <option value="WOOD">WOOD (+3 điểm)</option>
                    <option value="IRON">IRON (+6 điểm)</option>
                    <option value="BRONZE">BRONZE (+10 điểm)</option>
                    <option value="SILVER">SILVER (+18 điểm)</option>
                    <option value="GOLD">GOLD (+32 điểm)</option>
                    <option value="PLATINUM">PLATINUM (+50 điểm)</option>
                    <option value="DIAMOND">DIAMOND (+80 điểm)</option>
                </select>
                <InputError :message="form.errors.tier" class="mt-1" />
            </div>

            <!-- ─── Condition section ─────────────────────────── -->
            <div class="border border-[var(--color-border)] rounded-lg p-4 space-y-4">
                <p class="text-sm font-semibold text-[var(--color-text-primary)]">
                    Điều kiện trao tự động
                </p>

                <!-- Condition type -->
                <div>
                    <InputLabel for="condition_type" value="Loại điều kiện" />
                    <select id="condition_type" v-model="form.condition_type" class="input-base mt-1 block w-full">
                        <option
                            v-for="(label, key) in conditionTypes"
                            :key="key"
                            :value="key"
                        >
                            {{ label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.condition_type" class="mt-1" />
                    <p v-if="form.condition_type === 'manual'" class="text-xs text-[var(--color-text-muted)] mt-1">
                        Huy hiệu sẽ không tự động trao — admin cấp thủ công.
                    </p>
                </div>

                <!-- Condition value (only when needed) -->
                <div v-if="needsValue">
                    <InputLabel for="condition_value" :value="conditionValueLabel" />
                    <TextInput
                        id="condition_value"
                        v-model.number="form.condition_value"
                        type="number"
                        min="0"
                        max="99999"
                        class="mt-1 block w-full"
                        :placeholder="conditionValuePlaceholder"
                    />
                    <InputError :message="form.errors.condition_value" class="mt-1" />
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-2">
                <Button type="button" variant="ghost" @click="router.get(route('admin.badges.index'))">
                    Huỷ
                </Button>
                <Button type="submit" variant="primary" :disabled="form.processing">
                    {{ isEdit ? 'Lưu thay đổi' : 'Tạo huy hiệu' }}
                </Button>
            </div>
        </form>
    </div>
</template>
