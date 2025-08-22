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

// ⬇️ 新增
import * as XLSX from "xlsx";



// --------------------- 原有单个评价逻辑 ---------------------

// type ResponseDataType = {
//     age_in_months?: number;
//     nutrition_weight_evaluation?: string;
//     nutrition_height_evaluation?: string;
//     nutrition_height_weight_evaluation?: string;
//     standards?: {
//         height_type: string;
//     };
//     height_weight_evaluation?: string;
//     weight_evaluation?: string;
//     height_evaluation?: string;
//     height_weight_standards?: any;
//     bmi?:string;
//     bmi_evaluation?: string;
//     nutrition_bmi_evaluation?: string;
//
//     // Add other fields here if necessary
// };


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

// Excel 日期序列号转 JS 日期
const excelDateToJSDate = (serial: number): string => {
    const utcDays = Math.floor(serial - 25569); // Excel 起始日期 1899-12-31
    const utcValue = utcDays * 86400; // 转秒
    const dateInfo = new Date(utcValue * 1000);

    const year = dateInfo.getUTCFullYear();
    const month = (dateInfo.getUTCMonth() + 1).toString().padStart(2, "0");
    const day = dateInfo.getUTCDate().toString().padStart(2, "0");
    return `${year}-${month}-${day}`;
};

// 日期格式标准化（最终增强版）
const normalizeDateStr = (dateRaw: any): string => {
    if (!dateRaw) return "";

    // 如果是数字（Excel 可能存储为序列号）
    if (typeof dateRaw === "number") {
        return excelDateToJSDate(dateRaw);
    }

    let dateStr = dateRaw.toString().trim();

    // 替换常见分隔符为 "-"
    dateStr = dateStr.replace(/[./]/g, "-");

    // 如果是纯 8 位数字 20220302
    if (/^\d{8}$/.test(dateStr)) {
        dateStr = `${dateStr.slice(0, 4)}-${dateStr.slice(4, 6)}-${dateStr.slice(6, 8)}`;
    }

    // 最终校验
    const match = dateStr.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (!match) return dateRaw.toString(); // 无法识别就原样返回

    const year = match[1];
    const month = match[2].padStart(2, "0");
    const day = match[3].padStart(2, "0");
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

// --------------------- ⬇️ 新增 批量处理逻辑 ---------------------
const uploadedDataRows = ref<any[][]>([]);   // ⬅️ 新增：缓存上传的数据

const loading2 =ref(false);

// 下载模板
// 模板下载
const downloadTemplate = () => {
    // 模板表头（带单位）
    const templateData = [
        ["编号", "姓名", "出生日期(yyyy-mm-dd)", "性别(男/女)", "身高/身长(cm)", "体重(kg)"]
    ];

    const ws = XLSX.utils.aoa_to_sheet(templateData);

    // 设置“出生日期”列为文本格式（C列）
    const range = XLSX.utils.decode_range(ws['!ref']!);
    for (let R = range.s.r + 1; R <= range.e.r; ++R) { // 从第2行开始（跳过表头）
        const cell_address = { c: 2, r: R }; // C列
        const cell_ref = XLSX.utils.encode_cell(cell_address);
        if (!ws[cell_ref]) {
            ws[cell_ref] = { t: 's', v: '' }; // 空单元格也强制文本
        } else {
            ws[cell_ref].t = 's';
        }
    }

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "模板");
    XLSX.writeFile(wb, "生长发育评估模板.xlsx");
};

// 必填表头
const requiredHeaders = ["编号", "姓名", "出生日期(yyyy-mm-dd)", "性别(男/女)", "身高/身长(cm)", "体重(kg)"];

// 上传但不处理
const handleFileUpload = async (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const data = new Uint8Array(e.target?.result as ArrayBuffer);
        const workbook = XLSX.read(data, { type: "array" });
        const firstSheetName = workbook.SheetNames[0];
        const worksheet = workbook.Sheets[firstSheetName];
        const rows: any[][] = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

        if (rows.length === 0) {
            alert("❌ 上传的文件为空！");
            return;
        }

        const headers = rows[0].map((h: any) => (h ?? "").toString().trim());
        const missingHeaders = requiredHeaders.filter(h => !headers.includes(h));
        if (missingHeaders.length > 0) {
            alert(`❌ Excel 模板缺少以下字段：${missingHeaders.join("，")}`);
            return;
        }

        uploadedDataRows.value = rows.slice(1).filter(r => r.length > 0);
        alert("✅ 文件上传成功，请点击“开始评价”按钮进行批量计算");
    };
    reader.readAsArrayBuffer(file);
};

// 点击“开始评价”时才执行
const startEvaluation = () => {
    if (uploadedDataRows.value.length === 0) {
        alert("⚠️ 请先上传 Excel 文件！");
        return;
    }
    loading2.value = true;
    setTimeout(() => {   // 模拟异步处理
        processBatchData(uploadedDataRows.value);
        loading2.value = false;
    }, 300); // 短暂延时保证动画能显示
};

// 批量处理
const processBatchData = (dataRows: any[][]) => {
    const results: any[] = [];
    let has24MonthChild = false;

    for (const row of dataRows) {
        const [id, name, birthdayRaw, genderZh, heightStr, weightStr] = row;
        if (!id || !name || !birthdayRaw || !genderZh || !heightStr || !weightStr) continue;

        const birthday = normalizeDateStr(birthdayRaw);
        const ageInMonths = calculateAgeInMonths(birthday);
        const heightType = judgeHeightType(ageInMonths);
        if (ageInMonths === 24) has24MonthChild = true;

        const gender = genderZh === "男" ? "boy" : "girl";

        const result = evaluateGrowth({
            ageInMonths,
            gender,
            heightType: heightType || "height",
            height: parseFloat(heightStr),
            weight: parseFloat(weightStr),
        });

        results.push({
            编号: id,
            姓名: name,
            出生日期: birthday, // ⬅️ 这里是字符串格式
            性别: genderZh,
            年龄: `${Math.floor(ageInMonths / 12)}岁${ageInMonths % 12}个月`,
            身高类别: heightType === "length" ? "身长" : "身高",
            "身高/身长(cm)": heightStr,
            "体重(kg)": weightStr,

            体重评价: result.weightEvaluation,
            身高评价: result.heightEvaluation,
            "身高别体重评价": result.heightWeightEvaluation,
            BMI: result.bmi,
            "BMI评价": result.bmiEvaluation,

            "营养学体重评价": result.nutrition.weight ?? "",
            "营养学身高评价": result.nutrition.height ?? "",
            "营养学身高别体重评价": result.nutrition.heightWeight ?? "",
            "营养学BMI评价": result.nutrition.bmi ?? "",
        });
    }

    // 固定表头顺序
    const orderedHeaders = [
        "编号","姓名","出生日期","性别","年龄","身高类别","身高/身长(cm)","体重(kg)",
        "体重评价","身高评价","身高别体重评价","BMI","BMI评价",
        "营养学体重评价","营养学身高评价","营养学身高别体重评价","营养学BMI评价"
    ];

    const ws = XLSX.utils.json_to_sheet(results, { header: orderedHeaders });

    // ⬅️ 设置“出生日期”列为文本格式，避免 Excel 自动识别
    const range = XLSX.utils.decode_range(ws['!ref']!);
    for (let R = range.s.r + 1; R <= range.e.r; ++R) { // 跳过表头
        const cell_address = { c: 2, r: R }; // 第3列（C列）是“出生日期”
        const cell_ref = XLSX.utils.encode_cell(cell_address);
        if (!ws[cell_ref]) continue;
        ws[cell_ref].t = 's'; // 强制文本
    }

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "评价结果");
    XLSX.writeFile(wb, "生长发育评估结果.xlsx");

    if (has24MonthChild) {
        alert("⚠️ 注意：发现月龄 = 24 的儿童，系统已默认使用“身高”标准。");
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
        <div class="flex flex-col items-center w-full mt-4 space-y-6">
            <!-- --------------------- ⬇️ 新增 批量处理区域 --------------------- -->
            <!-- 批量评价区域 -->
            <details class="w-full max-w-4xl">
                <summary class="cursor-pointer font-semibold text-gray-700 text-center py-2">批量评价操作（点击展开）</summary>
                <div class="flex flex-col items-center space-y-4 mt-2 p-4 border rounded-lg shadow-sm bg-white">
                    <div class="flex items-center space-x-4">
                        <PrimaryButton @click="downloadTemplate">下载模板</PrimaryButton>
                        <input type="file" accept=".xlsx" @change="handleFileUpload" class="text-sm text-gray-600"/>
                        <PrimaryButton @click="startEvaluation">开始评价并下载</PrimaryButton>
                    </div>
                    <div class="flex justify-center items-center">
                        <LoadingSpinner v-if="loading2"/>
                    </div>
                </div>
            </details>
            <!-- ------------------------------------------------------------ -->

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
        </div>





    </AuthenticatedLayout>
</template>

