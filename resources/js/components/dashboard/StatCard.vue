<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { LucideIcon } from 'lucide-vue-next';

defineProps<{
    title: string;
    value: string | number;
    description?: string;
    icon: LucideIcon;
    trend?: 'up' | 'down' | 'neutral';
    trendValue?: string;
    iconColor?: string;
}>();
</script>

<template>
    <Card class="h-full">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2 sm:pb-2">
            <CardTitle class="text-xs sm:text-sm font-medium leading-tight">{{ title }}</CardTitle>
            <component
                :is="icon"
                :class="[
                    'h-3.5 w-3.5 sm:h-4 sm:w-4 flex-shrink-0',
                    iconColor || 'text-muted-foreground',
                ]"
            />
        </CardHeader>
        <CardContent class="space-y-1">
            <div class="text-xl sm:text-2xl font-bold">{{ value }}</div>
            <p v-if="description || trendValue" class="text-xs text-muted-foreground">
                <span v-if="trend && trendValue" :class="{
                    'text-green-600 dark:text-green-400': trend === 'up',
                    'text-red-600 dark:text-red-400': trend === 'down',
                }">
                    {{ trendValue }}
                </span>
                <span v-if="description" class="truncate block sm:inline">{{ description }}</span>
            </p>
        </CardContent>
    </Card>
</template>
