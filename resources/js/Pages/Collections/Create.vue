<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from 'vue-i18n';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const { t } = useI18n();

const form = useForm({
    name:        '',
    description: '',
    visibility:  'PUBLIC',
});

function submit() {
    form.post(route('collections.store'));
}
</script>

<template>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
        <h1 class="font-display font-black text-2xl text-[var(--color-text-primary)] mb-6">
            {{ t('collections.createPageTitle') }}
        </h1>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Name -->
            <div>
                <label class="block text-sm font-semibold text-[var(--color-text-primary)] mb-1">
                    {{ t('collections.nameLabel') }} <span class="text-red-400">*</span>
                </label>
                <input
                    v-model="form.name"
                    type="text"
                    maxlength="200"
                    :placeholder="t('collections.namePlaceholder')"
                    class="w-full px-4 py-2.5 rounded-lg bg-[var(--color-bg-elevated)] border border-[var(--color-border)] text-[var(--color-text-primary)] focus:border-[var(--color-accent)] focus:outline-none text-sm"
                    required
                />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-semibold text-[var(--color-text-primary)] mb-1">{{ t('collections.descLabel') }}</label>
                <textarea
                    v-model="form.description"
                    rows="3"
                    maxlength="1000"
                    :placeholder="t('collections.descPlaceholder')"
                    class="w-full px-4 py-2.5 rounded-lg bg-[var(--color-bg-elevated)] border border-[var(--color-border)] text-[var(--color-text-primary)] focus:border-[var(--color-accent)] focus:outline-none text-sm resize-none"
                />
            </div>

            <!-- Visibility -->
            <div>
                <label class="block text-sm font-semibold text-[var(--color-text-primary)] mb-2">{{ t('collections.visibilityLabel') }}</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.visibility" type="radio" value="PUBLIC" class="accent-[var(--color-accent)]" />
                        <span class="text-sm text-[var(--color-text-secondary)]">{{ t('collections.publicOption') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.visibility" type="radio" value="PRIVATE" class="accent-[var(--color-accent)]" />
                        <span class="text-sm text-[var(--color-text-secondary)]">{{ t('collections.privateOption') }}</span>
                    </label>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing || !form.name.trim()"
                    class="px-6 py-2.5 rounded-lg bg-[var(--color-accent)] text-white text-sm font-semibold disabled:opacity-50 hover:opacity-90 transition-opacity"
                >
                    {{ form.processing ? t('collections.creating') : t('collections.create') }}
                </button>
                <a
                    href="javascript:history.back()"
                    class="px-6 py-2.5 rounded-lg border border-[var(--color-border)] text-[var(--color-text-secondary)] text-sm font-semibold hover:border-[var(--color-accent)]/60 transition-colors"
                >
                    {{ t('common.cancel') }}
                </a>
            </div>
        </form>
    </div>
</template>
