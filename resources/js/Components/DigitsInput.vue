<script setup lang="ts">
import { ref, computed } from 'vue';

//使用ref保存所有输入框
const inputsRef = ref([]);
const digitsCount = 8;
const digitsArray = computed(() => Array.from({ length: digitsCount }, (_, i) => i));
const inputDigits = ref<string[]>(Array(digitsCount).fill(''));

const handleInput = (index: number, event: InputEvent) => {
    //替换非数字为空白
    inputDigits.value[index] = inputDigits.value[index].replace(/[^0-9]/g, "");
    const nextIndex = index + 1;
    if (nextIndex < digitsCount && event.data) {
        const nextInput = inputsRef.value[nextIndex];
        if (nextInput) {
            nextInput.focus();
        }
    }
    console.log(inputDigits.value);
};

const handleKeydown = (index: number, event: KeyboardEvent) => {
    // console.log("按下的按键是：\""+event.key+"\","+inputDigits.value[index]);
    //阻止非数字键入
    const validKeys = [
        "Backspace", "ArrowLeft", "ArrowRight", "Tab", "Delete"
    ];
    if (
        !validKeys.includes(event.key) &&
        (event.key < "0" || event.key > "9")
    ) {
        event.preventDefault();
    }
    if (event.key === "Backspace" && inputDigits.value[index] == "") {
        const prevIndex = index - 1;
        if (prevIndex >= 0) {
            const prevInput = inputsRef.value[prevIndex];
            if (prevInput) {
                prevInput.focus();
            }
        }
    }

    if(event.key === "ArrowLeft"){
        const prevIndex = index - 1;
        if (prevIndex >= 0) {
            const prevInput = inputsRef.value[prevIndex];
            if (prevInput) {
                prevInput.focus();
            }
        }
    }

    if(event.key === "ArrowRight"){
        const nextIndex = index + 1;
        if (nextIndex < digitsCount) {
            const nextInput = inputsRef.value[nextIndex];
            if (nextInput) {
                nextInput.focus();
            }
        }
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
            @keydown="handleKeydown(index, $event)"
            @paste="handlePaste($event)"
            maxlength="1"
            ref="inputsRef"
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
