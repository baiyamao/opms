<script setup lang="ts">
import { ref, computed } from 'vue';
let lastKey: string | null = null;
//使用ref保存所有输入框
const inputsRef = ref([]);
const digitsCount = 8;
const digitsArray = computed(() => Array.from({ length: digitsCount }, (_, i) => i));
const inputDigits = ref<string[]>(Array(digitsCount).fill(''));

const handleInput = (index: number, event: Event) => {
    const inputEvent = event as InputEvent;
    //替换非数字为空白
    inputDigits.value[index] = inputDigits.value[index].replace(/[^0-9]/g, "");
    const nextIndex = index + 1;
    if (nextIndex < digitsCount && (event as InputEvent).data) {
        const nextInput = inputsRef.value[nextIndex] as HTMLInputElement;
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
            const prevInput = inputsRef.value[prevIndex] as HTMLInputElement;
            if (prevInput) {
                prevInput.focus();
            }
        }
    }

    if(event.key === "ArrowLeft"){
        const prevIndex = index - 1;
        if (prevIndex >= 0) {
            const prevInput = inputsRef.value[prevIndex] as HTMLInputElement;
            if (prevInput) {
                prevInput.focus();
            }
        }
    }

    if(event.key === "ArrowRight"){
        const nextIndex = index + 1;
        if (nextIndex < digitsCount) {
            const nextInput = inputsRef.value[nextIndex] as HTMLInputElement;
            if (nextInput) {
                nextInput.focus();
            }
        }
    }
    // 如果按下的键是数字并且和上次按下的键相同，阻止输入
    if (event.key >= "0" && event.key <= "9" && lastKey === event.key) {
        event.preventDefault();
    } else if (event.key >= "0" && event.key <= "9") {
        lastKey = event.key;
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

const handleKeyup = (event: KeyboardEvent) => {
    // 当键释放时，重置lastKey
    if (event.key === lastKey) {
        lastKey = null;
    }
};

//切换输入框自动全选
// 使用 setTimeout 函数（即使延迟时间为0毫秒）将 select 调用移到JavaScript事件循环的末尾。这确保了 select 调用在浏览器完成焦点操作之后执行，从而全选输入框内容。
const handleFocus = (event: FocusEvent) => {
    const inputElement = event.target as HTMLInputElement;
    setTimeout(() => {
        inputElement.select();
    }, 0);
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
            @keyup="handleKeyup($event)"
            @paste="handlePaste($event)"
            @focus="handleFocus($event)"
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
