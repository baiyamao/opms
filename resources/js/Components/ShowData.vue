<script setup lang="ts">
import { PropType, computed } from 'vue';


// 定义数据项的类型
interface Patient {
    cardData: string;
    patName: string;
    age: string;
    addr: string;
    idCard: string;
    regName: string;
    mZSJ: string;
    patRegTime: string;
    patClass: string;
    telePhone: string;
}

// 定义字段映射的类型
const fieldMap: Record<keyof Patient, string> = {
    regName: "挂号科室",
    mZSJ: "序号",
    patName: "患者姓名",
    age: "年龄",
    patRegTime: "挂号时间",
    patClass: "医保类型",
    cardData: "就诊卡号",
    idCard: "证件号码",
    telePhone: "联系电话",
    addr: "联系地址",
};

// 定义组件 Props
const props = defineProps({
    data: {
        type: Array as PropType<Patient[]>,
        required: true,
    },
});

// 使用 computed 处理数据
const displayedData = computed(() =>
    props.data.map((item) => {
        const filteredItem: Record<string, string> = {};
        (Object.keys(fieldMap) as Array<keyof Patient>).forEach((key) => {
            filteredItem[fieldMap[key]] = item[key];
        });
        return filteredItem;
    })
);
</script>

<template>
    <div>
        <div v-for="(item, index) in displayedData" :key="index" class="">
            <ul class="flex flex-wrap gap-1 justify-between">
                <li v-for="(value, key) in item" :key="key"
                    class="">
                    <strong>{{ key }}:</strong> {{ value }}
                </li>
            </ul>
        </div>
    </div>
</template>

<style scoped>

</style>
