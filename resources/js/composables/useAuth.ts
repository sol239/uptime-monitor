import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { User, AppPageProps } from '@/types';

export function useAuth() {
    const page = usePage<AppPageProps>();
    
    const user = computed((): User | null => {
        return page.props.auth?.user || null;
    });
    
    const userId = computed((): number | null => {
        return user.value?.id || null;
    });
    
    const isAuthenticated = computed((): boolean => {
        return !!user.value;
    });
    
    return {
        user: user.value,
        userId: userId.value,
        isAuthenticated: isAuthenticated.value,
        // Also provide reactive versions
        userReactive: user,
        userIdReactive: userId,
        isAuthenticatedReactive: isAuthenticated,
    };
}
