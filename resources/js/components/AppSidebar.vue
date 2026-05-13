<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, CalendarDays, LayoutGrid, School, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();

const mainNavItems = computed<NavItem[]>(() => {
    const role = page.props.auth.user.role;

    if (role === 'teacher') {
        return [
            {
                title: 'Басқару тақтасы',
                href: '/dashboard',
                icon: LayoutGrid,
            },
        ];
    }

    if (role === 'student') {
        return [
            {
                title: 'Басқару тақтасы',
                href: '/dashboard',
                icon: LayoutGrid,
            },
        ];
    }

    return [
        {
            title: 'Басқару тақтасы',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Сыныптар',
            href: '/classes',
            icon: School,
        },
        {
            title: 'Сабақтар',
            href: '/lessons',
            icon: CalendarDays,
        },
        {
            title: 'Пәндер',
            href: '/subjects',
            icon: BookOpen,
        },
        {
            title: 'Қолданушылар',
            href: '/users',
            icon: Users,
        },
    ];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
