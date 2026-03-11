<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Button from '@/Components/UI/Button.vue';

defineOptions({ layout: AdminLayout });

const activeTab = ref('single');

const singleForm   = useForm({ mode: 'single',   type: 'movie', id: '',   pages: null });
const discoverForm = useForm({ mode: 'discover',  type: 'both',  id: null, pages: 3 });

function submitSingle()   { singleForm.post(route('admin.tmdb-import.store')); }
function submitDiscover() { discoverForm.post(route('admin.tmdb-import.store')); }

const singleTypeOptions   = [{ value: 'movie', label: '🎬 Movie' }, { value: 'tv', label: '📺 TV Series' }];
const discoverTypeOptions = [{ value: 'movie', label: '🎬 Movie' }, { value: 'tv', label: '📺 TV Series' }, { value: 'both', label: '🎬📺 Cả hai' }];

const tmdbMovieUrl = computed(() =>
    singleForm.id
        ? `https://www.themoviedb.org/${singleForm.type === 'tv' ? 'tv' : 'movie'}/${singleForm.id}`
        : null
);

// Live log polling
const logs      = ref([]);
const totalLogs = ref(0);
const polling   = ref(null);
const logError  = ref(false);

async function fetchLogs() {
    try {
        const res = await fetch(route('admin.tmdb-import.logs'));
        if (!res.ok) throw new Error();
        const data = await res.json();
        logs.value      = data.logs;
        totalLogs.value = data.total;
        logError.value  = false;
    } catch {
        logError.value = true;
    }
}

onMounted(() => { fetchLogs(); polling.value = setInterval(fetchLogs, 3000); });
onUnmounted(() => clearInterval(polling.value));

const statusConfig = {
    pending:    { label: 'Chờ',        dot: 'bg-yellow-400 animate-pulse', text: 'text-yellow-400', bg: 'bg-yellow-900/20' },
    processing: { label: 'Đang xử lý', dot: 'bg-blue-400 animate-ping',    text: 'text-blue-400',   bg: 'bg-blue-900/20' },
    done:       { label: 'Xong',       dot: 'bg-green-400',                 text: 'text-green-400',  bg: 'bg-green-900/10' },
    failed:     { label: 'Lỗi',        dot: 'bg-red-400',                   text: 'text-red-400',    bg: 'bg-red-900/20' },
};

function timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)   return `${diff}s trước`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m trước`;
    return `${Math.floor(diff / 3600)}h trước`;
}

const pendingCount  = computed(() => logs.value.filter(l => l.status === 'pending' || l.status === 'processing').length);
const doneCount     = computed(() => logs.value.filter(l => l.status === 'done').length);
const failedCount   = computed(() => logs.value.filter(l => l.status === 'failed').length);
const hiddenCount   = computed(() => Math.max(0, totalLogs.value - logs.value.length));
</script>

<template>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">

        <!-- LEFT: Form -->
        <div class="space-y-5">
            <div>
                <h1 class="font-display font-bold text-2xl text-[var(--color-text-primary)]">TMDB Import</h1>
                <p class="text-[var(--color-text-muted)] text-sm mt-1">Nhập phim từ The Movie Database — chạy ngầm qua queue worker.</p>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 p-1 bg-[var(--color-bg-surface)] rounded-xl border border-[var(--color-border)] w-fit">
                <button @click="activeTab = 'single'"
                    :class="['px-4 py-2 rounded-lg text-sm font-medium transition-colors', activeTab === 'single' ? 'bg-[var(--color-accent)] text-white shadow' : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]']">
                    Nhập theo ID
                </button>
                <button @click="activeTab = 'discover'"
                    :class="['px-4 py-2 rounded-lg text-sm font-medium transition-colors', activeTab === 'discover' ? 'bg-[var(--color-accent)] text-white shadow' : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]']">
                    Khám phá tự động
                </button>
            </div>

            <!-- Single ID form -->
            <div v-if="activeTab === 'single'" class="card p-6 space-y-5">
                <div>
                    <h2 class="font-display font-semibold text-base text-[var(--color-text-primary)] mb-1">Nhập 1 phim theo TMDB ID</h2>
                    <p class="text-[var(--color-text-muted)] text-xs">
                        Lấy ID từ URL: <code class="bg-[var(--color-bg-base)] px-1 rounded">themoviedb.org/movie/<strong>550</strong></code>
                    </p>
                </div>
                <form @submit.prevent="submitSingle" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--color-text-muted)] mb-2">Loại</label>
                        <div class="flex gap-2">
                            <label v-for="opt in singleTypeOptions" :key="opt.value"
                                :class="['flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border cursor-pointer text-sm font-medium transition-colors',
                                    singleForm.type === opt.value ? 'bg-[var(--color-accent)]/15 border-[var(--color-accent)] text-[var(--color-accent)]' : 'border-[var(--color-border)] text-[var(--color-text-muted)] hover:border-[var(--color-text-muted)]']">
                                <input type="radio" v-model="singleForm.type" :value="opt.value" class="sr-only" />
                                {{ opt.label }}
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--color-text-muted)] mb-2">TMDB ID</label>
                        <input v-model="singleForm.id" type="number" min="1" placeholder="Ví dụ: 550"
                            class="w-full bg-[var(--color-bg-base)] border border-[var(--color-border)] rounded-lg px-4 py-2.5 text-sm text-[var(--color-text-primary)] placeholder:text-[var(--color-text-muted)] focus:outline-none focus:border-[var(--color-accent)] transition-colors"
                            required />
                        <p v-if="singleForm.errors.id" class="text-red-400 text-xs mt-1">{{ singleForm.errors.id }}</p>
                        <a v-if="tmdbMovieUrl" :href="tmdbMovieUrl" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 mt-1.5 text-xs text-[var(--color-accent)] hover:underline">
                            Xem trên TMDB →
                        </a>
                    </div>
                    <div class="bg-[var(--color-bg-base)] rounded-lg p-3">
                        <p class="text-xs font-semibold text-[var(--color-text-muted)] uppercase tracking-wider mb-2">ID ví dụ</p>
                        <div class="grid grid-cols-2 gap-1.5 text-xs text-[var(--color-text-muted)]">
                            <button type="button" @click="singleForm.type = 'movie'; singleForm.id = 550"   class="text-left px-2 py-1.5 rounded hover:bg-[var(--color-bg-surface)] transition-colors">🎬 <span class="font-mono mr-1">550</span>Fight Club</button>
                            <button type="button" @click="singleForm.type = 'movie'; singleForm.id = 27205" class="text-left px-2 py-1.5 rounded hover:bg-[var(--color-bg-surface)] transition-colors">🎬 <span class="font-mono mr-1">27205</span>Inception</button>
                            <button type="button" @click="singleForm.type = 'tv';    singleForm.id = 1396"  class="text-left px-2 py-1.5 rounded hover:bg-[var(--color-bg-surface)] transition-colors">📺 <span class="font-mono mr-1">1396</span>Breaking Bad</button>
                            <button type="button" @click="singleForm.type = 'tv';    singleForm.id = 66732" class="text-left px-2 py-1.5 rounded hover:bg-[var(--color-bg-surface)] transition-colors">📺 <span class="font-mono mr-1">66732</span>Stranger Things</button>
                        </div>
                    </div>
                    <Button type="submit" variant="primary" :loading="singleForm.processing" class="w-full">Đẩy vào queue →</Button>
                </form>
            </div>

            <!-- Discover form -->
            <div v-else class="card p-6 space-y-5">
                <div>
                    <h2 class="font-display font-semibold text-base text-[var(--color-text-primary)] mb-1">Khám phá phim phổ biến</h2>
                    <p class="text-[var(--color-text-muted)] text-xs">Lấy danh sách phổ biến từ TMDB Discover API. Mỗi trang 20 kết quả.</p>
                </div>
                <form @submit.prevent="submitDiscover" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--color-text-muted)] mb-2">Loại</label>
                        <div class="flex gap-2">
                            <label v-for="opt in discoverTypeOptions" :key="opt.value"
                                :class="['flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg border cursor-pointer text-sm font-medium transition-colors',
                                    discoverForm.type === opt.value ? 'bg-[var(--color-accent)]/15 border-[var(--color-accent)] text-[var(--color-accent)]' : 'border-[var(--color-border)] text-[var(--color-text-muted)] hover:border-[var(--color-text-muted)]']">
                                <input type="radio" v-model="discoverForm.type" :value="opt.value" class="sr-only" />
                                {{ opt.label }}
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--color-text-muted)] mb-2">
                            Số trang
                            <span class="font-mono font-bold text-[var(--color-text-primary)] ml-1">{{ discoverForm.pages }}</span>
                            <span class="font-normal text-[var(--color-text-muted)]"> (~{{ discoverForm.pages * (discoverForm.type === 'both' ? 40 : 20) }} jobs)</span>
                        </label>
                        <input v-model.number="discoverForm.pages" type="range" min="1" max="10" step="1" class="w-full accent-[var(--color-accent)]" />
                        <div class="flex justify-between text-xs text-[var(--color-text-muted)] px-0.5 mt-1"><span>1</span><span>10</span></div>
                    </div>
                    <div class="flex items-start gap-2.5 bg-blue-950/30 border border-blue-900/40 rounded-lg px-4 py-3">
                        <svg class="w-4 h-4 mt-0.5 text-blue-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <p class="text-xs text-blue-300">Queue worker cần đang chạy: <code class="bg-blue-950/50 px-1 rounded font-mono">php artisan queue:work</code></p>
                    </div>
                    <Button type="submit" variant="primary" :loading="discoverForm.processing" class="w-full">Bắt đầu khám phá →</Button>
                </form>
            </div>

            <div class="flex items-center gap-2 text-xs text-[var(--color-text-muted)]">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                Scheduler tự động chạy khám phá hàng ngày lúc <strong class="text-[var(--color-text-primary)]">00:00</strong>
            </div>
        </div>

        <!-- RIGHT: Live Job Tracker -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-display font-bold text-xl text-[var(--color-text-primary)]">Tiến trình</h2>
                    <p class="text-[var(--color-text-muted)] text-xs mt-0.5">Tự động làm mới mỗi 3 giây</p>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-green-400">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse inline-block"></span>
                    LIVE
                    <span v-if="pendingCount > 0" class="ml-1 px-1.5 py-0.5 bg-blue-900/40 text-blue-300 rounded font-mono">{{ pendingCount }} đang chạy</span>
                </div>
            </div>

            <div v-if="logError" class="card p-4 text-center text-[var(--color-text-muted)] text-sm">
                Không thể tải tiến trình.
            </div>

            <div v-else-if="logs.length === 0" class="card p-12 flex flex-col items-center gap-3 text-center">
                <svg class="w-10 h-10 text-[var(--color-text-muted)]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <p class="text-[var(--color-text-muted)] text-sm">Chưa có job nào. Hãy đẩy một phim vào queue.</p>
            </div>

            <div v-else class="card overflow-hidden">
                <div class="max-h-[600px] overflow-y-auto divide-y divide-[var(--color-border)]">
                    <div v-for="log in logs" :key="log.id"
                        :class="['px-4 py-3 flex items-start gap-3 transition-colors', statusConfig[log.status]?.bg ?? '']">
                        <div class="mt-1.5 shrink-0">
                            <span :class="['inline-block w-2.5 h-2.5 rounded-full', statusConfig[log.status]?.dot ?? 'bg-gray-400']"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span :class="['text-xs font-semibold', statusConfig[log.status]?.text ?? 'text-gray-400']">
                                    {{ statusConfig[log.status]?.label ?? log.status }}
                                </span>
                                <span class="text-xs px-1.5 py-0.5 rounded bg-[var(--color-bg-elevated)] text-[var(--color-text-muted)] font-mono">
                                    {{ log.type === 'tv' ? '📺 TV' : '🎬 Movie' }}
                                </span>
                                <span class="text-xs text-[var(--color-text-muted)] font-mono">#{{ log.tmdb_id }}</span>
                            </div>
                            <p v-if="log.title_name" class="text-sm font-medium text-[var(--color-text-primary)] mt-0.5 truncate">{{ log.title_name }}</p>
                            <p v-else-if="log.status === 'pending'"    class="text-xs text-[var(--color-text-muted)] mt-0.5 italic">Đang chờ worker xử lý…</p>
                            <p v-else-if="log.status === 'processing'" class="text-xs text-blue-300 mt-0.5 italic">Đang tải từ TMDB…</p>
                            <p v-if="log.error_message" class="text-xs text-red-400 mt-1 line-clamp-2">{{ log.error_message }}</p>
                        </div>
                        <span class="text-xs text-[var(--color-text-muted)] shrink-0 mt-0.5">{{ timeAgo(log.created_at) }}</span>
                    </div>
                </div>
                <div class="px-4 py-2.5 bg-[var(--color-bg-base)] border-t border-[var(--color-border)] flex gap-4 text-xs text-[var(--color-text-muted)] flex-wrap">
                    <span>Tổng: <strong class="text-[var(--color-text-primary)]">{{ totalLogs }}</strong></span>
                    <span class="text-green-400">✓ {{ doneCount }} xong</span>
                    <span class="text-yellow-400">⏳ {{ pendingCount }} chạy</span>
                    <span class="text-red-400">✗ {{ failedCount }} lỗi</span>
                    <span v-if="hiddenCount > 0" class="text-[var(--color-text-muted)] italic">+ {{ hiddenCount }} ẩn (đã xong)</span>
                </div>
            </div>
        </div>
    </div>
</template>
