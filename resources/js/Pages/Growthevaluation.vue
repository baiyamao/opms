<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head , useForm } from '@inertiajs/vue3';
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {ref} from "vue";
import axios from "axios";

const growthData = ref({
    birthday:'2021-1-1',
    gender:'boy',
    height_type:'height',
    height:'100',
    weight:'25'
    });

const responseData = ref(null);

const submit = async () => {
        try {
            const response = await axios.post('/api/evaluate-growth', growthData.value);
            responseData.value = response.data
            // 在这里可以处理成功提交的响应数据
        } catch (error) {
            console.error('Error submitting data:', error);
            // 在这里可以处理请求失败的情况
        }

};
</script>

<template>
    <Head title="生长发育评估" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="font-semibold text-xl text-center text-gray-800 dark:text-gray-200 leading-tight">生长发育评估</div>
        </template>

        <form @submit.prevent="submit" class="items-center flex flex-col pt-2">
            <div>
                <InputLabel for="birthday" value="出生日期" />

                <TextInput
                    id="birthday"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="growthData.birthday"
                    required
                    autofocus
                />
            </div>
            <div>
                <InputLabel for="gender" value="性别" />

                <TextInput
                    id="gender"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="growthData.gender"
                    required
                />
            </div>
            <div>
                <InputLabel for="height-type" value="身高类别" />

                <TextInput
                    id="height-type"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="growthData.height_type"
                    required
                />
            </div>
            <div>
                <InputLabel for="height" value="身高" />

                <TextInput
                    id="height"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="growthData.height"
                    required
                />
            </div>
            <div>
                <InputLabel for="weight" value="体重" />

                <TextInput
                    id="weight"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="growthData.weight"
                    required
                />
            </div>
            <div class="flex items-center mt-4">

                <PrimaryButton>
                    评估
                </PrimaryButton>
            </div>
        </form>
        <div v-if="responseData">
            <div>
                身高评价：{{responseData.height_evaluation}}
            </div>
            <div>
                体重评价：{{responseData.weight_evaluation}}
            </div>
        </div>




    </AuthenticatedLayout>
</template>
