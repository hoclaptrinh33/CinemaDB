<script setup>
import { computed, ref } from 'vue';
import { CheckCircleIcon, ExclamationCircleIcon, InformationCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    type:        { type: String, default: 'success' }, // success | error | warning | info
    message:     { type: String, default: '' },
    dismissible: { type: Boolean, default: true },
});

const visible = ref(true);

const config = computed(() => ({
    success: { icon: CheckCircleIcon,        bg: 'bg-green-950/50',  border: 'border-green-800/50',  text: 'text-green-300',   iconColor: 'text-green-400' },
    error:   { icon: ExclamationCircleIcon,  bg: 'bg-red-950/50',    border: 'border-red-800/50',    text: 'text-red-300',     iconColor: 'text-red-400' },
    warning: { icon: ExclamationCircleIcon,  bg: 'bg-amber-950/50',  border: 'border-amber-800/50',  text: 'text-amber-300',   iconColor: 'text-amber-400' },
    info:    { icon: InformationCircleIcon,  bg: 'bg-blue-950/50',   border: 'border-blue-800/50',   text: 'text-blue-300',    iconColor: 'text-blue-400' },
}[props.type]));
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200"
        leave-from-class="opacity-100 max-h-32"
        leave-to-class="opacity-0 max-h-0 overflow-hidden"
    >
        <div
            v-if="visible && (message || $slots.default)"
            :class="['flex items-start gap-3 px-4 py-3 rounded-xl border', config.bg, config.border]"
            role="alert"
        >
            <component :is="config.icon" :class="['w-5 h-5 mt-0.5 shrink-0', config.iconColor]" />
            <p :class="['flex-1 text-sm leading-relaxed', config.text]">
                <slot>{{ message }}</slot>
            </p>
            <button
                v-if="dismissible"
                @click="visible = false"
                :class="['p-0.5 rounded transition-colors hover:bg-white/10', config.text]"
            >
                <XMarkIcon class="w-4 h-4" />
            </button>
        </div>
    </Transition>
</template>
