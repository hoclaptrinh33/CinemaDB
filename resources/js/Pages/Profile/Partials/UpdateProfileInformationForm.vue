<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    username: user.username,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-[var(--color-text-primary)]">
                Thông tin tài khoản
            </h2>
            <p class="mt-1 text-sm text-[var(--color-text-secondary)]">
                Cập nhật tên hiển thị, tên người dùng và địa chỉ email.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-5"
        >
            <div>
                <label for="name" class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">
                    Tên hiển thị
                </label>
                <input
                    id="name"
                    type="text"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    class="input-base"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <label for="username" class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">
                    Tên người dùng
                </label>
                <input
                    id="username"
                    type="text"
                    v-model="form.username"
                    required
                    autocomplete="username"
                    class="input-base"
                />
                <InputError class="mt-2" :message="form.errors.username" />
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">
                    Email
                </label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="email"
                    class="input-base"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    Email của bạn chưa được xác minh.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="text-sm text-[var(--color-accent)] underline hover:opacity-80"
                    >
                        Nhấn vào đây để gửi lại email xác minh.
                    </Link>
                </p>
                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-[var(--color-success)]"
                >
                    Đã gửi liên kết xác minh mới đến địa chỉ email của bạn.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <PrimaryButton :disabled="form.processing">Lưu thay đổi</PrimaryButton>
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-[var(--color-success)]">
                        Đã lưu.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

