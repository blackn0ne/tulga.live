<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-svh bg-muted/30 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="relative hidden overflow-hidden lg:flex lg:min-h-svh lg:flex-col lg:justify-start lg:p-10">
            <div class="absolute inset-0 bg-zinc-950" />
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.24),_transparent_38%),radial-gradient(circle_at_bottom,_rgba(168,85,247,0.18),_transparent_32%)]"
            />

            <Link :href="route('home')" class="relative z-20 flex items-center text-lg font-semibold text-white">
                <AppLogoIcon class="mr-3 size-8 fill-current text-white" />
                {{ name }}
            </Link>
        </div>

        <div class="flex min-h-svh items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
            <div class="w-full max-w-md rounded-2xl border bg-background/95 p-6 shadow-sm backdrop-blur md:p-8">
                <div class="mb-6 flex flex-col space-y-2 text-center">
                    <h1 v-if="title" class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
                    <p v-if="description" class="text-sm leading-6 text-muted-foreground">{{ description }}</p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
