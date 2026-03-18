<script setup>
import { usePage, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const page = usePage();
const props = defineProps(['activityLogs', 'filters', 'users'])
const isRefreshing = ref(false);
const activityLogs = computed(() => props.activityLogs.data);

const userIdFilter = ref(props.filters.user_id || 'all');
const search = ref(props.filters.search || '');
const resultFilter = ref(props.filters.result || 'all');

const refreshData = () => {
    isRefreshing.value = true;

    // router.get(route('player.activity'), {
    //     only: ['activityLogs'],
    //     onFinish: () => {
    //         isRefreshing.value = false;
    //     }
    // });
    router.reload({
        only: ['activityLogs'],
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
};

watch([search, resultFilter, userIdFilter], debounce(([newSearch, newResult, newUser]) => {
    router.get(route('player.activity'), {
        search: newSearch,
        result: newResult,
        user_id: newUser
    }, {
        preserveState: true,
        replace: true,
        only: ['activityLogs']
    });
}, 300));
</script>

<template>
    <div class="max-w-5xl mx-auto py-12 px-6">

        <div class="flex items-center justify-between mb-8">
            <Link href="/"
                class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-white transition-colors group">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2"
                    class="transform group-hover:-translate-x-1 transition-transform">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Back
            </Link>

            <button @click="refreshData" :disabled="isRefreshing"
                class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-white transition-colors group">
                <span class="text-xs uppercase tracking-widest font-bold">
                    {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
                </span>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2" :class="{ 'animate-spin text-green-500': isRefreshing }">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>

        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-white tracking-tight">Activity History</h1>
            <p class="text-gray-500 text-sm mt-1">Real-time performance logs for all active players.</p>
        </div>

        <div class="flex flex-col md:flex-row gap-4 mb-6 max-w-lg">
            <div class="relative flex-grow">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="2" />
                    </svg>
                </span>
                <input v-model="search" type="text" placeholder="Search by player or word..."
                    class="block w-full pl-10 pr-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm text-gray-300 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all" />
            </div>

            <select v-model="resultFilter"
                class="bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-sm text-gray-300 outline-none focus:ring-1 focus:ring-blue-500 cursor-pointer">
                <option value="all">All Results</option>
                <option value="correct">Success</option>
                <option value="wrong">Failed</option>
            </select>

            <select v-if="props.users" v-model="userIdFilter"
                class="bg-gray-900 border border-gray-700 rounded-lg px-4 py-2 text-sm text-gray-300 outline-none focus:ring-1 focus:ring-blue-500">
                <option value="all">All Players</option>
                <option v-for="u in props.users" :key="u.id" :value="u.id">
                    {{ u.name }}
                </option>
            </select>

        </div>



        <div class="bg-gray-900/40 border border-gray-800 rounded-xl overflow-hidden backdrop-blur-sm shadow-xl py-10">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="border-b border-gray-800 text-gray-500 text-[11px] uppercase tracking-[0.1em] font-bold">
                        <th class="px-6 py-4">Player</th>
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">Word</th>
                        <th class="px-6 py-4 text-center">Attempts</th>
                        <th class="px-6 py-4 text-right">Status</th>
                    </tr>
                </thead>
                <tbody v-if="activityLogs.length > 0" class="divide-y divide-gray-800/50">
                    <tr v-for="log in activityLogs" :key="log.id" class="hover:bg-white/[0.02] transition-colors">
                        <td class="px-6 py-4 text-white font-medium">
                            {{ log.user.name }}
                        </td>
                        <td class="px-6 py-4 text-gray-500">
                            {{ log.activity_date }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono font-bold text-white tracking-widest uppercase">
                                {{ log.answer }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-gray-200">{{ log.attempt_number }}</span><span class="text-gray-600"> /
                                6</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <span :class="log.result === 'correct' ? 'text-green-500' : 'text-red-400'"
                                    class="text-[10px] font-black uppercase tracking-widest">
                                    {{ log.result === 'correct' ? 'Success' : 'Failed' }}
                                </span>
                                <div class="w-1.5 h-1.5 rounded-full shadow-[0_0_8px_currentColor]"
                                    :class="log.result === 'correct' ? 'bg-green-500 text-green-500' : 'bg-red-500 text-red-500'">
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr>
                        <td colspan="5" class="px-6 py-20 text-center text-gray-500 italic">
                            No activity logs found matching your criteria.
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-6 flex items-center justify-between px-2">
                <div class="text-xs text-gray-500 font-medium uppercase tracking-widest">
                    Showing {{ page.props.activityLogs.from }}-{{ page.props.activityLogs.to }}
                    of {{ page.props.activityLogs.total }}
                </div>

                <div class="flex gap-2">
                    <Link v-for="link in page.props.activityLogs.links" :key="link.label" :href="link.url || '#'"
                        v-html="link.label" class="px-3 py-1.5 rounded text-xs font-bold transition-all border" :class="[
                            link.active ? 'bg-blue-600 border-blue-500 text-white' : 'bg-gray-800 border-gray-700 text-gray-400 hover:text-white',
                            !link.url ? 'opacity-30 cursor-not-allowed' : ''
                        ]" :disabled="!link.url" preserve-scroll />
                </div>
            </div>
        </div>
    </div>
</template>
