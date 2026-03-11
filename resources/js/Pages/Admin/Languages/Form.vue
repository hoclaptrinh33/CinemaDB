<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Button from '@/Components/UI/Button.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    language: { type: Object, default: null },
});

const isEdit = !!props.language;

const form = useForm({
    iso_code:      props.language?.iso_code      ?? '',
    language_name: props.language?.language_name ?? '',
});

function submit() {
    if (isEdit) {
        form.patch(route('admin.languages.update', props.language.language_id));
    } else {
        form.post(route('admin.languages.store'));
    }
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">
                {{ isEdit ? `Sửa: ${language.language_name}` : 'Thêm ngôn ngữ' }}
            </h1>
            <Link :href="route('admin.languages.index')">
                <Button variant="ghost" size="sm">← Quay lại</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-5 max-w-md">
            <TextInput
                v-model="form.iso_code"
                label="ISO Code"
                placeholder="VD: vi, en, ko, ja, zh"
                required
                :error="form.errors.iso_code"
            />
            <TextInput
                v-model="form.language_name"
                label="Tên ngôn ngữ"
                placeholder="VD: Tiếng Việt"
                required
                :error="form.errors.language_name"
            />

            <div class="flex items-center gap-3 pt-2 border-t border-[var(--color-border)]">
                <Button type="submit" variant="primary" :loading="form.processing">
                    {{ isEdit ? 'Lưu thay đổi' : 'Thêm ngôn ngữ' }}
                </Button>
                <Link :href="route('admin.languages.index')">
                    <Button type="button" variant="ghost">Huỷ</Button>
                </Link>
            </div>
        </form>
    </div>
</template>
