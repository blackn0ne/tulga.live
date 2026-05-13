<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SelectOption {
    id: number;
    name: string;
}

const props = defineProps<{
    subjects: SelectOption[];
    classes: SelectOption[];
    teachers: SelectOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Сабақтар',
        href: '/lessons',
    },
    {
        title: 'Қосу',
        href: '/lessons/create',
    },
];

const form = useForm<{
    subject_id: '' | number;
    class_id: '' | number;
    teacher_id: '' | number;
    starts_at: string;
    end_at: string;
}>({
    subject_id: '',
    class_id: '',
    teacher_id: '',
    starts_at: '',
    end_at: '',
});

const generatedTitle = computed(() => {
    const selectedSubject = props.subjects.find((subject) => subject.id === Number(form.subject_id));

    return selectedSubject?.name ?? '';
});

const submit = () => {
    form.post(route('lessons.store'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Сабақ қосу" />

        <div class="space-y-5 p-4 md:p-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight">Сабақ қосу</h1>
                    <p class="text-sm text-muted-foreground">Пән, сынып, мұғалім және басталу уақытын енгізіңіз. Аяқталу уақыты бос қалса, басталғаннан 45 минут кейін қойылады (оқушы интерфейсінде көрінбейді, авто-жабу үшін). Jitsi бөлмесі автоматты түрде жасалады.</p>
                </div>
            </section>

            <Card class="max-w-3xl shadow-sm">
                <CardHeader>
                    <CardTitle>Негізгі ақпарат</CardTitle>
                    <CardDescription>Сабақ атауы таңдалған пәннен автоматты түрде құрылады.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="grid gap-2 md:col-span-2">
                                <Label for="title_preview">Сабақ атауы</Label>
                                <Input id="title_preview" :model-value="generatedTitle || 'Пән таңдалғаннан кейін автоматты түрде толады'" readonly />
                            </div>

                            <div class="grid gap-2">
                                <Label for="subject_id">Пән</Label>
                                <select
                                    id="subject_id"
                                    v-model="form.subject_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                                >
                                    <option value="">Пәнді таңдаңыз</option>
                                    <option v-for="subject in props.subjects" :key="subject.id" :value="subject.id">
                                        {{ subject.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.subject_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="class_id">Сынып</Label>
                                <select
                                    id="class_id"
                                    v-model="form.class_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                                >
                                    <option value="">Сыныпты таңдаңыз</option>
                                    <option v-for="schoolClass in props.classes" :key="schoolClass.id" :value="schoolClass.id">
                                        {{ schoolClass.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.class_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="teacher_id">Мұғалім</Label>
                                <select
                                    id="teacher_id"
                                    v-model="form.teacher_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                                >
                                    <option value="">Мұғалімді таңдаңыз</option>
                                    <option v-for="teacher in props.teachers" :key="teacher.id" :value="teacher.id">
                                        {{ teacher.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.teacher_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="starts_at">Күні мен уақыты (басталуы)</Label>
                                <Input id="starts_at" v-model="form.starts_at" type="datetime-local" />
                                <InputError :message="form.errors.starts_at" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="end_at">Аяқталу уақыты (міндетті емес)</Label>
                                <Input id="end_at" v-model="form.end_at" type="datetime-local" />
                                <InputError :message="form.errors.end_at" />
                                <p class="text-xs text-muted-foreground">Бос қалса 45 минут. Оқушыға көрсетілмейді.</p>
                            </div>

                            <div class="grid gap-2 md:col-span-2">
                                <Label for="jitsi_room_preview">Jitsi бөлмесі</Label>
                                <Input id="jitsi_room_preview" model-value="Сақтағаннан кейін автоматты түрде жасалады" readonly />
                                <p class="text-sm text-muted-foreground">
                                    Мұғалім сабақты бастағанда Jitsi бөлмесі осы платформа ішінде ашылады.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button :disabled="form.processing">Сақтау</Button>
                            <Button variant="outline" as-child>
                                <Link :href="route('lessons.index')">Бас тарту</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
