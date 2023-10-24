<template>
    <table class="table-auto border-collapse border border-gray-700 text-sm">
        <thead>
        <tr>
            <th colspan="8" class="border border-gray-700 ">{{props.data.gender==="boy"?"男童":"女童"}}{{props.data.age_month}}月龄生长发育标准</th>
        </tr>
        <tr>
            <th class="border border-gray-700 "></th>
            <th class="border border-gray-700 ">-3sd</th>
            <th class="border border-gray-700 ">-2sd</th>
            <th class="border border-gray-700 ">-1sd</th>
            <th class="border border-gray-700 ">平均值</th>
            <th class="border border-gray-700 ">+1sd</th>
            <th class="border border-gray-700 ">+2sd</th>
            <th class="border border-gray-700 ">+3sd</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="row in tableRows" :key="row.label">
            <td class="border border-gray-700 ">{{ row.label }}</td>
            <td v-for="sd in row.values" :key="sd" class="border border-gray-700 ">{{ sd }}</td>
        </tr>
        </tbody>
    </table>
</template>

<script setup>
import { ref,defineProps,computed } from 'vue';

const props = defineProps({
    data: Object,
    heightWeightData: Object,
});


const tableRows = computed(() => {
    return [
        {
            label: props.heightWeightData?props.heightWeightData.height+"别体重kg":"别体重无数据",
            values: [
                props.heightWeightData?props.heightWeightData.weight_minus_3sd:"",
                props.heightWeightData?props.heightWeightData.weight_minus_2sd:"",
                props.heightWeightData?props.heightWeightData.weight_minus_1sd:"",
                props.heightWeightData?props.heightWeightData.weight_0sd:"",
                props.heightWeightData?props.heightWeightData.weight_plus_1sd:"",
                props.heightWeightData?props.heightWeightData.weight_plus_2sd:"",
                props.heightWeightData?props.heightWeightData.weight_plus_3sd:""
            ]
        },
        {
            label: "体重kg",
            values: [
                props.data.weight_minus_3sd,
                props.data.weight_minus_2sd,
                props.data.weight_minus_1sd,
                props.data.weight_0sd,
                props.data.weight_plus_1sd,
                props.data.weight_plus_2sd,
                props.data.weight_plus_3sd
            ]
        },
        {
            label: (props.data.height_type==="length")?"身长cm":"身高cm",
            values: [
                props.data.height_minus_3sd,
                props.data.height_minus_2sd,
                props.data.height_minus_1sd,
                props.data.height_0sd,
                props.data.height_plus_1sd,
                props.data.height_plus_2sd,
                props.data.height_plus_3sd
            ]
        },
        {
            label: "头围cm",
            values: [
                props.data.head_circumference_minus_3sd,
                props.data.head_circumference_minus_2sd,
                props.data.head_circumference_minus_1sd,
                props.data.head_circumference_0sd,
                props.data.head_circumference_plus_1sd,
                props.data.head_circumference_plus_2sd,
                props.data.head_circumference_plus_3sd
            ]
        },
        {
            label: "BMI",
            values: [
                props.data.bmi_minus_3sd,
                props.data.bmi_minus_2sd,
                props.data.bmi_minus_1sd,
                props.data.bmi_0sd,
                props.data.bmi_plus_1sd,
                props.data.bmi_plus_2sd,
                props.data.bmi_plus_3sd
            ]
        }
    ]
});

</script>

<style>
/* 您可以在此添加您需要的样式 */
</style>
