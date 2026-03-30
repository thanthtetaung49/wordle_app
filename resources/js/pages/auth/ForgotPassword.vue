<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <Head title="Forgot Password" />

        <div class="bg-[#1f2937] w-full max-w-sm rounded-xl p-8 border border-gray-700 shadow-2xl">
            <h2 class="text-2xl font-bold mb-6 text-center text-white">Forgot Password</h2>

            <p class="text-gray-400 text-sm mb-6 text-center">
                Enter your email address and we'll send you a link to reset your password.
            </p>

            <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-500">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Email Address</label>
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        class="w-full bg-[#111827] border border-gray-700 rounded-lg p-2.5 text-white focus:ring-2 focus:ring-green-500 outline-none transition-all"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-[#16a34a] hover:bg-[#15803d] text-white font-bold py-3 rounded-lg mt-4 transition-colors flex items-center justify-center gap-2"
                >
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    SEND RESET LINK
                </button>
            </form>

            <div class="mt-6 text-center text-sm">
                <span class="text-gray-400">Remembered your password?</span>
                <Link
                    :href="route('login')"
                    class="ml-2 text-green-500 font-bold hover:underline uppercase"
                >
                    Login
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-overlay {
    background: rgba(0, 0, 0, 0.8);
}
</style>
