<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <Head title="Reset Password" />

        <div class="bg-[#1f2937] w-full max-w-sm rounded-xl p-8 border border-gray-700 shadow-2xl">
            <h2 class="text-2xl font-bold mb-6 text-center text-white">New Password</h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Email Address</label>
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        readonly
                        class="w-full bg-[#111827] border border-gray-700 rounded-lg p-2.5 text-gray-400 cursor-not-allowed outline-none"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">New Password</label>
                    <div class="relative">
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            v-model="form.password"
                            required
                            autofocus
                            placeholder="••••••••"
                            class="w-full bg-[#111827] border border-gray-700 rounded-lg p-2.5 pr-10 text-white focus:ring-2 focus:ring-green-500 outline-none transition-all"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300"
                        >
                            <Eye v-if="!showPassword" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password" class="mt-1" />
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Confirm Password</label>
                    <div class="relative">
                        <input
                            id="password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            v-model="form.password_confirmation"
                            required
                            placeholder="••••••••"
                            class="w-full bg-[#111827] border border-gray-700 rounded-lg p-2.5 pr-10 text-white focus:ring-2 focus:ring-green-500 outline-none transition-all"
                        />
                        <button
                            type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300"
                        >
                            <Eye v-if="!showConfirmPassword" class="h-4 w-4" />
                            <EyeOff v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" class="mt-1" />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-[#16a34a] hover:bg-[#15803d] text-white font-bold py-3 rounded-lg mt-6 transition-colors flex items-center justify-center gap-2"
                >
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    UPDATE PASSWORD
                </button>
            </form>
        </div>
    </div>
</template>
