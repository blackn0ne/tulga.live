<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';

interface SchoolClassItem {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

interface PaginatedClasses {
    data: SchoolClassItem[];
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
    classes: PaginatedClasses;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Сыныптар',
        href: '/classes',
    },
];

const deleteForm = useForm({});

const remove = (schoolClass: SchoolClassItem) => {
    if (! window.confirm(`"${schoolClass.name}" сыныбын жойғыңыз келе ме?`)) {
        return;
    }

    deleteForm.delete(route('classes.destroy', schoolClass.id), {
        data: {
            page: props.classes.current_page,
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Сыныптар" />

        <div class="space-y-5 p-4 md:p-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight">Сыныптар</h1>
                        <p class="text-sm text-muted-foreground">Сыныптарды ықшам түрде құрып, өзгертіп және басқарыңыз.</p>
                    </div>

                    <Button as-child>
                        <Link :href="route('classes.create')">
                            <Plus />
                            Сынып қосу
                        </Link>
                    </Button>
                </div>
            </section>

            <Card class="shadow-sm">
                <CardHeader class="flex flex-col gap-3 border-b pb-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <CardTitle>Сыныптар тізімі</CardTitle>
                        <CardDescription>
                            Барлығы {{ props.classes.total }} жазба. Осы бетте {{ props.classes.from ?? 0 }}-{{ props.classes.to ?? 0 }} аралығы көрсетіліп тұр.
                        </CardDescription>
                    </div>

                    <div class="rounded-lg bg-muted px-3 py-2 text-sm text-muted-foreground">
                        Әр бетте {{ props.classes.per_page }} жазба
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <div v-if="props.classes.data.length === 0" class="flex flex-col items-center justify-center gap-4 px-6 py-16 text-center">
                        <div class="space-y-1">
                            <h3 class="text-lg font-medium">Сыныптар әлі қосылмаған</h3>
                            <p class="text-sm text-muted-foreground">Алғашқы сыныпты дәл қазір қосып, жұмысты бастауға болады.</p>
                        </div>

                        <Button as-child>
                            <Link :href="route('classes.create')">
                                <Plus />
                                Сынып қосу
                            </Link>
                        </Button>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b bg-muted/40 text-muted-foreground">
                                <tr>
                                    <th class="w-16 px-4 py-3 text-left font-medium">№</th>
                                    <th class="px-4 py-3 text-left font-medium">Сынып атауы</th>
                                    <th class="px-4 py-3 text-left font-medium">Құрылған күні</th>
                                    <th class="px-4 py-3 text-left font-medium">Жаңартылған күні</th>
                                    <th class="px-4 py-3 text-right font-medium">Әрекеттер</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(schoolClass, index) in props.classes.data" :key="schoolClass.id" class="bg-background">
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ ((props.classes.current_page - 1) * props.classes.per_page) + index + 1 }}
                                    </td>
                                    <td class="px-4 py-3 font-medium">{{ schoolClass.name }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ schoolClass.created_at }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ schoolClass.updated_at }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="route('classes.edit', schoolClass.id)">
                                                    <Pencil />
                                                    Өңдеу
                                                </Link>
                                            </Button>

                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                :disabled="deleteForm.processing"
                                                @click="remove(schoolClass)"
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
                            {{ props.classes.current_page }} / {{ props.classes.last_page }} бет
                        </p>

                        <div class="flex items-center gap-2">
                            <Button v-if="props.classes.prev_page_url" variant="outline" size="sm" as-child>
                                <Link :href="props.classes.prev_page_url">Алдыңғы</Link>
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>Алдыңғы</Button>

                            <Button v-if="props.classes.next_page_url" variant="outline" size="sm" as-child>
                                <Link :href="props.classes.next_page_url">Келесі</Link>
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>Келесі</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
