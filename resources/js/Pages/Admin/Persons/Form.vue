<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Textarea  from '@/Components/Form/Textarea.vue';
import DateInput from '@/Components/Form/DateInput.vue';
import FileUpload from '@/Components/Form/FileUpload.vue';
import Button    from '@/Components/UI/Button.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    person: { type: Object, default: null },
});

const isEdit = !!props.person;

const form = useForm({
    full_name:    props.person?.full_name    ?? '',
    birth_name:   props.person?.birth_name   ?? '',
    date_of_birth: props.person?.date_of_birth ?? '',
    nationality:  props.person?.nationality  ?? '',
    biography:    props.person?.biography    ?? '',
    biography_vi: props.person?.biography_vi ?? '',
    photo:        null,
});

function submit() {
    if (isEdit) {
        form.post(route('admin.persons.update', props.person.person_id), {
            method: 'patch', forceFormData: true,
        });
    } else {
        form.post(route('admin.persons.store'), { forceFormData: true });
    }
}
</script>

<template>
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">{{ isEdit ? `Sửa: ${person.full_name}` : 'Thêm Person' }}</h1>
        <Link :href="route('admin.persons.index')"><Button variant="ghost" size="sm">← Quay lại</Button></Link>
    </div>

    <form @submit.prevent="submit" class="space-y-6 max-w-2xl">
        <div class="flex gap-6 items-start">
            <!-- Photo -->
            <FileUpload
                v-model="form.photo"
                label="Ảnh đại diện"
                ratio="poster"
                :hint="isEdit ? 'Để trống giữ nguyên' : ''"
                :error="form.errors.photo"
            />

            <!-- Fields -->
            <div class="flex-1 space-y-4">
                <TextInput
                    v-model="form.full_name"
                    label="Họ và tên"
                    placeholder="VD: Christopher Nolan"
                    required
                    :error="form.errors.full_name"
                />
                <TextInput
                    v-model="form.birth_name"
                    label="Tên khai sinh"
                    placeholder="Nếu khác tên nghệ danh"
                    :error="form.errors.birth_name"
                />
                <DateInput
                    v-model="form.date_of_birth"
                    label="Ngày sinh"
                    :error="form.errors.date_of_birth"
                />
                <TextInput
                    v-model="form.nationality"
                    label="Quốc tịch"
                    placeholder="VD: British"
                    :error="form.errors.nationality"
                />
            </div>
        </div>

        <Textarea
            v-model="form.biography"
            label="Tiểu sử (Tiếng Anh)"
            placeholder="Tiểu sử ngắn..."
            :rows="5"
            :error="form.errors.biography"
        />

        <Textarea
            v-model="form.biography_vi"
            label="Tiểu sử (Tiếng Việt)"
            placeholder="Tiểu sử bằng tiếng Việt (nếu có)..."
            :rows="5"
            :error="form.errors.biography_vi"
        />

        <div class="flex items-center gap-3 pt-2 border-t border-[var(--color-border)]">
            <Button type="submit" variant="primary" :loading="form.processing">
                {{ isEdit ? 'Lưu thay đổi' : 'Thêm Person' }}
            </Button>
            <Link :href="route('admin.persons.index')">
                <Button type="button" variant="ghost">Huỷ</Button>
            </Link>
        </div>
    </form>
</template>
