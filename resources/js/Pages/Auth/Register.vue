<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const activeTooltip = ref(null);

const toggleTooltip = (key) => {
    activeTooltip.value = activeTooltip.value === key ? null : key;
};

const hints = {
    name:     'Tên sẽ hiển thị công khai trên hồ sơ của bạn. Có thể dùng tên thật hoặc biệt danh, tối đa 255 ký tự.',
    username: 'Tên người dùng dùng để đăng nhập và trong URL hồ sơ. Chỉ được dùng chữ cái, số, dấu gạch dưới (_) và gạch nối (-). Tối đa 50 ký tự.',
    email:    'Địa chỉ Gmail hoặc email hợp lệ. Bạn sẽ nhận một email xác thực sau khi đăng ký.',
    password: 'Mật khẩu tối thiểu 8 ký tự, nên kết hợp chữ hoa, chữ thường, số và ký tự đặc biệt để tăng bảo mật.',
    confirm:  'Nhập lại đúng mật khẩu bạn đã điền ở trên để xác nhận.',
};
</script>

<template>
    <GuestLayout>
        <Head title="Đăng ký" />

        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-display font-bold text-[var(--color-text-primary)] tracking-tight">
                Tạo tài khoản
            </h1>
            <p class="mt-1 text-sm text-[var(--color-text-secondary)]">
                Tham gia cộng đồng điện ảnh lớn nhất Việt Nam.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Name -->
            <div>
                <label for="name" class="flex items-center gap-1.5 text-sm font-medium text-[var(--color-text-secondary)] mb-1.5">
                    Tên hiển thị
                    <span class="relative inline-flex">
                        <button type="button" @click="toggleTooltip('name')" @mouseenter="activeTooltip = 'name'" @mouseleave="activeTooltip = null"
                            class="w-4 h-4 rounded-full bg-[var(--color-bg-elevated)] border border-[var(--color-border)] text-[var(--color-text-muted)] text-[10px] font-bold leading-none flex items-center justify-center hover:border-[var(--color-accent)] hover:text-[var(--color-accent)] transition-colors focus:outline-none">
                            !
                        </button>
                        <transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                            <div v-if="activeTooltip === 'name'" class="absolute left-6 top-0 z-20 w-64 rounded-lg bg-[var(--color-bg-elevated)] border border-[var(--color-border)] shadow-lg px-3 py-2 text-xs text-[var(--color-text-secondary)] leading-relaxed">
                                {{ hints.name }}
                            </div>
                        </transition>
                    </span>
                </label>
                <input id="name" type="text" v-model="form.name" required autofocus autocomplete="name" class="input-base" />
                <InputError class="mt-1.5" :message="form.errors.name" />
            </div>

            <!-- Username -->
            <div>
                <label for="username" class="flex items-center gap-1.5 text-sm font-medium text-[var(--color-text-secondary)] mb-1.5">
                    Tên người dùng
                    <span class="relative inline-flex">
                        <button type="button" @click="toggleTooltip('username')" @mouseenter="activeTooltip = 'username'" @mouseleave="activeTooltip = null"
                            class="w-4 h-4 rounded-full bg-[var(--color-bg-elevated)] border border-[var(--color-border)] text-[var(--color-text-muted)] text-[10px] font-bold leading-none flex items-center justify-center hover:border-[var(--color-accent)] hover:text-[var(--color-accent)] transition-colors focus:outline-none">
                            !
                        </button>
                        <transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                            <div v-if="activeTooltip === 'username'" class="absolute left-6 top-0 z-20 w-64 rounded-lg bg-[var(--color-bg-elevated)] border border-[var(--color-border)] shadow-lg px-3 py-2 text-xs text-[var(--color-text-secondary)] leading-relaxed">
                                {{ hints.username }}
                            </div>
                        </transition>
                    </span>
                </label>
                <input id="username" type="text" v-model="form.username" required autocomplete="username" class="input-base" />
                <InputError class="mt-1.5" :message="form.errors.username" />
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="flex items-center gap-1.5 text-sm font-medium text-[var(--color-text-secondary)] mb-1.5">
                    Email
                    <span class="relative inline-flex">
                        <button type="button" @click="toggleTooltip('email')" @mouseenter="activeTooltip = 'email'" @mouseleave="activeTooltip = null"
                            class="w-4 h-4 rounded-full bg-[var(--color-bg-elevated)] border border-[var(--color-border)] text-[var(--color-text-muted)] text-[10px] font-bold leading-none flex items-center justify-center hover:border-[var(--color-accent)] hover:text-[var(--color-accent)] transition-colors focus:outline-none">
                            !
                        </button>
                        <transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                            <div v-if="activeTooltip === 'email'" class="absolute left-6 top-0 z-20 w-64 rounded-lg bg-[var(--color-bg-elevated)] border border-[var(--color-border)] shadow-lg px-3 py-2 text-xs text-[var(--color-text-secondary)] leading-relaxed">
                                {{ hints.email }}
                            </div>
                        </transition>
                    </span>
                </label>
                <input id="email" type="email" v-model="form.email" required autocomplete="email" class="input-base" />
                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="flex items-center gap-1.5 text-sm font-medium text-[var(--color-text-secondary)] mb-1.5">
                    Mật khẩu
                    <span class="relative inline-flex">
                        <button type="button" @click="toggleTooltip('password')" @mouseenter="activeTooltip = 'password'" @mouseleave="activeTooltip = null"
                            class="w-4 h-4 rounded-full bg-[var(--color-bg-elevated)] border border-[var(--color-border)] text-[var(--color-text-muted)] text-[10px] font-bold leading-none flex items-center justify-center hover:border-[var(--color-accent)] hover:text-[var(--color-accent)] transition-colors focus:outline-none">
                            !
                        </button>
                        <transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                            <div v-if="activeTooltip === 'password'" class="absolute left-6 top-0 z-20 w-64 rounded-lg bg-[var(--color-bg-elevated)] border border-[var(--color-border)] shadow-lg px-3 py-2 text-xs text-[var(--color-text-secondary)] leading-relaxed">
                                {{ hints.password }}
                            </div>
                        </transition>
                    </span>
                </label>
                <input id="password" type="password" v-model="form.password" required autocomplete="new-password" class="input-base" />
                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="flex items-center gap-1.5 text-sm font-medium text-[var(--color-text-secondary)] mb-1.5">
                    Xác nhận mật khẩu
                    <span class="relative inline-flex">
                        <button type="button" @click="toggleTooltip('confirm')" @mouseenter="activeTooltip = 'confirm'" @mouseleave="activeTooltip = null"
                            class="w-4 h-4 rounded-full bg-[var(--color-bg-elevated)] border border-[var(--color-border)] text-[var(--color-text-muted)] text-[10px] font-bold leading-none flex items-center justify-center hover:border-[var(--color-accent)] hover:text-[var(--color-accent)] transition-colors focus:outline-none">
                            !
                        </button>
                        <transition enter-active-class="transition ease-out duration-150" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                            <div v-if="activeTooltip === 'confirm'" class="absolute left-6 top-0 z-20 w-64 rounded-lg bg-[var(--color-bg-elevated)] border border-[var(--color-border)] shadow-lg px-3 py-2 text-xs text-[var(--color-text-secondary)] leading-relaxed">
                                {{ hints.confirm }}
                            </div>
                        </transition>
                    </span>
                </label>
                <input id="password_confirmation" type="password" v-model="form.password_confirmation" required autocomplete="new-password" class="input-base" />
                <InputError class="mt-1.5" :message="form.errors.password_confirmation" />
            </div>

            <!-- Submit -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-2.5 px-4 rounded-xl font-semibold text-sm text-white transition-all mt-2"
                :class="form.processing ? 'opacity-50 cursor-not-allowed bg-[var(--color-accent)]' : 'bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] shadow-lg shadow-red-900/30 hover:shadow-red-900/50'"
            >
                <span v-if="form.processing" class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Đang tạo tài khoản...
                </span>
                <span v-else>Tạo tài khoản</span>
            </button>

            <!-- Divider -->
            <div class="relative flex items-center gap-3 py-1">
                <div class="flex-1 h-px bg-[var(--color-border)]"></div>
                <span class="text-xs text-[var(--color-text-muted)] uppercase tracking-wider">hoặc</span>
                <div class="flex-1 h-px bg-[var(--color-border)]"></div>
            </div>

            <!-- Google -->
            <a
                :href="route('auth.google')"
                class="flex w-full items-center justify-center gap-3 py-2.5 px-4 rounded-xl border border-[var(--color-border)] bg-[var(--color-bg-elevated)] text-sm font-medium text-[var(--color-text-primary)] hover:border-[var(--color-border-light)] hover:bg-[var(--color-bg-overlay)] transition-all"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Đăng ký bằng Google
            </a>
        </form>

        <!-- Footer link -->
        <p class="mt-6 text-center text-sm text-[var(--color-text-muted)]">
            Đã có tài khoản?
            <Link :href="route('login')" class="text-[var(--color-text-secondary)] font-medium hover:text-[var(--color-accent)] transition-colors">
                Đăng nhập
            </Link>
        </p>
    </GuestLayout>
</template>

