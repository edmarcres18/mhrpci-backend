<script setup lang="ts">
import { login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { useSiteSettings } from '@/composables/useSiteSettings';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const mobileMenuOpen = ref(false);
const { siteSettings } = useSiteSettings();
const currentYear = new Date().getFullYear();

const activeCard = ref(0);
const cursorPosition = ref({ x: 50, y: 30 });
const isClicking = ref(false);
const currentAction = ref('');
const actionProgress = ref(0);

const cardPositions = [
  { x: 28, y: 45 },
  { x: 72, y: 45 },
  { x: 28, y: 70 },
  { x: 72, y: 70 },
];

const managementSections = ref([
  {
    title: 'IT Inventories',
    count: 128,
    icon: 'shopping',
    color: 'purple',
    action: 'Adding inventory item...',
    subAction: 'Inventory "MRI Machine" saved',
  },
  {
    title: 'Users',
    count: 156,
    icon: 'users',
    color: 'purple',
    action: 'Managing users...',
    subAction: 'User permissions updated',
  },
  {
    title: 'Site Info',
    count: 1,
    icon: 'blog',
    color: 'purple',
    action: 'Updating site information...',
    subAction: 'Address details saved',
  },
  {
    title: 'Database',
    count: 99,
    icon: 'database',
    color: 'purple',
    action: 'Scheduling backup...',
    subAction: 'Backup scheduled for 2:00 AM',
  },
]);

let cardInterval: number | null = null;

const moveCursorToCard = (cardIndex: number) => {
  const targetPos = cardPositions[cardIndex];
  const startPos = { ...cursorPosition.value };
  const steps = 20;
  let currentStep = 0;

  const moveInterval = setInterval(() => {
    currentStep++;
    const progress = currentStep / steps;
    const easeProgress = 1 - Math.pow(1 - progress, 3);

    cursorPosition.value = {
      x: startPos.x + (targetPos.x - startPos.x) * easeProgress,
      y: startPos.y + (targetPos.y - startPos.y) * easeProgress,
    };

    if (currentStep >= steps) {
      clearInterval(moveInterval);
      isClicking.value = true;
      setTimeout(() => {
        isClicking.value = false;
      }, 200);
    }
  }, 30);
};

const startActionAnimation = () => {
  actionProgress.value = 0;
  currentAction.value = managementSections.value[activeCard.value].action;

  const progressInterval = setInterval(() => {
    actionProgress.value += 5;
    if (actionProgress.value >= 100) {
      clearInterval(progressInterval);
      currentAction.value = managementSections.value[activeCard.value].subAction;
      if (managementSections.value[activeCard.value].icon !== 'database') {
        managementSections.value[activeCard.value].count++;
      }
      setTimeout(() => {
        currentAction.value = '';
      }, 1500);
    }
  }, 50);
};

const startAnimations = () => {
  setTimeout(() => {
    moveCursorToCard(0);
    startActionAnimation();
  }, 500);

  cardInterval = window.setInterval(() => {
    const nextCard = (activeCard.value + 1) % managementSections.value.length;
    activeCard.value = nextCard;
    moveCursorToCard(nextCard);
    startActionAnimation();
  }, 4000);
};

const stopAnimations = () => {
  if (cardInterval) clearInterval(cardInterval);
};

onMounted(() => {
  startAnimations();
});

onUnmounted(() => {
  stopAnimations();
});
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    </Head>
    <div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-purple-50">
        <nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm shadow-sm border-b border-purple-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16 md:h-20">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-white rounded-lg flex items-center justify-center shadow-lg border border-neutral-200">
                            <img v-if="siteSettings.site_logo" :src="siteSettings.site_logo" :alt="siteSettings.site_name" class="h-8 w-8 md:h-10 md:w-10 object-contain" />
                            <AppLogoIcon v-else class="w-6 h-6 md:w-7 md:h-7 fill-current text-neutral-900" />
                        </div>
                        <div>
                            <span class="text-lg md:text-xl font-bold text-purple-900 block leading-tight">{{ siteSettings.site_name }}</span>
                            <span class="text-xs text-purple-600 font-medium hidden sm:block">Inventory Management Panel</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <Link :href="login()" class="inline-flex items-center px-4 md:px-6 py-2 md:py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transition-all transform hover:scale-105">
                            <svg class="w-4 h-4 md:w-5 md:h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            <span>Login</span>
                        </Link>
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-blue-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <section class="pt-24 md:pt-32 pb-12 md:pb-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid lg:grid-cols-2 gap-8 md:gap-12 items-center">
                    <div class="space-y-6 md:space-y-8 text-center lg:text-left">
                        <div class="inline-flex items-center px-4 py-2 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z" />
                                <path d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z" />
                                <path d="M17 5c0 1.657-3.134 3-7 3S3 6.657 3 5s3.134-3 7-3 7 1.343 7 3z" />
                            </svg>
                            PCI Backend Management
                        </div>
                        <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-gray-900 leading-tight">
                            Backend Database
                            <span class="text-purple-600 block mt-2">IT Inventory & Site Control</span>
                        </h1>
                        <p class="text-lg md:text-xl text-gray-600 max-w-2xl mx-auto lg:mx-0">
                            Manage IT inventories, users, site information, and system backups for MHR Property Conglomerate Inc. through a robust administrative interface.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <Link :href="login()" class="inline-flex items-center justify-center px-8 py-4 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
                                Access Dashboard
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                    <div class="relative lg:mt-0 mt-8">
                        <div class="relative bg-gradient-to-br from-purple-100 to-purple-200 rounded-3xl p-6 md:p-8 shadow-2xl overflow-hidden">
                            <div
                                class="absolute pointer-events-none transition-all duration-75 z-20"
                                :style="{ left: `${cursorPosition.x}%`, top: `${cursorPosition.y}%`, transform: 'translate(-50%, -50%)' }"
                            >
                                <div class="relative">
                                    <svg class="w-6 h-6 text-gray-800 drop-shadow-lg transition-transform duration-200" :class="isClicking ? 'scale-90' : 'scale-100'" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M5 3l3.057 13.573L10.5 14.5l2.073 2.073L23 5z" />
                                    </svg>
                                    <div v-if="isClicking" class="absolute top-0 left-0 w-8 h-8 -translate-x-1/4 -translate-y-1/4 bg-purple-400 rounded-full opacity-50 animate-ping"></div>
                                </div>
                            </div>

                            <transition name="slide-down">
                                <div v-if="currentAction" class="absolute top-4 left-1/2 -translate-x-1/2 bg-white px-4 py-2 rounded-lg shadow-lg border-2 border-purple-500 z-30 min-w-[200px]">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                        <p class="text-sm font-semibold text-gray-800">{{ currentAction }}</p>
                                    </div>
                                    <div v-if="actionProgress < 100" class="mt-2 h-1 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-purple-500 rounded-full transition-all duration-100" :style="{ width: `${actionProgress}%` }"></div>
                                    </div>
                                </div>
                            </transition>

                            <div class="bg-white rounded-2xl p-6 md:p-8 shadow-lg relative">
                                <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-purple-700 rounded-lg flex items-center justify-center shadow-md animate-pulse">
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z" />
                                                <path d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z" />
                                                <path d="M17 5c0 1.657-3.134 3-7 3S3 6.657 3 5s3.134-3 7-3 7 1.343 7 3z" />
                                            </svg>
                                        </div>
                                        <div class="space-y-1">
                                            <div class="h-3 w-32 bg-gradient-to-r from-gray-300 to-gray-200 rounded animate-pulse"></div>
                                            <div class="h-2 w-20 bg-gradient-to-r from-gray-200 to-gray-100 rounded animate-pulse" style="animation-delay: 0.2s"></div>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <div class="w-3 h-3 bg-red-400 rounded-full shadow-sm"></div>
                                        <div class="w-3 h-3 bg-yellow-400 rounded-full shadow-sm"></div>
                                        <div class="w-3 h-3 bg-green-400 rounded-full shadow-sm"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mt-6">
                                    <div
                                        v-for="(section, index) in managementSections"
                                        :key="section.title"
                                        class="rounded-xl p-4 space-y-2 transition-all duration-500 cursor-pointer relative overflow-hidden"
                                        :class="[
                                            activeCard === index ? `bg-${section.color}-50 ring-2 ring-${section.color}-400 scale-105 shadow-lg` : 'bg-gray-50 hover:bg-gray-100',
                                        ]"
                                    >
                                        <div v-if="activeCard === index" class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-30" style="animation: shimmer 2s infinite; transform: translateX(-100%)"></div>

                                        <div class="flex items-center justify-between relative z-10">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center shadow-md transition-transform duration-300" :class="[activeCard === index ? `bg-${section.color}-600 scale-110` : 'bg-gray-400']">
                                                <svg v-if="section.icon === 'shopping'" class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z" />
                                                </svg>
                                                <svg v-else-if="section.icon === 'blog'" class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd" />
                                                </svg>
                                                <svg v-else-if="section.icon === 'users'" class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                                </svg>
                                                <svg v-else-if="section.icon === 'database'" class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z" />
                                                    <path d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z" />
                                                </svg>
                                            </div>
                                            <div class="text-2xl font-bold transition-colors duration-300" :class="activeCard === index ? `text-${section.color}-600` : 'text-gray-400'">
                                                {{ section.icon === 'database' ? section.count + '%' : section.count }}
                                            </div>
                                        </div>
                                        <div class="relative z-10">
                                            <div class="text-sm font-semibold transition-colors duration-300" :class="activeCard === index ? `text-${section.color}-700` : 'text-gray-500'">
                                                {{ section.title }}
                                            </div>
                                            <div class="h-1 bg-gray-200 rounded-full mt-2 overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-1000" :class="`bg-${section.color}-500`" :style="{ width: activeCard === index ? '100%' : '0%' }"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3 mt-6">
                                    <div v-for="i in 3" :key="i" class="h-3 bg-gradient-to-r from-gray-200 via-gray-100 to-gray-200 rounded-full transition-all duration-500" :class="activeCard >= i - 1 ? 'opacity-100' : 'opacity-30'" :style="{ width: i === 1 ? '100%' : i === 2 ? '85%' : '70%', animationDelay: `${i * 0.2}s` }"></div>
                                </div>

                                <div class="mt-6 flex justify-end space-x-2">
                                    <div v-for="i in 2" :key="i" class="px-4 py-2 rounded-lg transition-all duration-300" :class="activeCard % 2 === i - 1 ? 'bg-purple-600 shadow-lg scale-105' : 'bg-gray-200'">
                                        <div class="h-2 w-12 bg-white/50 rounded"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-16 grid md:grid-cols-3 gap-6">
                    <div class="rounded-xl border border-purple-100 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 3a2 2 0 012-2h8a2 2 0 012 2v3H4V3zM4 9h12v4a2 2 0 01-2 2H6a2 2 0 01-2-2V9z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-purple-900">IT Inventory Control</h3>
                        </div>
                        <p class="mt-3 text-sm text-gray-600">Import, export, and manage inventory records with ease.</p>
                    </div>
                    <div class="rounded-xl border border-purple-100 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-purple-900">User Management</h3>
                        </div>
                        <p class="mt-3 text-sm text-gray-600">Role-based access for System Admin, Admin, and Staff.</p>
                    </div>
                    <div class="rounded-xl border border-purple-100 bg-white p-6 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 2a8 8 0 100 16A8 8 0 0010 2zm1 11H9V9h2v4z" />
                                </svg>
                            </div>
                            <h3 class="font-semibold text-purple-900">Backup & Restore</h3>
                        </div>
                        <p class="mt-3 text-sm text-gray-600">Schedule automated backups and download securely.</p>
                    </div>
                </div>

                <div class="mt-16">
                    <div class="rounded-2xl bg-gradient-to-r from-purple-600 to-purple-500 p-8 text-white shadow-lg">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                            <div>
                                <h3 class="text-2xl font-bold">Ready to manage your backend?</h3>
                                <p class="mt-1 text-purple-100">Access the dashboard and start controlling your data.</p>
                            </div>
                            <Link :href="login()" class="inline-flex items-center justify-center px-8 py-4 bg-white text-purple-700 font-bold rounded-xl shadow hover:bg-purple-50">
                                Go to Dashboard
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer class="mt-12 border-t border-purple-100 bg-white/80 backdrop-blur">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-sm text-gray-600 flex justify-between items-center">
                <span>&copy; {{ currentYear }} {{ siteSettings.site_name }}</span>
                <span>Powered by Laravel 12 & Vue 3</span>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.slide-down-enter-active,
.slide-down-leave-active { transition: all 0.3s ease; }
.slide-down-enter-from { opacity: 0; transform: translateX(-50%) translateY(-10px); }
.slide-down-leave-to { opacity: 0; transform: translateX(-50%) translateY(-10px); }
.slide-down-enter-to,
.slide-down-leave-from { opacity: 1; transform: translateX(-50%) translateY(0); }

/* Ensure dynamic purple classes compile with Tailwind */
.bg-purple-50 { background-color: rgb(250 245 255); }
.ring-purple-400 { --tw-ring-color: rgb(192 132 252); }
.bg-purple-600 { background-color: rgb(147 51 234); }
.text-purple-600 { color: rgb(147 51 234); }
.text-purple-700 { color: rgb(126 34 206); }
.bg-purple-500 { background-color: rgb(168 85 247); }
.bg-purple-700 { background-color: rgb(126 34 206); }
.bg-purple-100 { background-color: rgb(243 232 255); }
.border-purple-100 { border-color: rgb(243 232 255); }
.hover\:bg-purple-50:hover { background-color: rgb(243 232 255); }
</style>
