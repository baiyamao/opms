<template>
        <div class="flex">
            <div v-for="(item, index) in options"
                 :key="index"
                 class="flex w-full h-full items-center">
                    <input
                        type="radio"
                        :value="item.value"
                        v-model="internalValue"
                        :disabled="isDisabled"
                        class="disabled:opacity-25"
                    />
                    <span class="pl-1">{{ item.label }}</span>
            </div>
        </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

interface OptionItem {
    value: string;
    label: string;
}

const props = defineProps({
    id: String,
    modelValue: String,
    options: Array as () => OptionItem[],
    isDisabled: Boolean,
});

const emits = defineEmits(["update:modelValue"]);

const internalValue = ref(props.modelValue);

// 监听 modelValue 的变化并更新 internalValue，解决了growData改变而radio input不改变的问题
watch(() => props.modelValue, (newVal) => {
    internalValue.value = newVal;
});

watch(internalValue, (newValue) => {
    emits('update:modelValue', newValue);
});
</script>


<style scoped>

</style>
