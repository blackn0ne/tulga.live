<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';

interface SubjectItem {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

interface PaginatedSubjects {
    data: SubjectItem[];
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
    subjects: PaginatedSubjects;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Пәндер',
        href: '/subjects',
    },
];

const deleteForm = useForm({});

const remove = (subject: SubjectItem) => {
    if (! window.confirm(`"${subject.name}" пәнін жойғыңыз келе ме?`)) {
        return;
    }

    deleteForm.delete(route('subjects.destroy', subject.id), {
        data: {
            page: props.subjects.current_page,
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Пәндер" />

        <div class="space-y-5 p-4 md:p-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight">Пәндер</h1>
                        <p class="text-sm text-muted-foreground">Пәндерді ықшам түрде құрып, өзгертіп және басқарыңыз.</p>
                    </div>

                    <Button as-child>
                        <Link :href="route('subjects.create')">
                            <Plus />
                            Пән қосу
                        </Link>
                    </Button>
                </div>
            </section>

            <Card class="shadow-sm">
                <CardHeader class="flex flex-col gap-3 border-b pb-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <CardTitle>Пәндер тізімі</CardTitle>
                        <CardDescription>
                            Барлығы {{ props.subjects.total }} жазба. Осы бетте {{ props.subjects.from ?? 0 }}-{{ props.subjects.to ?? 0 }} аралығы көрсетіліп тұр.
                        </CardDescription>
                    </div>

                    <div class="rounded-lg bg-muted px-3 py-2 text-sm text-muted-foreground">
                        Әр бетте {{ props.subjects.per_page }} жазба
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <div v-if="props.subjects.data.length === 0" class="flex flex-col items-center justify-center gap-4 px-6 py-16 text-center">
                        <div class="space-y-1">
                            <h3 class="text-lg font-medium">Пәндер әлі қосылмаған</h3>
                            <p class="text-sm text-muted-foreground">Алғашқы пәнді дәл қазір қосып, жұмысты бастауға болады.</p>
                        </div>

                        <Button as-child>
                            <Link :href="route('subjects.create')">
                                <Plus />
                                Пән қосу
                            </Link>
                        </Button>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b bg-muted/40 text-muted-foreground">
                                <tr>
                                    <th class="w-16 px-4 py-3 text-left font-medium">№</th>
                                    <th class="px-4 py-3 text-left font-medium">Пән атауы</th>
                                    <th class="px-4 py-3 text-left font-medium">Құрылған күні</th>
                                    <th class="px-4 py-3 text-left font-medium">Жаңартылған күні</th>
                                    <th class="px-4 py-3 text-right font-medium">Әрекеттер</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(subject, index) in props.subjects.data" :key="subject.id" class="bg-background">
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ ((props.subjects.current_page - 1) * props.subjects.per_page) + index + 1 }}
                                    </td>
                                    <td class="px-4 py-3 font-medium">{{ subject.name }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ subject.created_at }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ subject.updated_at }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="route('subjects.edit', subject.id)">
                                                    <Pencil />
                                                    Өңдеу
                                                </Link>
                                            </Button>

                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                :disabled="deleteForm.processing"
                                                @click="remove(subject)"
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
                            {{ props.subjects.current_page }} / {{ props.subjects.last_page }} бет
                        </p>

                        <div class="flex items-center gap-2">
                            <Button v-if="props.subjects.prev_page_url" variant="outline" size="sm" as-child>
                                <Link :href="props.subjects.prev_page_url">Алдыңғы</Link>
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>Алдыңғы</Button>

                            <Button v-if="props.subjects.next_page_url" variant="outline" size="sm" as-child>
                                <Link :href="props.subjects.next_page_url">Келесі</Link>
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>Келесі</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
