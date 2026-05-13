<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Көрініс баптаулары',
        href: '/settings/appearance',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Көрініс баптаулары" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Көрініс баптаулары" description="Интерфейстің көрініс режимін таңдаңыз" />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
