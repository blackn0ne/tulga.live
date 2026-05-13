<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';

interface ManagedUser {
    id: number;
    name: string;
    username: string;
    email: string;
    role: 'admin' | 'student' | 'teacher';
    role_label: string;
    school_class_name: string | null;
    is_current: boolean;
}

interface GeneratedCredentials {
    name: string;
    username: string;
    email: string;
    password: string;
}

interface PaginatedUsers {
    data: ManagedUser[];
    current_page: number;
    from: number | null;
    last_page: number;
    next_page_url: string | null;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    users: PaginatedUsers;
    status?: string;
    generatedCredentials?: GeneratedCredentials | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Қолданушылар',
        href: '/users',
    },
];

const deleteForm = useForm({});

const remove = (user: ManagedUser) => {
    if (user.is_current) {
        return;
    }

    if (! window.confirm(`"${user.name}" қолданушысын жойғыңыз келе ме?`)) {
        return;
    }

    deleteForm.delete(route('users.destroy', user.id), {
        data: {
            page: props.users.current_page,
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Қолданушылар" />

        <div class="space-y-5 p-4 md:p-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight">Қолданушылар</h1>
                        <p class="text-sm text-muted-foreground">Қолданушыларды құрып, деректерін жаңартып және жүйеге қолжетімділігін басқарыңыз.</p>
                    </div>

                    <Button as-child>
                        <Link :href="route('users.create')">
                            <Plus />
                            Қолданушы қосу
                        </Link>
                    </Button>
                </div>
            </section>

            <section v-if="props.generatedCredentials" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <div class="space-y-3">
                    <div>
                        <h2 class="text-base font-semibold text-emerald-900">Қолданушы құрылды</h2>
                        <p class="text-sm text-emerald-800">Төмендегі уақытша деректерді бір рет сақтап алыңыз. Құпиясөз экранда тек қазір көрсетіліп тұр.</p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-4">
                        <div class="rounded-xl border bg-white px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Аты</p>
                            <p class="mt-1 font-medium">{{ props.generatedCredentials.name }}</p>
                        </div>
                        <div class="rounded-xl border bg-white px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Логин</p>
                            <p class="mt-1 font-medium">{{ props.generatedCredentials.username }}</p>
                        </div>
                        <div class="rounded-xl border bg-white px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">E-mail</p>
                            <p class="mt-1 font-medium">{{ props.generatedCredentials.email }}</p>
                        </div>
                        <div class="rounded-xl border bg-white px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Уақытша құпиясөз</p>
                            <p class="mt-1 font-mono text-lg font-semibold">{{ props.generatedCredentials.password }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section v-else-if="props.status" class="rounded-2xl border bg-card px-4 py-3 shadow-sm">
                <p class="text-sm text-muted-foreground">{{ props.status }}</p>
            </section>

            <Card class="shadow-sm">
                <CardHeader class="flex flex-col gap-3 border-b pb-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <CardTitle>Қолданушылар тізімі</CardTitle>
                        <CardDescription>
                            Барлығы {{ props.users.total }} жазба. Осы бетте {{ props.users.from ?? 0 }}-{{ props.users.to ?? 0 }} аралығы көрсетіліп тұр.
                        </CardDescription>
                    </div>

                    <div class="rounded-lg bg-muted px-3 py-2 text-sm text-muted-foreground">
                        Әр бетте {{ props.users.per_page }} жазба
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <div v-if="props.users.data.length === 0" class="flex flex-col items-center justify-center gap-4 px-6 py-16 text-center">
                        <div class="space-y-1">
                            <h3 class="text-lg font-medium">Қолданушылар әлі қосылмаған</h3>
                            <p class="text-sm text-muted-foreground">Бірінші қолданушыны дәл қазір қосып, жүйені толтыра бастаңыз.</p>
                        </div>

                        <Button as-child>
                            <Link :href="route('users.create')">
                                <Plus />
                                Қолданушы қосу
                            </Link>
                        </Button>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b bg-muted/40 text-muted-foreground">
                                <tr>
                                    <th class="w-16 px-4 py-3 text-left font-medium">№</th>
                                    <th class="px-4 py-3 text-left font-medium">ФИО</th>
                                    <th class="px-4 py-3 text-left font-medium">Логин</th>
                                    <th class="px-4 py-3 text-left font-medium">E-mail</th>
                                    <th class="px-4 py-3 text-left font-medium">Рөлі</th>
                                    <th class="px-4 py-3 text-left font-medium">Сыныбы</th>
                                    <th class="px-4 py-3 text-right font-medium">Әрекеттер</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y">
                                <tr v-for="(user, index) in props.users.data" :key="user.id" class="bg-background">
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ ((props.users.current_page - 1) * props.users.per_page) + index + 1 }}
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        <div class="flex items-center gap-2">
                                            <span>{{ user.name }}</span>
                                            <span
                                                v-if="user.is_current"
                                                class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                                            >
                                                Сіз
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ user.username }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">
                                            {{ user.role_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ user.school_class_name ?? 'Таңдалмаған' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="route('users.edit', user.id)">
                                                    <Pencil />
                                                    Өңдеу
                                                </Link>
                                            </Button>

                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                :disabled="deleteForm.processing || user.is_current"
                                                @click="remove(user)"
                                            >
                                                <Trash2 />
                                                Жою
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col gap-3 border-t px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-muted-foreground">
                            {{ props.users.current_page }} / {{ props.users.last_page }} бет
                        </p>

                        <div class="flex items-center gap-2">
                            <Button v-if="props.users.prev_page_url" variant="outline" size="sm" as-child>
                                <Link :href="props.users.prev_page_url">Алдыңғы</Link>
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>Алдыңғы</Button>

                            <Button v-if="props.users.next_page_url" variant="outline" size="sm" as-child>
                                <Link :href="props.users.next_page_url">Келесі</Link>
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>Келесі</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
