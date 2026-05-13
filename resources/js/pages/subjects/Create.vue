<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Пәндер',
        href: '/subjects',
    },
    {
        title: 'Қосу',
        href: '/subjects/create',
    },
];

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('subjects.store'));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Пән қосу" />

        <div class="space-y-5 p-4 md:p-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight">Пән қосу</h1>
                    <p class="text-sm text-muted-foreground">Жаңа пән құру үшін тек атауын енгізу жеткілікті.</p>
                </div>
            </section>

            <Card class="max-w-2xl shadow-sm">
                <CardHeader>
                    <CardTitle>Негізгі ақпарат</CardTitle>
                    <CardDescription>Бұл формада тек бір ғана өріс бар.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="name">Пән атауы</Label>
                            <Input id="name" v-model="form.name" required autofocus placeholder="Мысалы: Математика" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Button :disabled="form.processing">Сақтау</Button>
                            <Button variant="outline" as-child>
                                <Link :href="route('subjects.index')">Бас тарту</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
