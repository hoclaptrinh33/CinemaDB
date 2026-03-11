<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Textarea  from '@/Components/Form/Textarea.vue';
import Select    from '@/Components/Form/Select.vue';
import DateInput from '@/Components/Form/DateInput.vue';
import FileUpload from '@/Components/Form/FileUpload.vue';
import Button    from '@/Components/UI/Button.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    title:     { type: Object, default: null },    // null when creating
    languages: { type: Array, default: () => [] },
    genres:    { type: Array, default: () => [] },
});

const isEdit = !!props.title;

const form = useForm({
    title_name:     props.title?.title_name     ?? '',
    original_title: props.title?.original_title ?? '',
    title_type:     props.title?.title_type     ?? 'MOVIE',
    release_date:   props.title?.release_date   ?? '',
    runtime_mins:   props.title?.runtime_mins   ?? '',
    status:         props.title?.status         ?? 'Released',
    visibility:     props.title?.visibility     ?? 'PUBLIC',
    language_id:    props.title?.language?.id   ?? '',
    description:    props.title?.description    ?? '',
    trailer_url:    props.title?.trailer_url    ?? '',
    budget:         props.title?.budget         ?? '',
    revenue:        props.title?.revenue        ?? '',
    poster:         null,   // File upload
    backdrop:       null,
    genre_ids:      props.title?.genres?.map(g => g.id) ?? [],
});

function submit() {
    if (isEdit) {
        form.post(route('admin.titles.update', props.title.id), {
            method: 'patch',
            forceFormData: true,
        });
    } else {
        form.post(route('admin.titles.store'), { forceFormData: true });
    }
}

const typeOptions = [
    { value: 'MOVIE',   label: 'Phim lẻ' },
    { value: 'SERIES',  label: 'Series' },
    { value: 'EPISODE', label: 'Tập phim' },
];
const statusOptions = [
    { value: 'Released',        label: 'Đã phát hành' },
    { value: 'Rumored',         label: 'Sắp chiếu' },
    { value: 'Post Production', label: 'Đang sản xuất' },
    { value: 'Canceled',        label: 'Huỷ bỏ' },
];
const visibilityOptions = [
    { value: 'PUBLIC', label: 'Hiện' },
    { value: 'HIDDEN', label: 'Ẩn' },
];

function toggleGenre(id) {
    const idx = form.genre_ids.indexOf(id);
    if (idx === -1) form.genre_ids.push(id);
    else form.genre_ids.splice(idx, 1);
}
</script>

<template>
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">{{ isEdit ? `Sửa: ${title.title_name}` : 'Thêm Title mới' }}</h1>
        <Link :href="route('admin.titles.index')"><Button variant="ghost" size="sm">← Quay lại</Button></Link>
    </div>

    <form @submit.prevent="submit" class="space-y-6 max-w-5xl">
        <!-- Row: Poster + Backdrop + Fields -->
        <div class="grid md:grid-cols-[160px_200px_1fr] gap-6 items-start">
            <!-- Poster -->
            <FileUpload
                v-model="form.poster"
                label="Poster"
                ratio="poster"
                :hint="isEdit ? 'Để trống giữ nguyên ảnh cũ' : 'Tỷ lệ 2:3'"
                :error="form.errors.poster"
            />

            <!-- Backdrop -->
            <FileUpload
                v-model="form.backdrop"
                label="Backdrop"
                ratio="backdrop"
                :hint="isEdit ? 'Để trống giữ nguyên' : 'Tỷ lệ 16:9'"
                :error="form.errors.backdrop"
            />

            <!-- Core fields -->
            <div class="space-y-4">
                <TextInput
                    v-model="form.title_name"
                    label="Tên title"
                    placeholder="VD: Inception"
                    required
                    :error="form.errors.title_name"
                />
                <TextInput
                    v-model="form.original_title"
                    label="Tên gốc"
                    placeholder="Tên tiếng Anh / ngôn ngữ gốc"
                    :error="form.errors.original_title"
                />
                <div class="grid grid-cols-2 gap-4">
                    <Select
                        v-model="form.title_type"
                        label="Loại"
                        :options="typeOptions"
                        required
                        :error="form.errors.title_type"
                    />
                    <DateInput
                        v-model="form.release_date"
                        label="Ngày phát hành"
                        :error="form.errors.release_date"
                    />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <TextInput
                        v-model="form.runtime_mins"
                        label="Thời lượng (phút)"
                        type="number"
                        placeholder="120"
                        :error="form.errors.runtime_mins"
                    />
                    <Select
                        v-model="form.language_id"
                        label="Ngôn ngữ"
                        :options="languages.map(l => ({ value: l.id, label: l.language_name }))"
                        :error="form.errors.language_id"
                    />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <Select
                        v-model="form.status"
                        label="Trạng thái"
                        :options="statusOptions"
                        :error="form.errors.status"
                    />
                    <Select
                        v-model="form.visibility"
                        label="Visibility"
                        :options="visibilityOptions"
                        :error="form.errors.visibility"
                    />
                </div>
            </div>
        </div>

        <!-- Description -->
        <Textarea
            v-model="form.description"
            label="Mô tả"
            placeholder="Tóm tắt nội dung phim..."
            :rows="4"
            :error="form.errors.description"
        />

        <!-- Trailer + Budget/Revenue -->
        <div class="grid sm:grid-cols-3 gap-4">
            <TextInput
                v-model="form.trailer_url"
                label="Trailer URL"
                placeholder="https://youtube.com/..."
                :error="form.errors.trailer_url"
                class="sm:col-span-1"
            />
            <TextInput
                v-model="form.budget"
                label="Kinh phí (USD)"
                type="number"
                placeholder="0"
                :error="form.errors.budget"
            />
            <TextInput
                v-model="form.revenue"
                label="Doanh thu (USD)"
                type="number"
                placeholder="0"
                :error="form.errors.revenue"
            />
        </div>

        <!-- Genres -->
        <div v-if="genres.length" class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-wider text-[var(--color-text-muted)] font-display">
                Thể loại
            </p>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="g in genres"
                    :key="g.id"
                    type="button"
                    :class="[
                        'badge cursor-pointer transition-all',
                        form.genre_ids.includes(g.id)
                            ? 'bg-[var(--color-accent-muted)] !text-[var(--color-accent)] border border-[var(--color-accent)]/50'
                            : 'bg-[var(--color-bg-elevated)] text-[var(--color-text-secondary)] border border-[var(--color-border)] hover:border-[var(--color-text-muted)]',
                    ]"
                    @click="toggleGenre(g.id)"
                >
                    {{ g.genre_name }}
                </button>
            </div>
        </div>

        <!-- Submit row -->
        <div class="flex items-center gap-3 pt-2 border-t border-[var(--color-border)]">
            <Button type="submit" variant="primary" :loading="form.processing">
                {{ isEdit ? 'Lưu thay đổi' : 'Thêm Title' }}
            </Button>
            <Link :href="route('admin.titles.index')">
                <Button type="button" variant="ghost">Huỷ</Button>
            </Link>
        </div>
    </form>
</template>
