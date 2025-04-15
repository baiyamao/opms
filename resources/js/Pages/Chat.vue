<script setup lang="ts">
import ChatwootWidget from "@/Components/ChatwootWidget.vue";
import {Head} from "@inertiajs/vue3";

import { onMounted } from 'vue';

onMounted(() => {
    window.addEventListener('chatwoot:ready', () => {
        const overlay = document.getElementById('chatwoot-branding-overlay');
        if (overlay) {
            overlay.style.display = 'block';
        }
    });
});
</script>

<template>
    <Head title="眼视觉健康中心" />
    <ChatwootWidget />
    <div id="chatwoot-branding-overlay"></div>
</template>

<style>
#chatwoot-branding-overlay {
    display: none;
    position: fixed;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 50%; /* 根据实际需要调整宽度 */
    height: 30px;  /* 根据实际需要调整高度 */
    background-color: #f9f9fb; /* 或者使用页面背景色 */
    z-index: 9999999999; /* 确保遮罩层位于最上层 */
    //pointer-events: none; /* 允许点击事件穿透遮罩层 */
}
</style>
