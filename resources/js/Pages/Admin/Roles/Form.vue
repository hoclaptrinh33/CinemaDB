<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Button from '@/Components/UI/Button.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    role: { type: Object, default: null },
});

const isEdit = !!props.role;

const form = useForm({
    role_name: props.role?.role_name ?? '',
});

function submit() {
    if (isEdit) {
        form.patch(route('admin.roles.update', props.role.role_id));
    } else {
        form.post(route('admin.roles.store'));
    }
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">
                {{ isEdit ? `Sửa: ${role.role_name}` : 'Thêm vai trò phim' }}
            </h1>
            <Link :href="route('admin.roles.index')">
                <Button variant="ghost" size="sm">← Quay lại</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-5 max-w-md">
            <TextInput
                v-model="form.role_name"
                label="Tên vai trò"
                placeholder="VD: Actor, Director, Producer"
                required
                :error="form.errors.role_name"
            />

            <div class="flex items-center gap-3 pt-2 border-t border-[var(--color-border)]">
                <Button type="submit" variant="primary" :loading="form.processing">
                    {{ isEdit ? 'Lưu thay đổi' : 'Thêm vai trò' }}
                </Button>
                <Link :href="route('admin.roles.index')">
                    <Button type="button" variant="ghost">Huỷ</Button>
                </Link>
            </div>
        </form>
    </div>
</template>
