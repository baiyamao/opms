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
import GrowthStandardsTable from "@/Components/GrowthStandardsTable.vue";
// 引入 child-growth-eval
import { evaluateGrowth, type GrowthEvaluationResult } from "child-growth-eval";

type ResponseDataType = {
    age_in_months?: number;
    nutrition_weight_evaluation?: string;
    nutrition_height_evaluation?: string;
    nutrition_height_weight_evaluation?: string;
    standards?: {
        height_type: string;
    };
    height_weight_evaluation?: string;
    weight_evaluation?: string;
    height_evaluation?: string;
    height_weight_standards?: any;
    bmi?:string;
    bmi_evaluation?: string;
    nutrition_bmi_evaluation?: string;

    // Add other fields here if necessary
};


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
    hasError.value=false;

}

const loadExample=()=>{
    growthData.value={
        birthday:'2024-02-03',
        gender:'boy',
        height_type:'',
        height:'83',
        weight:'12'
    };

}

const loading =ref(false);
const hasError =ref(false);
const message =ref<string | null>(null);

//格式化日期
const normalizeDateStr = (dateStr: string): string => {
    // 使用正则表达式匹配上述提到的多种日期格式
    const match = dateStr.match(/^(\d{4})[-/.]?(\d{1,2})[-/.]?(\d{1,2})$/);

    if (!match) {
        // throw new Error('Invalid date format');
        return dateStr;
    }

    const year = match[1];
    const month = match[2].padStart(2, '0');  // Ensure month is 2 digits
    const day = match[3].padStart(2, '0');    // Ensure day is 2 digits

    return `${year}-${month}-${day}`;
};

//根据生日计算月龄
const calculateAgeInMonths = (birthdayStr: string): number => {
    const today = new Date();
    const birthDate = new Date(normalizeDateStr(birthdayStr));

    let months;
    months = (today.getFullYear() - birthDate.getFullYear()) * 12;
    months -= birthDate.getMonth();
    months += today.getMonth();
    return months <= 0 ? 0 : months;

};
//根据月龄判断身长和身高
const judgeHeightType =(ageInMonths:number):string=>{
  if (ageInMonths<24){
      growthData.value.height_type="length";
      return "length";
  }else if(ageInMonths>24){
      growthData.value.height_type="height";
      return "height";
  }else{
      growthData.value.height_type="";
      return "";
  }
};

const ageInMonths = computed(() => calculateAgeInMonths(growthData.value.birthday));
const heightType = computed(() => judgeHeightType(calculateAgeInMonths(growthData.value.birthday)));


const genderOptions = [
    {label: '男', value: 'boy'},
    {label: '女', value: 'girl'},
];

const heightOptions = [
    {label: '身长', value: 'length'},
    {label: '身高', value: 'height'},
];

export interface ExtendedGrowthEvaluationResult extends GrowthEvaluationResult {
    ageInMonths?: number; // 新增属性
    heightType?: "length" | "height";
}
const responseData = ref<ExtendedGrowthEvaluationResult | null>(null);

// 🔥 直接调用 child-growth-eval 而不是 axios
const submit = async () => {
    loading.value = true;
    hasError.value = false;
    try {
        responseData.value = evaluateGrowth({
            ageInMonths: ageInMonths.value,
            gender: growthData.value.gender as "boy" | "girl",
            heightType: growthData.value.height_type as "length" | "height",
            height: parseFloat(growthData.value.height),
            weight: parseFloat(growthData.value.weight)
        });
        if (responseData.value) {
            responseData.value['ageInMonths'] = ageInMonths.value;
            responseData.value['heightType'] = growthData.value.height_type as "length" | "height";

        }
        loading.value = false;
    } catch (error: unknown) {
        console.error("Error evaluating growth:", error);
        loading.value = false;
        hasError.value = true;
        message.value = error instanceof Error ? error.message : "未知错误";
    }
};

</script>

<template>
    <Head title="生长发育评估工具 beta" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="font-semibold text-xl text-center text-gray-800 dark:text-gray-200 leading-tight">生长发育评估工具 beta</div>
            <div class="flex justify-between text-gray-400 text-sm mt-2">
                <span>评价数据标准来源：WHO Child Growth Standards (2006)</span><span>作者：baiyamao</span>
            </div>
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

                <div>
                    <InputLabel  value="身高类别(24月龄需要)" />
                    <RadioGroup
                        id="height_type"
                        v-model="growthData.height_type"
                        :options="heightOptions"
                        class="mt-3 disabled:opacity-25"
                        :is-disabled="heightType!==''"
                    />
                </div>

                <div>
                    <InputLabel for="weight" value="体重(千克/Kg)" />

                    <TextInput
                        id="weight"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="growthData.weight"
                    />
                </div>
                <div>
                    <InputLabel for="height"
                                :value="growthData.height_type=='height'?'身高(厘米/cm)':'身长(厘米/cm)'" />

                    <TextInput
                        id="height"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="growthData.height"
                    />
                </div>


                <div class="flex items-center mt-6">

                    <PrimaryButton class="ml-2">
                        评估
                    </PrimaryButton>
                    <DangerButton type="button" class="ml-4" @click="resetForm">
                        清空
                    </DangerButton>
                </div>
            </div>


        </form>
        <div class="absolute grid-cols-1 grid w-full justify-items-center py-2">
            <LoadingSpinner v-if="loading"></LoadingSpinner>
            <span v-if="hasError" class="text-red-600">{{message}}</span>
            <span v-if="responseData && !responseData.heightWeightStandard" class="text-blue-600">注意：提供的身长身高值超出身高别体重数据范围！</span>
        </div>
        <div v-if="responseData" class="flex flex-col items-center mt-10 p-5 border rounded-lg shadow-md bg-white">
            <div class="text-2xl font-semibold mb-4">生长发育评价结果</div>
            <table class="text-sm bg-white border rounded overflow-hidden">
                <tbody>
                <tr class="border-b">
                    <td class="p-2 font-bold">年龄</td>
                    <td class="p-2">{{Math.floor((responseData?.ageInMonths || 0) / 12)}}岁{{(responseData?.ageInMonths || 0)%12}}个月</td>
                </tr>
                <tr class="border-b">
                    <td class="p-2 font-bold">体重评价</td>
                    <td class="p-2">{{responseData.weightEvaluation}}</td>
                    <td class="p-2 text-red-600">{{responseData.nutrition.weight}}</td>
                </tr>
                <tr class="border-b">
                    <td class="p-2 font-bold">{{(responseData.heightType==="length")?"身长":"身高"}}评价</td>
                    <td class="p-2">{{responseData.heightEvaluation}}</td>
                    <td class="p-2 text-red-600">{{responseData.nutrition.height}}</td>
                </tr>
                <tr class="border-b">
                    <td class="p-2 font-bold">{{(responseData.heightType==="length")?"身长别体重":"身高别体重"}}评价</td>
                    <td class="p-2">{{responseData.heightWeightEvaluation}}</td>
                    <td class="p-2 text-red-600">{{responseData.nutrition.heightWeight}}</td>
                </tr>
                <tr class="border-b">
                    <td class="p-2 font-bold">BMI</td>
                    <td class="p-2">{{responseData.bmi}}</td>
                </tr>
                <tr class="border-b">
                    <td class="p-2 font-bold">BMI评价</td>
                    <td class="p-2">{{responseData.bmiEvaluation}}</td>
                    <td class="p-2 text-red-600">{{responseData.nutrition.bmi}}</td>
                </tr>
                </tbody>
            </table>
            <GrowthStandardsTable :data="responseData.standard" :height-weight-data="responseData.heightWeightStandard" v-if="responseData"
                                  class="mt-2"/>
        </div>




    </AuthenticatedLayout>
</template>
