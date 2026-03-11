<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative min-h-screen flex flex-col items-center justify-center bg-[var(--color-bg-base)] overflow-hidden">
        <!-- Subtle background grid -->
        <div class="absolute inset-0 pointer-events-none" style="background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px); background-size: 40px 40px;"></div>

        <!-- Red accent glow top -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] rounded-full pointer-events-none" style="background: radial-gradient(ellipse at center, rgba(229,9,20,0.12) 0%, transparent 70%);"></div>

        <!-- Logo -->
        <Link href="/" class="relative z-10 flex items-center gap-3 mb-8 group">
            <div class="w-10 h-10 rounded-xl bg-[var(--color-accent)] flex items-center justify-center shadow-lg">
                <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375Z" />
                    <path fill-rule="evenodd" d="m3.087 9 .54 9.176A3 3 0 0 0 6.62 21h10.757a3 3 0 0 0 2.995-2.824L20.913 9H3.087ZM12 10.5a.75.75 0 0 1 .75.75v4.94l1.72-1.72a.75.75 0 1 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 1 1 1.06-1.06l1.72 1.72v-4.94a.75.75 0 0 1 .75-.75Z" clip-rule="evenodd" />
                </svg>
            </div>
            <span class="font-display font-black text-2xl tracking-tight text-[var(--color-text-primary)] group-hover:text-[var(--color-accent)] transition-colors">
                Cinema<span class="text-[var(--color-accent)]">DB</span>
            </span>
        </Link>

        <!-- Card -->
        <div class="relative z-10 w-full max-w-md">
            <div class="bg-[var(--color-bg-surface)] border border-[var(--color-border)] rounded-2xl shadow-2xl shadow-black/60 px-8 py-8">
                <slot />
            </div>
        </div>

        <!-- Bottom tagline -->
        <p class="relative z-10 mt-8 text-xs text-[var(--color-text-muted)]">
            &copy; 2026 CinemaDB &mdash; Your universe of cinema
        </p>
    </div>
</template>
