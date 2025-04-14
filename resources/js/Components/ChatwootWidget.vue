<script setup lang="ts">
import { onMounted } from 'vue'

onMounted(() => {
    const BASE_URL = 'https://chatwoot.dqfyh.cn'

    // 监听 Chatwoot 初始化完成事件
    window.addEventListener('chatwoot:ready', () => {
        if (window.innerWidth > 20) {
            // 通过 toggle 方法展开聊天窗口
            // @ts-ignore
            window.$chatwoot.toggle('open')
        }
    })

    const script = document.createElement('script')
    script.src = `${BASE_URL}/packs/js/sdk.js`
    script.async = true
    script.defer = true

    script.onload = () => {
        // @ts-ignore 忽略 window.chatwootSDK 的类型检查
        window.chatwootSDK.run({
            websiteToken: 'ZCtG2Z5bR11NEnosLgaExRQm',
            baseUrl: BASE_URL
        })

    }

    document.head.appendChild(script)
})
</script>

<template>

</template>

<style scoped>

</style>
