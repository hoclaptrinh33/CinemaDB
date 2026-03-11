<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import TextInput from '@/Components/Form/TextInput.vue';
import Button from '@/Components/UI/Button.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    country: { type: Object, default: null },
});

const isEdit = !!props.country;

const form = useForm({
    iso_code:     props.country?.iso_code     ?? '',
    country_name: props.country?.country_name ?? '',
});

function submit() {
    if (isEdit) {
        form.patch(route('admin.countries.update', props.country.country_id));
    } else {
        form.post(route('admin.countries.store'));
    }
}
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">
                {{ isEdit ? `Sửa: ${country.country_name}` : 'Thêm quốc gia' }}
            </h1>
            <Link :href="route('admin.countries.index')">
                <Button variant="ghost" size="sm">← Quay lại</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-5 max-w-md">
            <TextInput
                v-model="form.iso_code"
                label="ISO Code"
                placeholder="VD: VN, US, KR"
                required
                :error="form.errors.iso_code"
                class="uppercase"
            />
            <TextInput
                v-model="form.country_name"
                label="Tên quốc gia"
                placeholder="VD: Việt Nam"
                required
                :error="form.errors.country_name"
            />

            <div class="flex items-center gap-3 pt-2 border-t border-[var(--color-border)]">
                <Button type="submit" variant="primary" :loading="form.processing">
                    {{ isEdit ? 'Lưu thay đổi' : 'Thêm quốc gia' }}
                </Button>
                <Link :href="route('admin.countries.index')">
                    <Button type="button" variant="ghost">Huỷ</Button>
                </Link>
            </div>
        </form>
    </div>
</template>
