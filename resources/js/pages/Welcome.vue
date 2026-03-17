<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, reactive, ref } from 'vue';

const WORD_LIST = ["REACT", "VUEJS", "WORLD", "APPLE", "SMART", "PLANT", "CLOUD", "STORM", "BREAK", "LIGHT", "FLAME", "GREAT", "PIZZA", "MUSIC", "WATER"];

// User Data & Modal State
const userRegistered = ref(false);
const formData = useForm({
    name: '',
    email: '',
    tid: '',
    dept: ''
});

const solution = ref("");
const board = ref(Array(6).fill().map(() => Array(5).fill("")));
const currentRowIndex = ref(0);
const currentColIndex = ref(0);
const gameState = ref("playing");
const message = ref("");
const invalidGuess = ref(false);
const letterStates = ref({});

const keys = [
    ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P'],
    ['A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L'],
    ['ENTER', 'Z', 'X', 'C', 'V', 'B', 'N', 'M', 'DEL']
];

const registerUser = () => {
    if (formData.name && formData.email && formData.tid && formData.dept) {
        userRegistered.value = true;

        formData.post('/register/player', {
            preserveScroll: true,
            onSuccess: () => formData.reset('dept', 'email', 'name', 'tid')
        });

        console.log('User Registered:', { ...formData, timestamp: new Date() });
    }
};

const resetGame = () => {
    solution.value = WORD_LIST[Math.floor(Math.random() * WORD_LIST.length)];
    board.value = Array(6).fill().map(() => Array(5).fill(""));
    currentRowIndex.value = 0;
    currentColIndex.value = 0;
    gameState.value = "playing";
    message.value = "";
    letterStates.value = {};
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
    const solChars = solution.value.split("");
    const guessChars = guess.split("");
    const result = Array(5).fill("absent");

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
            showMessage("Genius!", 0);
            console.log('Game Won By:', formData.name, 'Solution:', solution.value);
        } else if (currentRowIndex.value === 5) {
            gameState.value = "lost";
            showMessage(solution.value, 0);
            console.log('Game Lost By:', formData.name, 'Solution:', solution.value);
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
    const isPastRow = row < currentRowIndex.value;
    const isWinningRow = gameState.value === 'won' && row === currentRowIndex.value;

    let classes = isCurrentRow && char ? "border-gray-400 scale-105" : "border-gray-700";

    if (isPastRow || (gameState.value !== 'playing' && row === currentRowIndex.value)) {
        const status = getStatus(row, col);
        if (status === 'correct') classes = "bg-green-600 border-green-600";
        else if (status === 'present') classes = "bg-yellow-600 border-yellow-600";
        else if (status === 'absent') classes = "bg-gray-700 border-gray-700";
    }

    if (isWinningRow) classes += " bounce";

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

    if (key === 'ENTER' || key === 'DEL') return base + "px-4 bg-gray-500 text-xs";
    if (state === 'correct') return base + "bg-green-600";
    if (state === 'present') return base + "bg-yellow-600";
    if (state === 'absent') return base + "bg-gray-800 text-gray-500";
    return base + "bg-gray-500";
};

const onKeyDown = (e) => handleInput(e.key.toUpperCase());

onMounted(() => {
    resetGame();
    window.addEventListener('keydown', onKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
});

</script>

<style>
[v-cloak] {
    display: none;
}

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
    backdrop-filter: blur(4px);
}
</style>

<template>
    <!-- Registration Modal -->
    <div v-if="!userRegistered" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay">
        <div class="bg-gray-800 w-full max-w-sm rounded-xl p-6 border border-gray-700 shadow-2xl">
            <h2 class="text-2xl font-bold mb-4 text-center">User Registration</h2>
            <p class="text-gray-400 text-sm mb-6 text-center">Please enter your details to start the game.</p>

            <form @submit.prevent="registerUser" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Full Name</label>
                    <input v-model="formData.name" required type="text" placeholder="John Doe"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">Email Address</label>
                    <input v-model="formData.email" required type="email" placeholder="john@company.com"
                        class="w-full bg-gray-900 border border-gray-700 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-500 mb-1">TID</label>
                        <input v-model="formData.tid" required type="text" placeholder="T-1234"
                            class="w-full bg-gray-900 border border-gray-700 rounded-lg p-2.5 focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                    <div>
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
                    </div>
                </div>
                <button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg mt-4 transition-colors">
                    START PLAYING
                </button>
            </form>
        </div>
    </div>

    <!-- Header -->
    <header class="border-b border-gray-700 pb-2 mb-8 flex justify-between items-center">
        <h1 class="text-3xl font-bold tracking-widest">WORDLE</h1>
        <div class="flex items-center gap-3">
            <span v-if="userRegistered" class="text-xs text-gray-500 hidden sm:inline">{{ formData.name }} ({{
                formData.dept }})</span>
            <button @click="resetGame" class="bg-gray-700 hover:bg-gray-600 px-3 py-1 rounded text-sm">New Game</button>
        </div>
    </header>

    <!-- Game Board -->
    <div class="flex-grow flex items-center justify-center">
        <div class="grid grid-rows-6 gap-2">
            <div v-for="(row, rowIndex) in board" :key="rowIndex" class="grid grid-cols-5 gap-2"
                :class="{ 'shake': rowIndex === currentRowIndex && invalidGuess }">
                <div v-for="(cell, cellIndex) in row" :key="cellIndex"
                    class="w-14 h-14 border-2 flex items-center justify-center text-2xl font-bold uppercase transition-all duration-500"
                    :class="getCellClass(rowIndex, cellIndex)">
                    {{ cell }}
                </div>
            </div>
        </div>
    </div>

    <!-- Message Box -->
    <div class="h-12 flex items-center justify-center">
        <div v-if="message" class="bg-white text-black px-4 py-2 rounded font-bold shadow-lg">
            {{ message }}
        </div>
    </div>

    <!-- Keyboard -->
    <div class="mt-auto pb-4">
        <div v-for="(row, rIdx) in keys" :key="rIdx" class="flex justify-center gap-1.5 mb-2">
            <button v-for="key in row" :key="key" @click="handleInput(key)" :class="getKeyClass(key)"
                class="h-14 rounded font-bold flex items-center justify-center transition-colors uppercase">
                <span v-if="key !== 'DEL'">{{ key }}</span>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.41-6.41A2 2 0 0110.83 5H21a2 2 0 012 2v10a2 2 0 01-2 2h-10.17a2 2 0 01-1.42-.59L3 12z" />
                </svg>
            </button>
        </div>
    </div>
</template>
