<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface SchoolClassForm {
    id: number;
    name: string;
}

const props = defineProps<{
    schoolClass: SchoolClassForm;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Сыныптар',
        href: '/classes',
    },
    {
        title: 'Өңдеу',
        href: `/classes/${props.schoolClass.id}/edit`,
    },
];

const form = useForm({
    name: props.schoolClass.name,
});

const submit = () => {
    form.put(route('classes.update', props.schoolClass.id));
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`${props.schoolClass.name} - өңдеу`" />

        <div class="space-y-5 p-4 md:p-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight">Сыныпты өңдеу</h1>
                    <p class="text-sm text-muted-foreground">Бұл жерде сыныптың атауын ғана өзгерте аласыз.</p>
                </div>
            </section>

            <Card class="max-w-2xl shadow-sm">
                <CardHeader>
                    <CardTitle>Негізгі ақпарат</CardTitle>
                    <CardDescription>Атауды өзгертіп, жаңартуды сақтаңыз.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="grid gap-2">
                            <Label for="name">Сынып атауы</Label>
                            <Input id="name" v-model="form.name" required autofocus placeholder="Сынып атауын енгізіңіз" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Button :disabled="form.processing">Жаңарту</Button>
                            <Button variant="outline" as-child>
                                <Link :href="route('classes.index')">Бас тарту</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
