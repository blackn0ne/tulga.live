<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, MonitorPlay, Video } from 'lucide-vue-next';
import { computed } from 'vue';

interface LessonDetails {
    id: number;
    title: string;
    subject_name: string | null;
    class_name: string | null;
    teacher_name: string | null;
    starts_at: string;
    jitsi_room: string | null;
    meeting_url: string | null;
    meeting_provider: string;
    meeting_status: string;
    meeting_status_key: 'finished' | 'scheduled' | 'started';
}

const props = defineProps<{
    status?: string;
    viewerRole: 'admin' | 'student' | 'teacher';
    canStart: boolean;
    canEnter: boolean;
    lesson: LessonDetails;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Басқару тақтасы',
        href: '/dashboard',
    },
    {
        title: props.lesson.title,
        href: `/lessons/${props.lesson.id}`,
    },
]);

const openMeeting = () => {
    if (!props.lesson.meeting_url) {
        return;
    }

    window.open(props.lesson.meeting_url, '_blank', 'noopener,noreferrer');
};

const startLesson = () => {
    router.post(
        route('lessons.start', props.lesson.id),
        {},
        {
            onSuccess: () => openMeeting(),
        },
    );
};

const finishLesson = () => {
    if (! window.confirm('Сабақты жабуды растайсыз ба?')) {
        return;
    }

    router.post(route('lessons.finish', props.lesson.id));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="props.lesson.title" />

        <div class="space-y-5 p-4 md:p-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ props.lesson.title }}</h1>
                        <p class="text-sm text-muted-foreground">
                            {{ props.lesson.subject_name ?? '-' }} | {{ props.lesson.class_name ?? '-' }} | {{ props.lesson.starts_at }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button v-if="props.canStart && props.lesson.meeting_status_key === 'scheduled'" @click="startLesson">
                            <MonitorPlay />
                            Сабақты бастау
                        </Button>

                        <template v-if="props.viewerRole === 'teacher' && props.lesson.meeting_status_key === 'started'">
                            <Button variant="outline" @click="openMeeting">
                                <ExternalLink />
                                Сабақты ашу
                            </Button>

                            <Button variant="destructive" @click="finishLesson">
                                Жабу
                            </Button>
                        </template>

                        <Button variant="outline" as-child>
                            <Link :href="route('dashboard')">Кері қайту</Link>
                        </Button>
                    </div>
                </div>
            </section>

            <section v-if="props.status" class="rounded-2xl border bg-card px-4 py-3 shadow-sm">
                <p class="text-sm text-muted-foreground">{{ props.status }}</p>
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,2fr)_320px]">
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Онлайн сабақ бөлмесі</CardTitle>
                        <CardDescription>Сабақ `meet.jit.si` арқылы бөлек бетте ашылады.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex min-h-[280px] flex-col items-center justify-center rounded-2xl border border-dashed bg-muted/20 p-8 text-center">
                            <Video class="mb-4 size-10 text-muted-foreground" />

                            <template v-if="props.viewerRole === 'student' && !props.canEnter">
                                <template v-if="props.lesson.meeting_status_key === 'finished'">
                                    <h3 class="text-lg font-medium">Сабақ өтіп кетті</h3>
                                    <p class="mt-2 max-w-md text-sm text-muted-foreground">
                                        Бұл сабақ мұғалім тарапынан жабылды және енді өткен сабақ ретінде көрсетіледі.
                                    </p>
                                </template>

                                <template v-else>
                                    <h3 class="text-lg font-medium">Сабақ әлі басталған жоқ</h3>
                                    <p class="mt-2 max-w-md text-sm text-muted-foreground">
                                        Мұғалім сабақты бастағаннан кейін ғана оқушыларға кіру батырмасы көрінеді.
                                    </p>
                                </template>
                            </template>

                            <template v-else-if="props.canStart && props.lesson.meeting_status_key === 'scheduled'">
                                <h3 class="text-lg font-medium">Jitsi бөлмесі дайын</h3>
                                <p class="mt-2 max-w-md text-sm text-muted-foreground">
                                    `Сабақты бастау` батырмасын басқаннан кейін кездесу `meet.jit.si` бетінде ашылады, сол жерде
                                    ұйымдастырушы ретінде жалғастырасыз.
                                </p>
                            </template>

                            <template v-else-if="props.canEnter && props.lesson.meeting_url">
                                <h3 class="text-lg font-medium">Сабаққа кіруге болады</h3>
                                <p class="mt-2 max-w-md text-sm text-muted-foreground">
                                    {{
                                        props.viewerRole === 'teacher'
                                            ? 'Кездесуді бөлек бетте ашып, ұйымдастырушы ретінде жалғастырыңыз.'
                                            : 'Мұғалім сабақты бастады. Енді Jitsi бөлмесіне кіре аласыз.'
                                    }}
                                </p>

                                <div class="mt-4 flex flex-wrap justify-center gap-3">
                                    <Button @click="openMeeting">
                                        <ExternalLink />
                                        {{ props.viewerRole === 'student' ? 'Сабаққа кіру' : 'Jitsi-ді ашу' }}
                                    </Button>
                                </div>
                            </template>

                            <template v-else>
                                <template v-if="props.lesson.meeting_status_key === 'finished'">
                                    <h3 class="text-lg font-medium">Сабақ аяқталды</h3>
                                    <p class="mt-2 max-w-md text-sm text-muted-foreground">
                                        Мұғалім бұл сабақты жапты. Ол кестеде өткен сабақ ретінде көрсетіледі.
                                    </p>
                                </template>

                                <template v-else>
                                    <h3 class="text-lg font-medium">Jitsi бөлмесі қолжетімсіз</h3>
                                    <p class="mt-2 max-w-md text-sm text-muted-foreground">
                                        Бөлмені ашу үшін сабақтың басталуын күтіңіз.
                                    </p>
                                </template>
                            </template>
                        </div>
                    </CardContent>
                </Card>

                <Card class="h-fit shadow-sm">
                    <CardHeader>
                        <CardTitle>Сабақ ақпараты</CardTitle>
                        <CardDescription>Негізгі мәліметтер мен кездесу күйі.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="rounded-xl border bg-muted/30 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Пән</p>
                            <p class="mt-1 font-medium">{{ props.lesson.subject_name ?? '-' }}</p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Сынып</p>
                            <p class="mt-1 font-medium">{{ props.lesson.class_name ?? '-' }}</p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Мұғалім</p>
                            <p class="mt-1 font-medium">{{ props.lesson.teacher_name ?? '-' }}</p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Күні мен уақыты</p>
                            <p class="mt-1 font-medium">{{ props.lesson.starts_at }}</p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Кездесу платформасы</p>
                            <p class="mt-1 font-medium">{{ props.lesson.meeting_provider }}</p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Кездесу күйі</p>
                            <p class="mt-1 font-medium">{{ props.lesson.meeting_status }}</p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Jitsi бөлмесі</p>
                            <p class="mt-1 font-medium">
                                {{ props.lesson.jitsi_room ?? 'Жасалмаған' }}
                            </p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Кіру сілтемесі</p>
                            <p class="mt-1 break-all font-medium">
                                {{ props.lesson.meeting_url ?? 'Сабақ басталған соң ашылады' }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
