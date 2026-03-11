<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Textarea  from '@/Components/Form/Textarea.vue';
import FileUpload from '@/Components/Form/FileUpload.vue';
import Button    from '@/Components/UI/Button.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    studio: { type: Object, default: null },
});

const isEdit = !!props.studio;

const form = useForm({
    studio_name:  props.studio?.studio_name  ?? '',
    country:      props.studio?.country      ?? '',
    website:      props.studio?.website      ?? '',
    description:  props.studio?.description  ?? '',
    logo:         null,
});

function submit() {
    if (isEdit) {
        form.post(route('admin.studios.update', props.studio.studio_id), { method: 'patch', forceFormData: true });
    } else {
        form.post(route('admin.studios.store'), { forceFormData: true });
    }
}
</script>

<template>
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">{{ isEdit ? `Sửa: ${studio.studio_name}` : 'Thêm Studio' }}</h1>
        <Link :href="route('admin.studios.index')"><Button variant="ghost" size="sm">← Quay lại</Button></Link>
    </div>

    <form @submit.prevent="submit" class="space-y-6 max-w-xl">
        <div class="flex gap-6 items-start">
            <FileUpload v-model="form.logo" label="Logo" ratio="square" :hint="isEdit ? 'Để trống giữ nguyên' : ''" :error="form.errors.logo" />
            <div class="flex-1 space-y-4">
                <TextInput v-model="form.studio_name" label="Tên studio" placeholder="VD: Warner Bros." required :error="form.errors.studio_name" />
                <TextInput v-model="form.country" label="Quốc gia" placeholder="VD: United States" :error="form.errors.country" />
                <TextInput v-model="form.website" label="Website" placeholder="https://..." :error="form.errors.website" />
            </div>
        </div>
        <Textarea v-model="form.description" label="Giới thiệu" :rows="4" :error="form.errors.description" />
        <div class="flex items-center gap-3 pt-2 border-t border-[var(--color-border)]">
            <Button type="submit" variant="primary" :loading="form.processing">{{ isEdit ? 'Lưu thay đổi' : 'Thêm Studio' }}</Button>
            <Link :href="route('admin.studios.index')"><Button type="button" variant="ghost">Huỷ</Button></Link>
        </div>
    </form>
</template>
