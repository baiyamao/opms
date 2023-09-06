<template>
    <div class="loader"></div>
</template>

<script>
export default {
    name: 'LoadingSpinner'
}
</script>

<style scoped>
@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

.loader {
    border: 4px solid #f3f3f3;  /* 灰色背景 */
    border-top: 4px solid #3498db; /* 蓝色 */
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
}
</style>
