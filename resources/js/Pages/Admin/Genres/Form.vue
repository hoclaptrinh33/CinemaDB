<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Button from '@/Components/UI/Button.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    genre: { type: Object, default: null },
});

const isEdit = !!props.genre;

const form = useForm({
    tmdb_id:      props.genre?.tmdb_id      ?? '',
    genre_name:   props.genre?.genre_name   ?? '',
    genre_name_vi: props.genre?.genre_name_vi ?? '',
});

function submit() {
    if (isEdit) {
        form.patch(route('admin.genres.update', props.genre.genre_id));
    } else {
        form.post(route('admin.genres.store'));
    }
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">
                {{ isEdit ? `Sửa: ${genre.genre_name}` : 'Thêm thể loại' }}
            </h1>
            <Link :href="route('admin.genres.index')">
                <Button variant="ghost" size="sm">← Quay lại</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-5 max-w-md">
            <TextInput
                v-model="form.tmdb_id"
                label="TMDB ID (tuỳ chọn)"
                placeholder="VD: 28"
                type="number"
                :error="form.errors.tmdb_id"
            />
            <TextInput
                v-model="form.genre_name"
                label="Tên thể loại (EN)"
                placeholder="VD: Action"
                required
                :error="form.errors.genre_name"
            />
            <TextInput
                v-model="form.genre_name_vi"
                label="Tên thể loại (VI) — tuỳ chọn"
                placeholder="VD: Hành động"
                :error="form.errors.genre_name_vi"
            />

            <div class="flex items-center gap-3 pt-2 border-t border-[var(--color-border)]">
                <Button type="submit" variant="primary" :loading="form.processing">
                    {{ isEdit ? 'Lưu thay đổi' : 'Thêm thể loại' }}
                </Button>
                <Link :href="route('admin.genres.index')">
                    <Button type="button" variant="ghost">Huỷ</Button>
                </Link>
            </div>
        </form>
    </div>
</template>
