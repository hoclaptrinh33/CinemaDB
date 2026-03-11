<script setup>
import { computed, ref, onUnmounted } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const page = usePage();

// Determine if this is a new registration (not yet logged in) or an email change (logged in)
const isRegistrationFlow = computed(() => !page.props.auth?.user);

const userEmail = computed(() =>
    isRegistrationFlow.value
        ? page.props.flash?.pendingEmail ?? ''
        : page.props.auth?.user?.email ?? ''
);

// New-registration resend form (public route, sends email field)
const resendForm = useForm({ email: userEmail.value });

// Logged-in resend form (auth route, no fields needed)
const authResendForm = useForm({});

const countdown = ref(0);
let timer = null;

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');

const canResend = computed(() =>
    countdown.value === 0 && !resendForm.processing && !authResendForm.processing
);

const startCountdown = () => {
    countdown.value = 60;
    timer = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(timer);
            countdown.value = 0;
        }
    }, 1000);
};

const submit = () => {
    if (isRegistrationFlow.value) {
        resendForm.email = userEmail.value;
        resendForm.post(route('register.resend'), { onSuccess: startCountdown });
    } else {
        authResendForm.post(route('verification.send'), { onSuccess: startCountdown });
    }
};

onUnmounted(() => clearInterval(timer));
</script>

<template>
    <GuestLayout>
        <Head title="Xác thực Email" />

        <div class="mb-4 text-sm text-gray-600">
            <template v-if="isRegistrationFlow">
                Tài khoản của bạn đã được tạo! Vui lòng xác thực email
                <span v-if="userEmail" class="font-semibold text-gray-800">{{ userEmail }}</span>
                bằng cách nhấn vào link chúng tôi vừa gửi. Tài khoản sẽ được kích hoạt sau khi xác thực.
            </template>
            <template v-else>
                Vui lòng xác thực địa chỉ email
                <span class="font-semibold text-gray-800">{{ userEmail }}</span>
                bằng cách nhấn vào link chúng tôi vừa gửi.
            </template>
        </div>

        <div
            v-if="verificationLinkSent"
            class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-700"
        >
            Đã gửi lại link xác thực tới
            <span class="font-semibold">{{ userEmail }}</span>.
            Vui lòng kiểm tra hộp thư (kể cả thư mục spam).
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-50 cursor-not-allowed': !canResend }"
                    :disabled="!canResend"
                >
                    <span v-if="countdown > 0">Gửi lại sau {{ countdown }}s</span>
                    <span v-else>Gửi lại email xác thực</span>
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Đăng xuất
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
