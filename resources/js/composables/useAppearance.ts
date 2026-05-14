import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

/** App always uses light theme; system dark mode is ignored. */
export function updateTheme(_value?: Appearance): void {
    document.documentElement.classList.remove('dark');
}

export function initializeTheme(): void {
    localStorage.setItem('appearance', 'light');
    updateTheme();
}

export function useAppearance() {
    const appearance = ref<Appearance>('light');

    onMounted(() => {
        initializeTheme();
        appearance.value = 'light';
    });

    function updateAppearance(_value: Appearance): void {
        appearance.value = 'light';
        localStorage.setItem('appearance', 'light');
        updateTheme();
    }

    return {
        appearance,
        updateAppearance,
    };
}
