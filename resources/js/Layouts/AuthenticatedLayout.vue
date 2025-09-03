<script setup>
import { computed, ref } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { Link, usePage } from "@inertiajs/vue3";

const showingNavigationDropdown = ref(false);

const page = usePage();
// Inertia page.props defined in app/Http/Middleware/HandleInertiaRequests.php
const currentUser = computed(() => page.props.auth.user);
</script>

<template>
    <div
        class="min-h-screen"
        style="
            background: linear-gradient(
                to bottom right,
                #47a663,
                #242424 20%,
                #242424 80%,
                #47a663
            ) !important;
        "
    >
        <nav
            class="bg-white dark:bg-zinc-800 border-b border-zinc-100 dark:border-zinc-700 z-50 sticky"
        >
            <!-- Primary Navigation Menu -->
            <div class="mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('pl')">
                                <ApplicationLogo
                                    class="block h-9 w-auto fill-current text-zinc-800 dark:text-zinc-200"
                                />
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div
                            class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                        >
                            <NavLink
                                :href="route('pl')"
                                :active="route().current('pl')"
                            >
                                Profit/Loss
                            </NavLink>

                            <NavLink
                                :href="route('assets')"
                                :active="route().current('assets')"
                            >
                                Assets
                            </NavLink>

                            <NavLink
                                v-if="currentUser.isAdmin"
                                :href="route('admin')"
                                :active="route().current('admin')"
                            >
                                Admin
                            </NavLink>
                        </div>
                    </div>
                    <div class="flex">
                        <div class="shrink-0 flex items-center mx-2">
                            <a
                                href="https://discord.gg/ZpvJcNWsgw"
                                target="_blank"
                            >
                                <img
                                    x="0px"
                                    y="0px"
                                    width="32"
                                    height="32"
                                    src="discord_icon.svg"
                                />
                            </a>
                        </div>
                        <div class="shrink-0 flex items-center mx-2">
                            <a
                                href="https://twitter.com/operatingcan"
                                target="_blank"
                            >
                                <img
                                    x="0px"
                                    y="0px"
                                    width="32"
                                    height="32"
                                    src="X_icon.png"
                                />
                            </a>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <!-- Settings Dropdown -->
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-800 hover:text-zinc-700 dark:hover:text-zinc-300 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                {{
                                                    $page.props.auth.user
                                                        .username
                                                }}

                                                <svg
                                                    class="ms-2 -me-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center p-2 rounded-md text-zinc-400 dark:text-zinc-500 hover:text-zinc-500 dark:hover:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 focus:outline-none focus:bg-zinc-100 dark:focus:bg-zinc-900 focus:text-zinc-500 dark:focus:text-zinc-400 transition duration-150 ease-in-out"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div
                :class="{
                    block: showingNavigationDropdown,
                    hidden: !showingNavigationDropdown,
                }"
                class="sm:hidden"
            >
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink
                        :href="route('pl')"
                        :active="route().current('pl')"
                    >
                        Profit/Loss
                    </ResponsiveNavLink>

                    <ResponsiveNavLink
                        :href="route('assets')"
                        :active="route().current('assets')"
                    >
                        Assets
                    </ResponsiveNavLink>
                </div>

                <!-- Responsive Settings Options -->
                <div
                    class="pt-4 pb-1 border-t border-zinc-200 dark:border-zinc-600"
                >
                    <div class="px-4">
                        <div
                            class="font-medium text-base text-zinc-800 dark:text-zinc-200"
                        >
                            {{ $page.props.auth.user.usename }}
                        </div>
                        <div class="font-medium text-sm text-zinc-500">
                            {{ $page.props.auth.user.username }}
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">
                            Profile
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('logout')"
                            method="post"
                            as="button"
                        >
                            Log Out
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-if="currentUser.isAdmin"
                            :href="route('admin')"
                            :active="route().current('admin')"
                        >
                            Admin
                        </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header class="white dark:bg-zinc-800 shadow" v-if="$slots.header">
            <div class="mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
