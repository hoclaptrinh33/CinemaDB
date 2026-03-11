<script setup>
import Modal from './Modal.vue';
import Button from './Button.vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({
    show:    { type: Boolean, default: false },
    title:   { type: String,  default: 'Xác nhận' },
    message: { type: String,  default: 'Bạn có chắc chắn muốn thực hiện thao tác này?' },
    confirmLabel: { type: String, default: 'Xác nhận' },
    cancelLabel:  { type: String, default: 'Huỷ' },
    loading: { type: Boolean, default: false },
    danger:  { type: Boolean, default: true },
});

const emit = defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" :title="title" max-width="sm" @close="emit('close')">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-red-950/60 flex items-center justify-center shrink-0 mt-0.5">
                <ExclamationTriangleIcon class="w-5 h-5 text-red-400" />
            </div>
            <p class="text-sm text-[var(--color-text-secondary)] leading-relaxed mt-2">{{ message }}</p>
        </div>

        <template #footer>
            <Button variant="ghost" @click="emit('close')">{{ cancelLabel }}</Button>
            <Button :variant="danger ? 'danger' : 'primary'" :loading="loading" @click="emit('confirm')">
                {{ confirmLabel }}
            </Button>
        </template>
    </Modal>
</template>
