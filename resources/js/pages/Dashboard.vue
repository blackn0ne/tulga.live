<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, CalendarDays, School, ShieldCheck, Users } from 'lucide-vue-next';
import { computed } from 'vue';

interface DashboardLesson {
    id: number;
    title: string;
    subject_name: string | null;
    class_name: string | null;
    teacher_name: string | null;
    starts_at: string;
    starts_at_date_key: string;
    starts_at_date_label: string;
    starts_at_time: string;
    jitsi_room: string | null;
    meeting_url: string | null;
    meeting_status: string;
    meeting_status_key: 'finished' | 'scheduled' | 'started';
}

interface DashboardStats {
    classes: number;
    subjects: number;
    users: number;
    lessons: number;
}

const props = defineProps<{
    mode: 'admin' | 'student' | 'teacher';
    userClassName?: string | null;
    status?: string;
    stats: DashboardStats;
    lessons: DashboardLesson[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Басқару тақтасы',
        href: '/dashboard',
    },
];

const openMeeting = (meetingUrl: string | null) => {
    if (! meetingUrl) {
        return;
    }

    window.open(meetingUrl, '_blank', 'noopener,noreferrer');
};

const startLesson = (lesson: DashboardLesson) => {
    router.post(
        route('lessons.start', lesson.id),
        {},
        {
            onSuccess: () => openMeeting(lesson.meeting_url),
        },
    );
};

const finishLesson = (lessonId: number) => {
    if (! window.confirm('Сабақты жабуды растайсыз ба?')) {
        return;
    }

    router.post(route('lessons.finish', lessonId));
};

const lessonsByDate = computed(() => {
    const groups = props.lessons.reduce<Array<{ dateKey: string; dateLabel: string; lessons: DashboardLesson[] }>>((carry, lesson) => {
        const currentGroup = carry.at(-1);

        if (currentGroup && currentGroup.dateKey === lesson.starts_at_date_key) {
            currentGroup.lessons.push(lesson);
            return carry;
        }

        carry.push({
            dateKey: lesson.starts_at_date_key,
            dateLabel: lesson.starts_at_date_label,
            lessons: [lesson],
        });

        return carry;
    }, []);

    return groups;
});
</script>

<template>
    <Head title="Басқару тақтасы" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-5 p-4 md:p-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight">
                            {{ props.mode === 'teacher' || props.mode === 'student' ? 'Менің сабақ кестем' : 'Басқару тақтасы' }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            <template v-if="props.mode === 'teacher'">
                                Сізге бекітілген сабақтар осында көрсетіледі. Сабақты осы беттен бастай аласыз.
                            </template>
                            <template v-else-if="props.mode === 'student'">
                                {{ props.userClassName ? `${props.userClassName} сыныбына арналған сабақтар тізімі.` : 'Сыныпқа арналған сабақтар кестесі.' }}
                            </template>
                            <template v-else>
                                Негізгі бөлімдерге жылдам өтіп, жүйені бір жерден басқарыңыз.
                            </template>
                        </p>
                    </div>

                    <Button v-if="props.mode === 'admin'" as-child>
                        <Link :href="route('lessons.create')">
                            <CalendarDays />
                            Сабақ жоспарлау
                        </Link>
                    </Button>

                </div>
            </section>

            <section v-if="props.status" class="rounded-2xl border bg-card px-4 py-3 shadow-sm">
                <p class="text-sm text-muted-foreground">{{ props.status }}</p>
            </section>

            <div v-if="props.mode === 'admin'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card class="shadow-sm">
                    <CardHeader class="space-y-3">
                        <School class="size-5 text-primary" />
                        <div class="space-y-1">
                            <CardTitle>{{ props.stats.classes }}</CardTitle>
                            <CardDescription>Сыныптар саны</CardDescription>
                        </div>
                    </CardHeader>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader class="space-y-3">
                        <BookOpen class="size-5 text-primary" />
                        <div class="space-y-1">
                            <CardTitle>{{ props.stats.subjects }}</CardTitle>
                            <CardDescription>Пәндер саны</CardDescription>
                        </div>
                    </CardHeader>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader class="space-y-3">
                        <Users class="size-5 text-primary" />
                        <div class="space-y-1">
                            <CardTitle>{{ props.stats.users }}</CardTitle>
                            <CardDescription>Қолданушылар саны</CardDescription>
                        </div>
                    </CardHeader>
                </Card>

                <Card class="shadow-sm">
                    <CardHeader class="space-y-3">
                        <ShieldCheck class="size-5 text-primary" />
                        <div class="space-y-1">
                            <CardTitle>{{ props.stats.lessons }}</CardTitle>
                            <CardDescription>Сабақтар саны</CardDescription>
                        </div>
                    </CardHeader>
                </Card>
            </div>

            <Card class="shadow-sm">
                <CardHeader class="flex flex-col gap-3 border-b pb-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <CardTitle>
                            {{ props.mode === 'admin' ? 'Жақын сабақтар' : 'Сабақ кестесі' }}
                        </CardTitle>
                        <CardDescription>
                            <template v-if="props.mode === 'teacher'">
                                Мұғалім ретінде алдағы сабақтарыңыздың тізімі.
                            </template>
                            <template v-else-if="props.mode === 'student'">
                                Оқушы ретінде өз сыныбыңыздың сабақтары.
                            </template>
                            <template v-else>
                                Жүйедегі ең жақын жоспарланған сабақтар.
                            </template>
                        </CardDescription>
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <div v-if="props.lessons.length === 0" class="flex flex-col items-center justify-center gap-4 px-6 py-16 text-center">
                        <div class="space-y-1">
                            <h3 class="text-lg font-medium">Сабақтар табылмады</h3>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    props.mode === 'admin'
                                        ? 'Алғашқы сабақты жоспарлау үшін төмендегі батырманы пайдаланыңыз.'
                                        : 'Әзірге сізге арналған сабақтар кестеде жоқ.'
                                }}
                            </p>
                        </div>

                        <Button v-if="props.mode === 'admin'" as-child>
                            <Link :href="route('lessons.create')">
                                <CalendarDays />
                                Сабақ қосу
                            </Link>
                        </Button>
                    </div>

                    <div v-else class="space-y-6 p-6">
                        <section v-for="group in lessonsByDate" :key="group.dateKey" class="space-y-3">
                            <div class="space-y-1">
                                <h3 class="text-base font-semibold tracking-tight">{{ group.dateLabel }}</h3>
                                <p class="text-xs text-muted-foreground">
                                    {{ group.lessons.length }} сабақ
                                </p>
                            </div>

                            <div class="grid gap-3">
                                <div
                                    v-for="lesson in group.lessons"
                                    :key="lesson.id"
                                    class="rounded-2xl border bg-background p-4 shadow-sm transition-colors hover:bg-muted/20"
                                >
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="space-y-3">
                                            <div class="space-y-1">
                                                <h4 class="font-medium leading-none">{{ lesson.title }}</h4>
                                                <p class="text-sm text-muted-foreground">{{ lesson.subject_name ?? '-' }}</p>
                                            </div>

                                            <div class="flex flex-wrap gap-2 text-xs text-muted-foreground">
                                                <span class="rounded-full bg-muted px-2.5 py-1">{{ lesson.class_name ?? '-' }}</span>
                                                <span class="rounded-full bg-muted px-2.5 py-1">{{ lesson.teacher_name ?? '-' }}</span>
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-3 lg:items-end">
                                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                                <span class="rounded-full border px-2.5 py-1 text-xs font-medium">
                                                    {{ lesson.starts_at_time }}
                                                </span>
                                                <span class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium">
                                                    {{ lesson.meeting_status }}
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap gap-2 lg:justify-end">
                                                <Button
                                                    v-if="props.mode === 'teacher' && lesson.meeting_status_key === 'scheduled'"
                                                    size="sm"
                                                    @click="startLesson(lesson)"
                                                >
                                                    Сабақты бастау
                                                </Button>

                                                <template v-else-if="props.mode === 'teacher' && lesson.meeting_status_key === 'started'">
                                                    <Button size="sm" variant="outline" as-child>
                                                        <Link :href="route('lessons.show', lesson.id)">
                                                            Сабақты ашу
                                                        </Link>
                                                    </Button>

                                                    <Button size="sm" variant="destructive" @click="finishLesson(lesson.id)">
                                                        Жабу
                                                    </Button>
                                                </template>

                                                <Button
                                                    v-else-if="props.mode === 'student' && lesson.meeting_status_key === 'scheduled'"
                                                    size="sm"
                                                    variant="outline"
                                                    disabled
                                                >
                                                    Күту
                                                </Button>

                                                <Button
                                                    v-else-if="props.mode === 'student' && lesson.meeting_status_key === 'finished'"
                                                    size="sm"
                                                    variant="outline"
                                                    disabled
                                                >
                                                    Өтілді
                                                </Button>

                                                <Button v-else size="sm" variant="outline" as-child>
                                                    <Link :href="route('lessons.show', lesson.id)">
                                                        {{ props.mode === 'student' ? 'Сабаққа кіру' : 'Сабақты ашу' }}
                                                    </Link>
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="props.mode === 'admin'" class="shadow-sm">
                <CardHeader>
                    <CardTitle>Жылдам әрекеттер</CardTitle>
                    <CardDescription>Көбірек қолданылатын бөлімдерге бір шерту арқылы өтіңіз.</CardDescription>
                </CardHeader>
                <CardContent class="flex flex-wrap gap-3">
                    <Button as-child>
                        <Link :href="route('classes.create')">Сынып қосу</Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('subjects.create')">Пән қосу</Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('users.create')">Қолданушы қосу</Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('lessons.create')">Сабақ жоспарлау</Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
