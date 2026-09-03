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
    () => (url: string) => (isCurrentRoute.value(url) ? 'text-yellow-400 dark:bg-white/5' : ''),
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
            <Link :href="route('dashboard')" class="flex items-center gap-x-2.5 flex-shrink-0">
                <img src="/Logo pemira.png" alt="Logo PEMIRA" class="size-10 object-contain" onerror="this.style.display='none'" />
                <div class="hidden sm:block leading-tight">
                    <p class="text-[10px] text-yellow-400/60 font-medium uppercase tracking-widest leading-none">Pemilihan Umum Raya</p>
                    <p class="text-sm font-bold text-yellow-400 leading-tight">FMIPA {{ dayjs().year() }}</p>
                </div>
            </Link>

            <!-- Desktop Admin Nav -->
            <div v-if="auth.user && (auth.user.is_admin === true || auth.user.is_admin === 1)" class="hidden h-full lg:flex lg:flex-1">
                <NavigationMenu class="ml-6 flex h-full items-stretch">
                    <NavigationMenuList class="flex h-full items-stretch space-x-1">
                        <NavigationMenuItem v-for="(item, index) in mainNavItems" :key="index"
                            class="relative flex h-full items-center">
                            <Link
                                :class="[navigationMenuTriggerStyle(), activeItemStyles(item.href), 'h-9 cursor-pointer px-3 text-white/70 hover:text-yellow-400 hover:bg-white/5 bg-transparent']"
                                :href="item.href">
                            {{ item.title }}
                            </Link>
                            <div v-if="isCurrentRoute(item.href)"
                                class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-yellow-400">
                            </div>
                        </NavigationMenuItem>
                    </NavigationMenuList>
                </NavigationMenu>
            </div>

            <!-- Spacer -->
            <div class="flex-1" />

            <!-- Right: NIM badge + User menu -->
            <div class="flex items-center gap-3">
                <!-- NIM Badge (logged in users) -->
                <div v-if="auth.user" class="hidden sm:flex items-center gap-1.5 bg-yellow-400/10 border border-yellow-400/30 rounded-full px-3 py-1">
                    <div class="size-1.5 rounded-full bg-yellow-400 animate-pulse"></div>
                    <span class="text-xs font-semibold text-yellow-400">NIM: {{ auth.user.nim }}</span>
                </div>

                <!-- User Dropdown -->
                <DropdownMenu v-if="auth.user">
                    <DropdownMenuTrigger :as-child="true">
                        <Button variant="ghost" size="icon"
                            class="relative size-10 w-auto rounded-full py-1 px-1 sm:px-2 hover:bg-white/5 focus-within:ring-2 focus-within:ring-yellow-400/50">
                            <Avatar class="size-8 overflow-hidden rounded-full border border-yellow-400/30">
                                <AvatarImage v-if="auth.user.avatar" :src="`/storage/${auth.user.avatar}`"
                                    :alt="auth.user.nama" />
                                <AvatarFallback
                                    class="rounded-lg bg-yellow-400/10 font-semibold text-yellow-400">
                                    {{ getInitials(auth.user?.nama) }}
                                </AvatarFallback>
                            </Avatar>
                            <span class="hidden sm:flex text-white/80 text-sm">{{ auth.user.nama }}</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56 bg-[#1a1f4a] border-yellow-900/30">
                        <UserMenuContent :user="auth.user" />
                    </DropdownMenuContent>
                </DropdownMenu>

                <!-- Guest buttons -->
                <div v-else class="flex gap-2">
                    <Link :href="route('login')">
                        <Button type="button" size="sm"
                            class="btn-gold text-xs font-bold px-4 rounded-full">
                            ➜ Masuk Pemilih
                        </Button>
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
