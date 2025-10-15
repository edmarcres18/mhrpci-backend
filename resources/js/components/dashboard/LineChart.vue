<script setup lang="ts">
import { computed, onMounted, ref, watch, onBeforeUnmount } from 'vue';
import type { ChartData } from '@/types';

const props = defineProps<{
    data: ChartData | null;
    height?: number;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const chartHeight = computed(() => props.height || 300);
let resizeObserver: ResizeObserver | null = null;

const drawChart = () => {
    if (!canvasRef.value || !props.data) return;

    const canvas = canvasRef.value;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const { labels, datasets } = props.data;
    if (!datasets.length || !datasets[0].data.length) return;

    const data = datasets[0].data;
    const padding = 40;
    const width = canvas.width;
    const height = canvas.height;
    const chartWidth = width - padding * 2;
    const chartHeight = height - padding * 2;

    // Clear canvas
    ctx.clearRect(0, 0, width, height);

    // Get max value for scaling
    const maxValue = Math.max(...data, 1);
    const minValue = 0;
    const valueRange = maxValue - minValue || 1;

    // Calculate points
    const points = data.map((value, index) => ({
        x: padding + (index / (data.length - 1 || 1)) * chartWidth,
        y: padding + chartHeight - ((value - minValue) / valueRange) * chartHeight,
    }));

    // Draw grid lines
    ctx.strokeStyle = getComputedStyle(document.documentElement)
        .getPropertyValue('--border').trim() || '#e5e7eb';
    ctx.lineWidth = 1;
    
    for (let i = 0; i <= 4; i++) {
        const y = padding + (i / 4) * chartHeight;
        ctx.beginPath();
        ctx.moveTo(padding, y);
        ctx.lineTo(width - padding, y);
        ctx.stroke();
    }

    // Draw area fill
    ctx.fillStyle = 'rgba(99, 102, 241, 0.1)';
    ctx.beginPath();
    ctx.moveTo(points[0].x, height - padding);
    points.forEach(point => ctx.lineTo(point.x, point.y));
    ctx.lineTo(points[points.length - 1].x, height - padding);
    ctx.closePath();
    ctx.fill();

    // Draw line
    ctx.strokeStyle = 'rgb(99, 102, 241)';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.beginPath();
    points.forEach((point, index) => {
        if (index === 0) {
            ctx.moveTo(point.x, point.y);
        } else {
            ctx.lineTo(point.x, point.y);
        }
    });
    ctx.stroke();

    // Draw points
    ctx.fillStyle = 'rgb(99, 102, 241)';
    points.forEach(point => {
        ctx.beginPath();
        ctx.arc(point.x, point.y, 3, 0, Math.PI * 2);
        ctx.fill();
    });

    // Draw labels
    ctx.fillStyle = getComputedStyle(document.documentElement)
        .getPropertyValue('--muted-foreground').trim() || '#6b7280';
    ctx.font = '11px sans-serif';
    ctx.textAlign = 'center';
    
    // X-axis labels (show every 5th label to avoid crowding)
    labels.forEach((label, index) => {
        if (index % 5 === 0 || index === labels.length - 1) {
            const x = padding + (index / (data.length - 1 || 1)) * chartWidth;
            ctx.fillText(label, x, height - padding + 20);
        }
    });

    // Y-axis labels
    ctx.textAlign = 'right';
    for (let i = 0; i <= 4; i++) {
        const value = Math.round(maxValue - (i / 4) * valueRange);
        const y = padding + (i / 4) * chartHeight;
        ctx.fillText(value.toString(), padding - 10, y + 4);
    }
};

const initCanvas = () => {
    if (!canvasRef.value) return;
    
    const dpr = window.devicePixelRatio || 1;
    const rect = canvasRef.value.getBoundingClientRect();
    canvasRef.value.width = rect.width * dpr;
    canvasRef.value.height = chartHeight.value * dpr;
    const ctx = canvasRef.value.getContext('2d');
    if (ctx) {
        ctx.scale(dpr, dpr);
    }
    drawChart();
};

onMounted(() => {
    initCanvas();
    
    // Add resize observer for responsive canvas
    if (canvasRef.value) {
        resizeObserver = new ResizeObserver((entries) => {
            initCanvas();
        });
        resizeObserver.observe(canvasRef.value.parentElement!);
    }
});

onBeforeUnmount(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
});

watch(() => props.data, drawChart, { deep: true });
</script>

<template>
    <div class="w-full" :style="{ height: `${chartHeight}px` }">
        <canvas
            ref="canvasRef"
            :style="{ width: '100%', height: `${chartHeight}px` }"
            class="touch-pan-x touch-pan-y"
        />
    </div>
</template>
