<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>

    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
        <header class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
            <nav class="flex items-center justify-end gap-4">
                <Link v-if="$page.props.auth.user" :href="route('projects')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]">
                Projects
                </Link>
                <template v-else>
                    <Link :href="route('login')"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]">
                    Log in
                    </Link>
                    <Link :href="route('register')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]">
                    Register
                    </Link>
                </template>
            </nav>
        </header>
        <main class="flex-grow flex items-center justify-center">
            <div class="bg-white dark:bg-[#121212] rounded-xl shadow-lg max-w-3xl w-full p-10"
                style="border: 1px solid white;">
                <h1 class="text-4xl dark:text-white font-extrabold mb-4 tracking-tight drop-shadow-md text-center">
                    Uptime Monitor
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    This monitoring service allows users to create projects and define multiple monitors to regularly
                    check the
                    status and performance of websites or servers, similar to popular tools like UptimeRobot. Users can
                    set up
                    different types of monitors, including ping checks and HTTP/HTTPS website checks, with customizable
                    intervals ranging from 5 to 300 seconds. The service tracks the success or failure of each
                    monitoring task,
                    records response times, and provides detailed history views with live updates in list, calendar, and
                    graph
                    formats. Additionally, it offers status badges that visually represent the current state of each
                    monitor.
                    Built using Laravel and Livewire, the application provides a smooth, reactive user experience while
                    ensuring
                    robust backend functionality. The service supports team collaboration through shared repositories
                    and
                    ensures data persistence using databases and Docker-based deployment for easy setup and scalability.
                </p>

                <!-- Github Button -->
                <div class="mt-6 text-center">
                    <div class="flex justify-center gap-4">
                        <a href="https://github.com/sol239/uptime-monitor"
                            class="inline-flex items-center gap-2 px-4 py-2 min-w-[150px] justify-center rounded-md border border-transparent bg-[#19140035] dark:bg-[#3E3E3A] hover:bg-[#1915014a] dark:hover:bg-[#62605b] transition-colors duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 2C6.477 2 2 6.484 2 12.012c0 4.418 2.867 8.166 6.839 9.489.5.092.682-.217.682-.483 0-.237-.009-.868-.013-1.703-2.782.605-3.369-1.342-3.369-1.342-.454-1.154-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.004.07 1.532 1.032 1.532 1.032.892 1.53 2.341 1.088 2.91.832.091-.647.35-1.088.636-1.339-2.221-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.254-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.025A9.564 9.564 0 0 1 12 6.844c.85.004 1.705.115 2.504.337 1.909-1.295 2.748-1.025 2.748-1.025.546 1.378.202 2.396.099 2.65.64.7 1.028 1.595 1.028 2.688 0 3.847-2.337 4.695-4.566 4.944.359.309.678.919.678 1.852 0 1.336-.012 2.417-.012 2.747 0 .268.18.579.688.481C19.135 20.175 22 16.427 22 12.012 22 6.484 17.523 2 12 2z" />
                            </svg>
                            View on GitHub
                        </a>
                        <a href="https://webik.ms.mff.cuni.cz/nswi153/seminar-project/"
                            class="inline-flex items-center gap-2 px-4 py-2 min-w-[150px] justify-center rounded-md border border-transparent bg-emerald-600 text-white hover:bg-emerald-700 transition-colors duration-300">
                            Assignment
                        </a>
                    </div>
                </div>
            </div>
        </main>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
