<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';

interface LessonItem {
    id: number;
    title: string;
    subject_name: string | null;
    class_name: string | null;
    teacher_name: string | null;
    starts_at: string;
    jitsi_room: string | null;
    meeting_provider: string;
    meeting_status: string;
    meeting_has_room?: boolean;
}

interface PaginatedLessons {
    data: LessonItem[];
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
    lessons: PaginatedLessons;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Сабақтар',
        href: '/lessons',
    },
];

const deleteForm = useForm({});

const remove = (lesson: LessonItem) => {
    if (! window.confirm(`"${lesson.title}" сабағын жойғыңыз келе ме?`)) {
        return;
    }

    deleteForm.delete(route('lessons.destroy', lesson.id), {
        data: {
            page: props.lessons.current_page,
        },
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Сабақтар" />

        <div class="space-y-5 p-4 md:p-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight">Сабақтар</h1>
                        <p class="text-sm text-muted-foreground">Пән, сынып, мұғалім және уақыт бойынша сабақтарды жоспарлаңыз. Әр сабаққа Jitsi бөлмесі беріледі.</p>
                    </div>

                    <Button as-child>
                        <Link :href="route('lessons.create')">
                            <Plus />
                            Сабақ қосу
                        </Link>
                    </Button>
                </div>
            </section>

            <Card class="shadow-sm">
                <CardHeader class="flex flex-col gap-3 border-b pb-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <CardTitle>Сабақтар тізімі</CardTitle>
                        <CardDescription>
                            Барлығы {{ props.lessons.total }} жазба. Осы бетте {{ props.lessons.from ?? 0 }}-{{ props.lessons.to ?? 0 }} аралығы көрсетіліп тұр.
                        </CardDescription>
                    </div>

                    <div class="rounded-lg bg-muted px-3 py-2 text-sm text-muted-foreground">
                        Әр бетте {{ props.lessons.per_page }} жазба
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <div v-if="props.lessons.data.length === 0" class="flex flex-col items-center justify-center gap-4 px-6 py-16 text-center">
                        <div class="space-y-1">
                            <h3 class="text-lg font-medium">Сабақтар әлі қосылмаған</h3>
                            <p class="text-sm text-muted-foreground">Алғашқы сабақты дәл қазір жоспарлауға болады.</p>
                        </div>

                        <Button as-child>
                            <Link :href="route('lessons.create')">
                                <Plus />
                                Сабақ қосу
                            </Link>
                        </Button>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b bg-muted/40 text-muted-foreground">
                                <tr>
                                    <th class="w-16 px-4 py-3 text-left font-medium">№</th>
                                    <th class="px-4 py-3 text-left font-medium">Сабақ атауы</th>
                                    <th class="px-4 py-3 text-left font-medium">Пән</th>
                                    <th class="px-4 py-3 text-left font-medium">Сынып</th>
                                    <th class="px-4 py-3 text-left font-medium">Мұғалім</th>
                                    <th class="px-4 py-3 text-left font-medium">Күні мен уақыты</th>
                                    <th class="px-4 py-3 text-left font-medium">Онлайн бөлім</th>
                                    <th class="px-4 py-3 text-right font-medium">Әрекеттер</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(lesson, index) in props.lessons.data" :key="lesson.id" class="bg-background">
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ ((props.lessons.current_page - 1) * props.lessons.per_page) + index + 1 }}
                                    </td>
                                    <td class="px-4 py-3 font-medium">{{ lesson.title }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ lesson.subject_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ lesson.class_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ lesson.teacher_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ lesson.starts_at }}</td>
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            <div class="text-xs font-medium">{{ lesson.meeting_provider }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ lesson.meeting_status }}{{ lesson.meeting_has_room ? ' | Бөлме дайын' : ' | Бөлме жоқ' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button variant="outline" size="sm" as-child>
                                                <Link :href="route('lessons.edit', lesson.id)">
                                                    <Pencil />
                                                    Өңдеу
                                                </Link>
                                            </Button>

                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                :disabled="deleteForm.processing"
                                                @click="remove(lesson)"
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
                            {{ props.lessons.current_page }} / {{ props.lessons.last_page }} бет
                        </p>

                        <div class="flex items-center gap-2">
                            <Button v-if="props.lessons.prev_page_url" variant="outline" size="sm" as-child>
                                <Link :href="props.lessons.prev_page_url">Алдыңғы</Link>
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>Алдыңғы</Button>

                            <Button v-if="props.lessons.next_page_url" variant="outline" size="sm" as-child>
                                <Link :href="props.lessons.next_page_url">Келесі</Link>
                            </Button>
                            <Button v-else variant="outline" size="sm" disabled>Келесі</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
