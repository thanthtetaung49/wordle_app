<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

// const WORD_LIST = ["REACT", "VUEJS", "WORLD", "APPLE", "SMART", "PLANT", "CLOUD", "STORM", "BREAK", "LIGHT", "FLAME", "GREAT", "PIZZA", "MUSIC", "WATER"];
const page = usePage();

const userRegistered = computed(() => !!page.props.auth.authUser);
const user = computed(() => page.props.auth.authUser);
const playerId = user.value?.id;
const playerName = user.value?.name;
const playerRole = user.value?.role;
const fetchedWords = page.props.words;

const WORD_LIST = ref(fetchedWords).value;

const formData = useForm({
    name: '',
    email: '',
    password: '',
    confirmPassword: '',
    tid: '',
    dept: '',
    status: 'inactive',
    loginTime: null,
});

const solution = ref("");
const board = ref(Array(5).fill().map(() => Array(5).fill("")));
// const board = ref([]);
const currentRowIndex = ref(0);
const currentColIndex = ref(0);
const gameState = ref("playing"); // "playing", "won", "lost"
const message = ref("");
const invalidGuess = ref(false);
const letterStates = ref({});
const isLoginMode = ref(true);
const showResultModal = ref(false); // New state for result modal

const keys = [
    ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P'],
    ['A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L'],
    ['ENTER', 'Z', 'X', 'C', 'V', 'B', 'N', 'M', 'DEL']
];

watch([board, solution, currentRowIndex, letterStates, gameState], () => {
    const gameStateSave = {
        board: board.value,
        solution: solution.value,
        currentRowIndex: currentRowIndex.value,
        letterStates: letterStates.value,
        gameState: gameState.value
    };
    localStorage.setItem('current_game_state', JSON.stringify(gameStateSave));
}, { deep: true });

const toggleLoginMode = () => {
    isLoginMode.value = !isLoginMode.value;
    formData.reset();
    formData.clearErrors();
}

const handleSubmit = () => {
    formData.status = "active";
    formData.loginTime = Date.now();

    const endpoint = isLoginMode.value ? '/login/player' : '/register/player';
    formData.post(endpoint, {
        preserveScroll: true,
        onSuccess: () => {
            window.location.href = route('home');
            formData.reset();
        }
    });
}

const resetGame = () => {
    if (WORD_LIST && WORD_LIST.length > 0) {
        const randomWordObj = WORD_LIST[Math.floor(Math.random() * WORD_LIST.length)];
        solution.value = (randomWordObj.word || randomWordObj).toUpperCase();
    }

    board.value = Array(6).fill().map(() => Array(5).fill(""));

    currentRowIndex.value = 0;
    currentColIndex.value = 0;
    gameState.value = "playing";
    message.value = "";
    letterStates.value = {};
    showResultModal.value = false;

    localStorage.removeItem('current_game_state');
};

const showMessage = (msg, duration = 2000) => {
    message.value = msg;
    if (duration > 0) setTimeout(() => message.value = "", duration);
};

const handleInput = (key) => {
    if (!userRegistered.value || gameState.value !== "playing") return;

    if (key === 'ENTER') {
        submitGuess();
    } else if (key === 'DEL' || key === 'BACKSPACE') {
        if (currentColIndex.value > 0) {
            currentColIndex.value--;
            board.value[currentRowIndex.value][currentColIndex.value] = "";
        }
    } else if (/^[A-Z]$/.test(key.toUpperCase()) && key.length === 1) {
        if (currentColIndex.value < 5) {
            board.value[currentRowIndex.value][currentColIndex.value] = key.toUpperCase();
            currentColIndex.value++;
        }
    }
};

const submitGuess = () => {
    const guess = board.value[currentRowIndex.value].join("");
    if (guess.length < 5) {
        invalidGuess.value = true;
        showMessage("Not enough letters");
        setTimeout(() => invalidGuess.value = false, 500);
        return;
    }
    processGuess(guess);
};

const processGuess = (guess) => {
    const solChars = solution.value.toUpperCase().split("");
    const guessChars = guess.split("");
    const result = Array(5).fill("absent");

    let status = 'wrong';
    if (guess === solution.value) status = 'correct';
    if (currentRowIndex.value === 4 && guess !== solution.value) status = 'lost';

    router.post(route('tracker.store'), {
        answer: guess,
        attempt_number: currentRowIndex.value + 1,
        result: status
    }, { preserveScroll: true });

    guessChars.forEach((char, i) => {
        if (char === solChars[i]) {
            result[i] = "correct";
            solChars[i] = null;
            updateKeyStatus(char, "correct");
        }
    });

    guessChars.forEach((char, i) => {
        if (result[i] !== "correct" && solChars.includes(char)) {
            result[i] = "present";
            solChars[solChars.indexOf(char)] = null;
            updateKeyStatus(char, "present");
        } else if (result[i] !== "correct") {
            updateKeyStatus(char, "absent");
        }
    });

    setTimeout(() => {
        if (guess === solution.value) {
            gameState.value = "won";
            setTimeout(() => showResultModal.value = true, 1000);
        } else if (currentRowIndex.value === 5) {
            gameState.value = "lost";
            setTimeout(() => showResultModal.value = true, 1000);
        } else {
            currentRowIndex.value++;
            currentColIndex.value = 0;
        }
    }, 100);
};

const updateKeyStatus = (char, status) => {
    const current = letterStates.value[char];
    if (current === 'correct') return;
    if (current === 'present' && status === 'absent') return;
    letterStates.value[char] = status;
};

const getCellClass = (row, col) => {
    const char = board.value[row][col];
    const isCurrentRow = row === currentRowIndex.value;

    const isGameOver = gameState.value !== 'playing';
    const isPastRow = row < currentRowIndex.value;
    const isFinishedRow = isGameOver && row === currentRowIndex.value;

    let classes = isCurrentRow && char ? "border-gray-400 scale-105" : "border-gray-700";

    if (isPastRow || isFinishedRow) {
        const status = getStatus(row, col);
        if (status === 'correct') classes = "bg-green-600 border-green-600 text-white";
        else if (status === 'present') classes = "bg-yellow-600 border-yellow-600 text-white";
        else if (status === 'absent') classes = "bg-gray-700 border-gray-700 text-white";
    }

    if (gameState.value === 'won' && row === currentRowIndex.value) classes += " bounce";

    return classes;
};

const getStatus = (row, col) => {
    const char = board.value[row][col];
    const sol = solution.value;
    if (char === sol[col]) return "correct";
    if (sol.includes(char)) return "present";
    return "absent";
};

const getKeyClass = (key) => {
    const base = "flex-1 min-w-[32px] ";
    const state = letterStates.value[key];
    if (key === 'ENTER' || key === 'DEL') return base + "px-4 bg-gray-500 text-xs text-white";
    if (state === 'correct') return base + "bg-green-600 text-white";
    if (state === 'present') return base + "bg-yellow-600 text-white";
    if (state === 'absent') return base + "bg-gray-800 text-gray-500";
    return base + "bg-gray-500 text-white";
};

const existGame = () => {
    router.get(route('player.existGame', {id: playerId}));
    localStorage.removeItem('current_game_state');
}

const onKeyDown = (e) => handleInput(e.key.toUpperCase());

onMounted(() => {
    const saved = localStorage.getItem('current_game_state');

    if (saved) {
       const parsed = JSON.parse(saved);
        board.value = parsed.board;
        solution.value = parsed.solution;
        currentRowIndex.value = parsed.currentRowIndex;
        letterStates.value = parsed.letterStates || {};
        gameState.value = parsed.gameState || "playing";
    } else {
        // Only pick a word if there is no saved game
        if (WORD_LIST && WORD_LIST.length > 0) {
            const initialWord = WORD_LIST[Math.floor(Math.random() * WORD_LIST.length)];
            solution.value = (initialWord.word || initialWord).toUpperCase();
        }
    }

    window.addEventListener('keydown', onKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
});
</script>

<template>
    <div class="min-h-screen bg-gray-900 text-white flex flex-col p-4 pt-0 font-sans select-none">

        <!-- Result Modal -->
        <div v-if="showResultModal"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div
                class="bg-gray-800 w-full max-w-sm rounded-2xl p-8 border border-gray-700 shadow-2xl text-center transform transition-all scale-110">
                <div v-if="gameState === 'won'" class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-500/20 rounded-full mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-black text-white mb-2 tracking-tight">SPLENDID!</h2>
                    <p class="text-gray-400">You guessed it in <span class="text-green-500 font-bold">{{ currentRowIndex
                        + 1 }}</span> attempts.</p>
                </div>

                <div v-else class="mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-500/20 rounded-full mb-4">
                        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-black text-white mb-2 tracking-tight">GAME OVER</h2>
                    <p class="text-gray-400">The word was: <span
                            class="text-white font-mono font-bold tracking-widest block text-xl mt-1 uppercase">{{
                                solution }}</span></p>
                </div>

                <div class="flex flex-col gap-3 mt-8">
                    <button @click="resetGame"
                        class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-4 rounded-xl transition-all active:scale-95 shadow-lg shadow-green-900/20">
                        PLAY AGAIN
                    </button>
                    <button @click="showResultModal = false"
                        class="w-full bg-gray-700 hover:bg-gray-600 text-gray-300 font-semibold py-3 rounded-xl transition-all">
                        CLOSE
                    </button>
                </div>
            </div>
        </div>

        <!-- Registration Modal (Existing) -->
        <div v-if="!userRegistered && !user"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay">
            <div class="bg-gray-800 w-full max-w-sm rounded-xl p-6 border border-gray-700 shadow-2xl">
                <h2 class="text-2xl font-bold mb-2 text-center">{{ isLoginMode ? 'Welcome Back' : 'User Registration' }}
                </h2>
                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div v-if="!isLoginMode">
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Full Name</label>
                        <input v-model="formData.name" required type="text"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 outline-none">
                        <span v-if="formData.errors.name" class="text-red-500 text-sm">{{ formData.errors.name }}</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Email Address</label>
                        <input v-model="formData.email" required type="email"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 outline-none">
                        <span v-if="formData.errors.email" class="text-red-500 text-sm">{{ formData.errors.email
                            }}</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Password</label>
                        <input v-model="formData.password" required type="password"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 outline-none">
                        <span v-if="formData.errors.password" class="text-red-500 text-sm">{{ formData.errors.password
                            }}</span>
                    </div>
                    <div v-if="!isLoginMode">
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Confirm Password</label>
                        <input v-model="formData.confirmPassword" required type="password"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 outline-none">
                        <span v-if="formData.errors.confirmPassword" class="text-red-500 text-sm">{{
                            formData.errors.confirmPassword }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div v-if="!isLoginMode">
                            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">TID</label>
                            <input v-model="formData.tid" required type="text" placeholder="T-1234"
                                class="w-full bg-gray-900 border border-gray-700 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 outline-none">

                            <span v-if="formData.errors.tid" class="text-red-500 text-sm">{{ formData.errors.tid
                            }}</span>
                        </div>

                        <div v-if="!isLoginMode">
                            <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Dept Group</label>
                            <select v-model="formData.dept" required
                                class="w-full bg-gray-900 border border-gray-700 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 outline-none">
                                <option value="" disabled>Select</option>
                                <option value="IT">IT</option>
                                <option value="Sales">Sales</option>
                                <option value="HR">HR</option>
                                <option value="Finance">Finance</option>
                                <option value="Ops">Operations</option>
                            </select>

                            <span v-if="formData.errors.dept" class="text-red-500 text-sm">{{ formData.errors.dept
                                }}</span>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg mt-4 transition-colors">
                        {{ isLoginMode ? 'START PLAYING' : 'REGISTER' }}
                    </button>
                </form>
                <div class="mt-6 text-center text-sm">
                    <span class="text-gray-400">{{ isLoginMode ? "Don't have an account?" : "Already have an account?"
                        }}</span>
                    <button @click="toggleLoginMode" class="ml-2 text-green-500 font-bold hover:underline">{{
                        isLoginMode ? 'Register' : 'Login' }}</button>
                </div>
            </div>
        </div>

        <!-- Header -->
        <header class="sticky top-0 z-40 bg-gray-900 border-b border-gray-700 py-3 mb-4 flex justify-between items-center max-w-3xl mx-auto w-full px-4">
            <h1 class="text-3xl font-black tracking-tighter">WORDLE</h1>
            <div class="flex items-center gap-3">
                <span v-if="userRegistered" class="text-[10px] text-gray-500 uppercase tracking-widest">{{ playerName
                    }}</span>
                <Link v-if="userRegistered" :href="route('player.activity', { id: playerId })"
                    class="bg-blue-900/30 text-blue-400 hover:bg-blue-900/50 px-3 py-1.5 rounded text-xs font-bold border border-blue-800/50 transition-colors">
                    {{ playerRole === 'admin' ? 'PLAYER ACTIVITIES' : 'MY ACTIVITY' }}
                </Link>
                <button @click="resetGame"
                    class="bg-gray-800 hover:bg-gray-700 px-3 py-1.5 rounded text-xs font-bold border border-gray-700">NEW
                    GAME</button>
                <button type="button" @click="existGame"
                    class="bg-gray-700 hover:bg-gray-600 hover:text-red-500 px-3 py-1 rounded text-sm">Exist Game</button>
            </div>
        </header>

        <!-- Game Board -->
        <div class="flex flex-col items-center justify-center mt-5">
            <div class="grid grid-rows-6 gap-2">
                <div v-for="(row, rowIndex) in board" :key="rowIndex" class="grid grid-cols-5 gap-2"
                    :class="{ 'shake': rowIndex === currentRowIndex && invalidGuess }">
                    <div v-for="(cell, cellIndex) in row" :key="cellIndex"
                        class="w-14 h-14 border-2 flex items-center justify-center text-3xl font-black uppercase transition-all duration-500"
                        :class="getCellClass(rowIndex, cellIndex)">
                        {{ cell }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Keyboard Section -->
        <div class="max-w-xl mx-auto w-full pb-4">
            <div v-for="(row, rIdx) in keys" :key="rIdx" class="flex justify-center gap-1.5 mb-2">
                <button v-for="key in row" :key="key" @click="handleInput(key)" :class="getKeyClass(key)"
                    class="h-14 rounded-lg font-bold flex items-center justify-center transition-all uppercase active:scale-95 shadow-sm">
                    <span v-if="key !== 'DEL'">{{ key }}</span>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.41-6.41A2 2 0 0110.83 5H21a2 2 0 012 2v10a2 2 0 01-2 2h-10.17a2 2 0 01-1.42-.59L3 12z" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes shake {

    0%,
    100% {
        transform: translateX(0);
    }

    20%,
    60% {
        transform: translateX(-5px);
    }

    40%,
    80% {
        transform: translateX(5px);
    }
}

.shake {
    animation: shake 0.5s;
}

@keyframes bounce {

    0%,
    20% {
        transform: translateY(0);
    }

    40% {
        transform: translateY(-20px);
    }

    50% {
        transform: translateY(5px);
    }

    60% {
        transform: translateY(-10px);
    }

    80% {
        transform: translateY(2px);
    }

    100% {
        transform: translateY(0);
    }
}

.bounce {
    animation: bounce 0.6s ease;
}

.modal-overlay {
    background-color: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(8px);
}
</style>
