<script setup lang="ts">
import { Sun } from 'lucide-vue-next';

interface Props {
    class?: string;
}

const { class: containerClass = '' } = defineProps<Props>();
</script>

<template>
    <div
        :class="[
            'inline-flex max-w-xl items-start gap-3 rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm text-muted-foreground',
            containerClass,
        ]"
    >
        <Sun class="mt-0.5 h-4 w-4 shrink-0 text-foreground" />
        <span>
            Интерфейс әрқашан <span class="font-medium text-foreground">ашық (ақ)</span> режимде көрсетіледі; жүйелік қараңғы режим қолданылмайды.
        </span>
    </div>
</template>
