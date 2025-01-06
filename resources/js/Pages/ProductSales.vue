<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {ref} from "vue";
const dateValue = ref([]);
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM',
})
</script>

<template>
    <Head title="产品销售" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">产品销售</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100 w-96">
                        <vue-tailwind-datepicker
                            v-model="dateValue"
                            as-single
                            :formatter="formatter"
                            i18n="zh-cn"

                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
