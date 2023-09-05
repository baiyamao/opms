<script setup lang="ts">
import { ref, computed } from 'vue';

const digitsCount = 8;
const digitsArray = computed(() => Array.from({ length: digitsCount }, (_, i) => i));
const inputDigits = ref<string[]>(Array(digitsCount).fill(''));

const handleInput = (index: number, event: InputEvent) => {
    const nextIndex = index + 1;
    if (nextIndex < digitsCount && event.data) {
        const inputRefName = `input${nextIndex}`;
        const nextInput = ref(inputRefName);
        console.log(nextInput);
        // if (nextInput.value) {
        //     nextInput.value.focus();
        // }
    }
};

const handlePaste = (event: ClipboardEvent) => {
    event.preventDefault();
    const pastedText = event.clipboardData?.getData('text/plain') || '';
    const sanitizedText = pastedText.replace(/\D/g, '').slice(0, digitsCount);

    for (let i = 0; i < digitsCount; i++) {
        inputDigits.value[i] = sanitizedText[i] || '';
    }
};
</script>

<template>
    <div>
        <input
            v-for="(digit, index) in digitsArray"
            :key="index"
            v-model="inputDigits[index]"
            @input="handleInput(index, $event)"
            @paste="handlePaste($event)"
            maxlength="1"
            :ref="`input${index}`"
            class="px-0"
        />
    </div>
</template>

<style>
input {
    width: 2em;
    text-align: center;
}
</style>
