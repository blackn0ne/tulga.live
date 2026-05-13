<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface RoleOption {
    value: 'admin' | 'student' | 'teacher';
    label: string;
}

interface SchoolClassOption {
    id: number;
    name: string;
}

const props = defineProps<{
    roles: RoleOption[];
    classes: SchoolClassOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Қолданушылар',
        href: '/users',
    },
    {
        title: 'Қосу',
        href: '/users/create',
    },
];

const form = useForm<{
    name: string;
    username: string;
    role: RoleOption['value'];
    class_id: '' | number;
}>({
    name: '',
    username: '',
    role: 'student',
    class_id: '',
});

const generatedEmail = computed(() => {
    const username = form.username.trim().toLowerCase();

    return username ? `${username}@mail.com` : 'login@mail.com';
});

const needsClass = computed(() => form.role === 'student');
const hasClasses = computed(() => props.classes.length > 0);

watch(
    () => form.role,
    (role) => {
        if (role !== 'student') {
            form.class_id = '';
        }
    },
);

const submit = () => {
    form.post(route('users.store'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Қолданушы қосу" />

        <div class="space-y-5 p-4 md:p-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight">Қолданушы қосу</h1>
                    <p class="text-sm text-muted-foreground">
                        Пайдаланушыға логин бойынша e-mail автоматты түрде құрылады, ал құпиясөз жүйе арқылы 6 цифрмен жасалады.
                    </p>
                </div>
            </section>

            <Card class="max-w-3xl shadow-sm">
                <CardHeader>
                    <CardTitle>Негізгі деректер</CardTitle>
                    <CardDescription>Оқушыны құру кезінде ғана сынып таңдалады.</CardDescription>
                </CardHeader>

                <CardContent>
                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="grid gap-2 md:col-span-2">
                                <Label for="name">ФИО</Label>
                                <Input id="name" v-model="form.name" required autofocus placeholder="Қолданушының толық аты" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="username">Логин</Label>
                                <Input id="username" v-model="form.username" required autocomplete="username" placeholder="Мысалы: aidos" />
                                <InputError :message="form.errors.username" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="role">Рөлі</Label>
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                                >
                                    <option v-for="role in props.roles" :key="role.value" :value="role.value">
                                        {{ role.label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.role" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="email_preview">Авто e-mail</Label>
                                <Input id="email_preview" :model-value="generatedEmail" readonly />
                            </div>

                            <div class="grid gap-2">
                                <Label for="password_preview">Авто құпиясөз</Label>
                                <Input id="password_preview" model-value="6 таңбалы сан автоматты түрде жасалады" readonly />
                            </div>

                            <div v-if="needsClass" class="grid gap-2 md:col-span-2">
                                <Label for="class_id">Сынып</Label>
                                <select
                                    id="class_id"
                                    v-model="form.class_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background"
                                    :disabled="!hasClasses"
                                >
                                    <option value="">Сыныпты таңдаңыз</option>
                                    <option v-for="schoolClass in props.classes" :key="schoolClass.id" :value="schoolClass.id">
                                        {{ schoolClass.name }}
                                    </option>
                                </select>
                                <p v-if="!hasClasses" class="text-sm text-amber-600">
                                    Алдымен кемінде бір сынып құрыңыз.
                                    <Link :href="route('classes.create')" class="underline">Сынып қосу</Link>
                                </p>
                                <InputError :message="form.errors.class_id" />
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button :disabled="form.processing || (needsClass && !hasClasses)">Құру</Button>
                            <Button variant="outline" as-child>
                                <Link :href="route('users.index')">Бас тарту</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
