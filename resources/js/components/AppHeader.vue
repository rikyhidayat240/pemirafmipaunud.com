<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { NavigationMenu, NavigationMenuItem, NavigationMenuList, navigationMenuTriggerStyle } from '@/components/ui/navigation-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import type { BreadcrumbItem, NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Home, Menu, Calendar, UserCheck, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import dayjs from 'dayjs';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);

const isCurrentRoute = computed(() => (url: string) => page.url === url);

const activeItemStyles = computed(
    () => (url: string) => (isCurrentRoute.value(url) ? 'text-white font-bold' : 'text-white/60 hover:text-white'),
);

const mainNavItems: NavItem[] = [
    {
        title: 'Beranda',
        href: '/dashboard',
        icon: Home,
    },
    {
        title: 'Kandidat',
        href: '/candidates',
        icon: UserCheck,
    },
    {
        title: 'Hasil BEM',
        href: '/result-bem',
        icon: UserCheck,
    },
    {
        title: 'Hasil HIMA',
        href: '/result-hima',
        icon: Users,
    },
    {
        title: 'Kegiatan',
        href: '/events',
        icon: Calendar,
    },
    {
        title: 'Mahasiswa',
        href: '/users',
        icon: Users,
    },
];
</script>

<template>
    <div class="sticky top-0 z-50 pemira-header">
        <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl gap-3">
            <!-- Mobile Menu (admin only) -->
            <div v-if="auth.user && (auth.user.is_admin === true || auth.user.is_admin === 1)" class="lg:hidden">
                <Sheet>
                    <SheetTrigger :as-child="true">
                        <Button variant="ghost" size="icon" class="mr-2 h-9 w-9 text-white/70 hover:text-yellow-400 hover:bg-white/5">
                            <Menu class="h-5 w-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-[300px] p-6 bg-[#0f1229] border-yellow-900/30">
                        <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
                        <SheetHeader class="flex justify-start text-left">
                            <div class="flex justify-start items-center gap-2 text-left">
                                <AppLogoIcon class="size-6 fill-current text-yellow-400" />
                                <div>
                                    <p class="text-xs text-yellow-400/70 font-medium uppercase tracking-widest">Pemilihan Umum Raya</p>
                                    <p class="text-sm font-bold text-yellow-400">FMIPA {{ dayjs().year() }}</p>
                                </div>
                            </div>
                        </SheetHeader>
                        <div class="flex h-full flex-1 flex-col justify-between space-y-4 py-4">
                            <nav class="-mx-3 space-y-1">
                                <Link v-for="item in mainNavItems" :key="item.title" :href="item.href"
                                    class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium text-white/70 hover:bg-white/5 hover:text-yellow-400 transition-colors"
                                    :class="activeItemStyles(item.href)">
                                <component v-if="item.icon" :is="item.icon" class="h-5 w-5" />
                                {{ item.title }}
                                </Link>
                            </nav>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>

            <!-- Logo Left -->
            <Link :href="route('dashboard')" class="flex items-center gap-x-3 flex-shrink-0">
                <div class="relative flex items-center justify-center size-10 rounded-full bg-[#202336] border border-yellow-500/30 shadow-md">
                    <img src="/Logo pemira.png" alt="Logo PEMIRA" class="size-7 object-contain" onerror="this.style.display='none'" />
                </div>
                <div class="hidden sm:block leading-tight border-l border-white/10 pl-3">
                    <p class="text-[9px] text-yellow-400 font-bold uppercase tracking-widest leading-none">Pemilihan Umum Raya</p>
                    <p class="text-base font-black text-white tracking-wider leading-tight poppins-font">FMIPA <span class="text-yellow-400">{{ dayjs().year() }}</span></p>
                </div>
            </Link>

            <!-- Desktop Admin Nav -->
            <div v-if="auth.user && (auth.user.is_admin === true || auth.user.is_admin === 1)" class="hidden h-full lg:flex lg:flex-1 justify-center">
                <NavigationMenu class="flex h-full items-stretch">
                    <NavigationMenuList class="flex h-full items-stretch space-x-2">
                        <NavigationMenuItem v-for="(item, index) in mainNavItems" :key="index"
                            class="relative flex h-full items-center">
                            <Link
                                :class="[
                                    'h-full flex items-center px-4 text-xs font-semibold tracking-wide transition-colors bg-transparent',
                                    isCurrentRoute(item.href) ? 'text-white font-bold' : 'text-white/60 hover:text-white'
                                ]"
                                :href="item.href">
                            {{ item.title }}
                            </Link>
                            <div v-if="isCurrentRoute(item.href)"
                                class="absolute bottom-0 left-0 h-[3px] w-full bg-yellow-400 rounded-t-full shadow-[0_-2px_8px_rgba(240,192,64,0.8)]">
                            </div>
                        </NavigationMenuItem>
                    </NavigationMenuList>
                </NavigationMenu>
            </div>

            <!-- Spacer -->
            <div class="flex-1" />

            <!-- Right: NIM badge + User menu -->
            <div class="flex items-center gap-3">
                <!-- User Dropdown -->
                <DropdownMenu v-if="auth.user">
                    <DropdownMenuTrigger :as-child="true">
                        <Button variant="ghost"
                            class="relative flex items-center gap-3 h-auto py-1 px-2 rounded-full hover:bg-white/5 focus-within:ring-0 focus:ring-0">
                            <Avatar class="size-9 border-2 border-yellow-500/50 shadow-md">
                                <AvatarImage v-if="auth.user.avatar" :src="`/storage/${auth.user.avatar}`"
                                    :alt="auth.user.nama" />
                                <AvatarFallback
                                    class="bg-[#3a3525] font-bold text-yellow-400 text-xs">
                                    {{ getInitials(auth.user?.nama) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="hidden sm:flex flex-col text-left leading-none">
                                <span class="text-white text-xs font-bold">{{ auth.user.nama }}</span>
                                <span class="text-[10px] text-yellow-400/80 font-semibold mt-0.5">{{ auth.user.is_admin ? 'Admin' : 'Pemilih' }}</span>
                            </div>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56 bg-[#1a1f4a] border-yellow-900/30">
                        <UserMenuContent :user="auth.user" />
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- Guest buttons -->
                <div v-else class="flex gap-2">
                    <Link :href="route('login')">
                        <button class="btn-gold !text-xs font-bold !px-5 !py-2">
                            ➜ Masuk Pemilih
                        </button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Breadcrumbs -->
        <div v-if="props.breadcrumbs.length > 1" class="flex w-full border-b border-yellow-900/20">
            <div class="mx-auto flex h-10 w-full items-center justify-start px-4 text-white/40 md:max-w-7xl text-sm">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
