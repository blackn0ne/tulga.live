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

interface ManagedUser {
    id: number;
    name: string;
    username: string;
    email: string;
    role: 'admin' | 'student' | 'teacher';
    role_label: string;
    school_class_name: string | null;
}

const props = defineProps<{
    managedUser: ManagedUser;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Қолданушылар',
        href: '/users',
    },
    {
        title: 'Өңдеу',
        href: `/users/${props.managedUser.id}/edit`,
    },
];

const form = useForm({
    name: props.managedUser.name,
    username: props.managedUser.username,
    password: '',
});

const generatedEmail = computed(() => {
    const username = form.username.trim().toLowerCase();

    return username ? `${username}@mail.com` : 'login@mail.com';
});

const submit = () => {
    form.put(route('users.update', props.managedUser.id));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`${props.managedUser.name} - өңдеу`" />

        <div class="space-y-5 p-4 md:p-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight">Қолданушыны өңдеу</h1>
                    <p class="text-sm text-muted-foreground">ФИО, логин және қажет болса жаңа құпиясөзді өзгертіңіз.</p>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,2fr)_320px]">
                <Card class="shadow-sm">
                    <CardHeader>
                        <CardTitle>Өңдеу формасы</CardTitle>
                        <CardDescription>Бос қалдырылған жаңа құпиясөз өзгертілмейді.</CardDescription>
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
                                    <Input id="username" v-model="form.username" required autocomplete="username" placeholder="Логин" />
                                    <InputError :message="form.errors.username" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="email_preview">Авто e-mail</Label>
                                    <Input id="email_preview" :model-value="generatedEmail" readonly />
                                </div>

                                <div class="grid gap-2 md:col-span-2">
                                    <Label for="password">Жаңа құпиясөз</Label>
                                    <Input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        autocomplete="new-password"
                                        placeholder="Өзгерту қажет болса енгізіңіз"
                                    />
                                    <p class="text-sm text-muted-foreground">Кемінде 6 таңба. Бұл өріс міндетті емес.</p>
                                    <InputError :message="form.errors.password" />
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <Button :disabled="form.processing">Жаңарту</Button>
                                <Button variant="outline" as-child>
                                    <Link :href="route('users.index')">Бас тарту</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card class="h-fit shadow-sm">
                    <CardHeader>
                        <CardTitle>Қосымша ақпарат</CardTitle>
                        <CardDescription>Бұл блок тек анықтама үшін көрсетіледі.</CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="rounded-xl border bg-muted/30 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Рөлі</p>
                            <p class="mt-1 font-medium">{{ props.managedUser.role_label }}</p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Сыныбы</p>
                            <p class="mt-1 font-medium">{{ props.managedUser.school_class_name ?? 'Таңдалмаған' }}</p>
                        </div>

                        <div class="rounded-xl border bg-muted/30 px-4 py-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Қазіргі e-mail</p>
                            <p class="mt-1 font-medium">{{ props.managedUser.email }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
