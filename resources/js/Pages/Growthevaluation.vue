<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head , useForm } from '@inertiajs/vue3';
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {ref, computed} from "vue";
import axios from "axios";
import RadioGroup from "@/Components/RadioGroup.vue";
import LoadingSpinner from "@/Components/LoadingSpinner.vue"
import DangerButton from "@/Components/DangerButton.vue";

const growthData = ref({
    birthday:'',
    gender:'',
    height_type:'',
    height:'',
    weight:''
    });

const resetForm=()=>{
    growthData.value={
        birthday:'',
        gender:'',
        height_type:'',
        height:'',
        weight:''
    };
    responseData.value=null;
    loading.value=false;

}

const loadExample=()=>{
    growthData.value={
        birthday:'20200303',
        gender:'boy',
        height_type:'height',
        height:'100',
        weight:'25'
    };

}

const loading =ref(false);
const message =ref(null);

//根据生日计算月龄
const calculateAgeInMonths = (birthdayStr: string): number => {
    const today = new Date();
    const birthDate = new Date(birthdayStr);

    let months;
    months = (today.getFullYear() - birthDate.getFullYear()) * 12;
    months -= birthDate.getMonth();
    months += today.getMonth();

    return months <= 0 ? 0 : months;
};

const ageInMonths = computed(() => calculateAgeInMonths(growthData.value.birthday));

const genderOptions = [
    {label: '男', value: 'boy'},
    {label: '女', value: 'girl'},
];

const heightOptions = [
    {label: '身长', value: 'length'},
    {label: '身高', value: 'height'},
];

const responseData = ref(null);

const submit = async () => {
    loading.value=true;
        try {
            const response = await axios.post('/api/evaluate-growth', growthData.value);
            responseData.value = response.data
            loading.value=false;
            // 在这里可以处理成功提交的响应数据
        } catch (error) {
            console.error('Error submitting data:', error);
            message.value="数据有误，评价失败，请核对数据。";
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
            <a href="#" class="text-sm text-gray-500 flex underline" @click="loadExample">加载示例数据</a>
            <div class="grid grid-cols-3 gap-4">
                <div class="">
                    <InputLabel for="birthday" value="出生日期" />
                    <TextInput
                        id="birthday"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="growthData.birthday"
                        autofocus
                    />
                </div>
                <div>
                    <InputLabel value="性别" />
                    <RadioGroup
                        id="gender"
                        v-model="growthData.gender"
                        :options="genderOptions"
                        class="mt-3"
                    />
                </div>

                <div v-if="ageInMonths==24">
                    <InputLabel  value="身高类别" />
                    <RadioGroup
                        id="height_type"
                        v-model="growthData.height_type"
                        :options="heightOptions"
                        class="mt-3"
                    />
                </div>
                <div v-else>

                </div>
                <div>
                    <InputLabel for="height" value="身高/身长 (厘米/cm)" />

                    <TextInput
                        id="height"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="growthData.height"
                    />
                </div>
                <div>
                    <InputLabel for="weight" value="体重 (千克/Kg)" />

                    <TextInput
                        id="weight"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="growthData.weight"
                    />
                </div>


                <div class="flex items-center mt-6">

                    <PrimaryButton class="ml-2">
                        评估
                    </PrimaryButton>
                    <DangerButton type="button" class="ml-4" @click="resetForm">
                        重置
                    </DangerButton>
                </div>
            </div>


        </form>
        <div v-if="loading" class="absolute grid-cols-1 grid w-full justify-items-center py-1">
            <LoadingSpinner></LoadingSpinner>
            <span>{{message}}</span>
        </div>
        <div v-if="responseData" class="flex flex-col items-center mt-10">
            <div class="text-xl">生长发育评价结果</div>
            <div class="mt-2">
                月龄：{{responseData.age_in_months}}个月
            </div>
            <div class="mt-2">
                身高/身长评价：{{responseData.height_evaluation}}
            </div>
            <div class="mt-2">
                体重评价：{{responseData.weight_evaluation}}
            </div>
        </div>




    </AuthenticatedLayout>
</template>
