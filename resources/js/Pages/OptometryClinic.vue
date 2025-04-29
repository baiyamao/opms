<script setup lang="ts">
import {ref, onMounted, onUnmounted, computed} from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { getRandomIV, encryptAES } from '@/utils/crypto-aes';
import ShowData from "@/Components/ShowData.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import dayjs from 'dayjs'



// 定义单个视光记录的接口
interface OptometryRecord {
    medical_record_number?: string;
    name: string;
    phone: string;
    // ...其他可能的字段
}

// 定义接口类型来描述从 API 获取的病人数据的结构
interface Patient {
    opcId: string;
    patRegTime: string; // 挂号时间
    mzsj: string;       // 序号（假设为某种标识符）
    regName: string;    // 科室
    state: string;      // 状态
    optometry_record: OptometryRecord[]; // 视光档案数组
    patName: string;    // 挂号姓名
    sex: number;        // 性别
    age: string;        // 年龄
    dateOfBirth: string // 生日
    cardData: string;   // 就诊ID
    telePhone: string;  // 挂号电话
    info_check:string;//一致性检查
    addr?: string; // 可选属性
    idCard?: string; // 可选属性
    patClass?: string; // 可选属性
    selected: boolean;
    // ...根据实际需求添加其他属性
}

const dateValue = ref([]);
const formatter = ref({
    date: 'YYYYMMDD',
    month: 'MM',
})

function onDateSelect() {
    // 立即拉取数据
    fetchData()
}


const patientData = ref<Patient[]>([]);
const showErbao = ref<boolean>(false); // 控制是否显示儿保挂号
const showFinish = ref<boolean>(true); // 控制是否显示诊毕


// 切换显示诊毕
function toggleShowFinish() {
    showFinish.value = !showFinish.value;
    fetchData(); // 每次切换后重新获取数据
}

// 切换显示儿保挂号
function toggleShowErbao() {
    showErbao.value = !showErbao.value;
    fetchData(); // 每次切换后重新获取数据
}

const activePatients = computed(() =>{
    // 基准日期从外部或直接硬编码，都可以灵活替换
    const asOfStr = dayjs().format('YYYYMMDD')

    return patientData.value
        .filter(p => showErbao.value || p.regName !== '儿保')
        // .filter(p => p.regName !== '诊毕')  // 若还需要排除“诊毕”
        .map(p => {
            // 计算并赋值
            p.age = calcAge(p.dateOfBirth, asOfStr)
            return p
        })

})

/**
 * 计算从出生日期到指定“当前日期”之间的岁/月/天
 * @param dobStr 生日，格式 "YYYY-MM-DD HH:mm:ss"
 * @param asOfStr 计算基准日期，格式 "YYYYMMDD"，如 "20250405"
 */
function calcAge(dobStr: string, asOfStr: string): string {
    const dob = dayjs(dobStr, 'YYYY-MM-DD HH:mm:ss')
    const asOf = dayjs(asOfStr, 'YYYYMMDD')

    let years = asOf.diff(dob, 'year')
    const afterYears = dob.add(years, 'year')

    let months = asOf.diff(afterYears, 'month')
    const afterMonths = afterYears.add(months, 'month')

    let days = asOf.diff(afterMonths, 'day')

    const parts = []
    if (years > 0) parts.push(`${years}岁`)
    if (months > 0) parts.push(`${months}月`)
    if (days > 0) parts.push(`${days}天`)
    // 若都为 0，表示当天生日
    if (parts.length === 0) parts.push('0天')

    return parts.join('')
}

const formatDate = (dateString: string): string => {
    const [date, time] = dateString.split(' ');
    const [hours, minutes] = time.split(':');
    return `${date} ${hours}:${minutes}`;
};

// 是否为多选模式
const isMultiSelect = ref(false);
// 单选模式下是否有条目被选中
const isSingleSelect = ref(false);
const pressTimer = ref<number | null>(null); // 长按计时器
const isLongPress = ref(false); // 是否为长按行为

// 切换多选模式
const toggleMultiSelect = () => {
    isMultiSelect.value = !isMultiSelect.value;
    isSingleSelect.value = false;

    // 如果退出多选模式，清除所有选中状态
    if (!isMultiSelect.value) {
        activePatients.value.forEach(row => (row.selected = false));
        isSingleSelect.value = false;
    }
};

// 选中行的索引
const selectedRowIndex = ref<number | null>(null);

// 单选模式下单击行
const toggleSingleSelect = (index: number) => {
    if(activePatients.value[index].selected){//再次单击取消选择
        activePatients.value[index].selected = !activePatients.value[index].selected;
        isSingleSelect.value = false;
    }else{
        activePatients.value.forEach((row, i) => (row.selected = i === index));
        isSingleSelect.value = true;
    }

};

// 多选模式下切换选中状态
const toggleRowSelection = (index: number) => {
    activePatients.value[index].selected = !activePatients.value[index].selected;
};

// 长按相关逻辑
const startPressTimer = (index: number) => { // 新增代码
    clearPressTimer(); // 确保没有残留计时器
    isLongPress.value = false;

    pressTimer.value = window.setTimeout(() => {
        isLongPress.value = true; // 长按触发
        // console.log(`Row ${index} long-pressed`); // 长按行为
    }, 500); // 设置为 500ms 触发长按
};

const endPressTimer = (index: number) => { // 新增代码
    if (!isLongPress.value) {
        clearPressTimer();
    }
};

const clearPressTimer = () => { // 新增代码
    if (pressTimer.value) {
        clearTimeout(pressTimer.value);
        pressTimer.value = null;
    }
};

// 双击逻辑
// const handleDoubleClick = (index: number) => { // 新增代码
//     console.log(`Row ${index} double-clicked`);
//     clearPressTimer(); // 确保清除计时器
// };

// 单击复选框处理（防止双击和手动选中冲突）
const onCheckboxChange = (index: number, checked: boolean) => {
    activePatients.value[index].selected = checked;
};

//全选和取消全选
const toggleAll = (event: Event) => {
    const checked = (event.target as HTMLInputElement).checked;
    activePatients.value.forEach(row => {
        row.selected = checked;
    });
};

// 单击逻辑
const handleClick = (index: number) => { // 修改：封装单击逻辑
    if (isLongPress.value) {
        // 如果是长按触发，阻止单击逻辑
        return;
    }
    if (isMultiSelect.value) {
        toggleRowSelection(index); // 多选模式下切换选中状态
    } else {
        toggleSingleSelect(index); // 单选模式下切换选中状态
    }
};

// fetchData函数负责从API获取数据，并更新patientData的值
async function fetchData() {
    try {
        const response = await axios.post('/api/get-register-list-by-date',{
            // showErbao: showErbao.value,
            regDate:dateValue.value[0]
        });

        if (response.data && Array.isArray(response.data)) {
            //// 显式地为 `selected` 添加默认值
            // 更新 patientData.value，保留 selected 状态
            patientData.value = response.data.map(patient => {
                // 在现有 patientData.value 中查找是否存在当前患者数据
                // 假设 opcId 是唯一标识符，用于匹配患者
                const existingPatient = patientData.value.find(
                    (p: Patient) => p.opcId === patient.opcId
                );

                // 返回新的患者数据对象，保留原属性，并根据以下逻辑处理 selected:
                // - 如果 existingPatient 存在且其 selected 为 true，则保留 true
                // - 如果 existingPatient 不存在或 selected 为 false，则设置为 false
                return {
                    ...patient,                           // 保留新数据的所有原属性
                    selected: existingPatient?.selected || false, // 根据原状态保留或重置
                };
            });
            patientData.value.sort((a, b) => new Date(b.patRegTime).getTime() - new Date(a.patRegTime).getTime());
        } else {
            console.error('数据格式不正确:', response.data);
        }
    } catch (error) {
        let message = '获取数据出错.';
        if (axios.isAxiosError(error)) {
            if (error.response) {
                // 可以根据错误响应体的内容定制错误信息
                message = error.response.data.message || '获取数据出错.';
            }
        } else {
            console.error('出错:', error);
        }
        // 调用openModal方法显示错误信息
        openModal(message);

        // 清除定时器，无论错误类型
        if (intervalId !== undefined) {
            clearInterval(intervalId);
            intervalId = undefined;
        }
    }
}


let intervalId: number | undefined;

onMounted(async () => {
    await fetchData(); // 组件挂载时获取数据
    // 设置定时器，每10秒调用一次fetchData函数
    intervalId = setInterval(fetchData, 10000);
});

onUnmounted(() => {
    // 组件卸载时清除定时器
    if (intervalId !== undefined) {
        clearInterval(intervalId);
    }
});

function openModal(errorMessage = '') {
    const modal = document.getElementById('my_modal_1');
    const modalErrorMessage = document.getElementById('modalErrorMessage');
    if (modal && modalErrorMessage) {
        modalErrorMessage.textContent = errorMessage; // 设置错误信息
        (modal as HTMLDialogElement).showModal(); // 显示模态框
    }
}

const key = '385f33cb91484b04a177828829081ab7';
interface SystemAccount {
    username?: string;
    password?: string;
    iv?:string;
}


// 获取huzhoufuyou系统账号密码
async function fetchSystemAccount(): Promise<SystemAccount | null> {
    try {
        const response = await axios.post('/api/get-system-account');

        if (response.data) {
            const { account, password } = response.data;
            const systemAccount: SystemAccount= {
                iv:getRandomIV()
            };
            if (systemAccount.iv != null) {
                systemAccount.username = encryptAES(account, key, systemAccount.iv);
            }
            if (systemAccount.iv != null) {
                systemAccount.password = encryptAES(password, key, systemAccount.iv);
            }
            return systemAccount;
        } else {
            console.error('API 返回数据为空');
            return null;
        }
    } catch (error) {
        console.error('请求失败:', error);
        return null;
    }
}

// 使用示例
fetchSystemAccount().then((account) => {
    if (account) {
        console.log('aes账户信息:', account);
    } else {
        console.log('未获取到账户信息');
    }
});
//tabs
const activeTab = ref("overview");
const tabs = [
    {
        id: "overview",
        name: "屈光发育概况",
        content: "屈光发育概况内容",
        iconPath:
            "M6.143 0H1.857A1.857 1.857 0 0 0 0 1.857v4.286C0 7.169.831 8 1.857 8h4.286A1.857 1.857 0 0 0 8 6.143V1.857A1.857 1.857 0 0 0 6.143 0Zm10 0h-4.286A1.857 1.857 0 0 0 10 1.857v4.286C10 7.169 10.831 8 11.857 8h4.286A1.857 1.857 0 0 0 18 6.143V1.857A1.857 1.857 0 0 0 16.143 0Zm-10 10H1.857A1.857 1.857 0 0 0 0 11.857v4.286C0 17.169.831 18 1.857 18h4.286A1.857 1.857 0 0 0 8 16.143v-4.286A1.857 1.857 0 0 0 6.143 10Zm10 0h-4.286A1.857 1.857 0 0 0 10 11.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 18 16.143v-4.286A1.857 1.857 0 0 0 16.143 10Z",
        iconViewBox: "0 0 18 18",
    },
    {
        id: "records",
        name: "视光门诊记录",
        content: "视光门诊记录内容",
        iconPath:
            "M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z",
        iconViewBox: "0 0 20 20",
    },
    {
        id: "eye-check",
        name: "幼儿眼部体检",
        content: "幼儿眼部体检内容",
        iconPath:
            "M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z",
        iconViewBox: "0 0 20 20",
    },
    {
        id: "vision-screening",
        name: "学生视力筛查",
        content: "学生视力筛查内容",
        iconPath:
            "M16 1h-3.278A1.992 1.992 0 0 0 11 0H7a1.993 1.993 0 0 0-1.722 1H2a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2Zm-3 14H5a1 1 0 0 1 0-2h8a1 1 0 0 1 0 2Zm0-4H5a1 1 0 0 1 0-2h8a1 1 0 1 1 0 2Zm0-5H5a1 1 0 0 1 0-2h2V2h4v2h2a1 1 0 1 1 0 2Z",
        iconViewBox: "0 0 20 20",
    },
    {
        id: "basic-info",
        name: "档案基本信息",
        content: "档案基本信息内容",
        iconPath:
            "M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z",
        iconViewBox: "0 0 20 20",
    },
];
// interface Tab {
//     id: string;
//     title: string;
//     icon: string;
// }
//
// const tabs = ref<Tab[]>([
//     {
//         id: 'overview',
//         title: '屈光发育概况',
//         icon: 'overview-icon' // 使用实际的图标类名替换
//     },
//     {
//         id: 'records',
//         title: '视光门诊记录',
//         icon: 'records-icon' // 使用实际的图标类名替换
//     },
//     {
//         id: 'eye-check',
//         title: '幼儿眼部体检',
//         icon: 'eye-check-icon' // 使用实际的图标类名替换
//     },
//     {
//         id: 'vision-screening',
//         title: '学生视力筛查',
//         icon: 'vision-screening-icon' // 使用实际的图标类名替换
//     },
//     {
//         id: 'basic-info',
//         title: '档案基本信息',
//         icon: 'basic-info-icon' // 使用实际的图标类名替换
//     }
// ]);
//
// const activeTab = ref(tabs.value[0].id);
//
// const selectTab = (tabId: string) => {
//     activeTab.value = tabId;
// };
const setActiveTab = (tabId: string) => {
    activeTab.value = tabId;
};
</script>



<template>
    <Head title="视光门诊" />

    <AuthenticatedLayout class="overflow-hidden h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex">
            <ul class="menu menu-xs lg:menu-horizontal pl-12">
                <li><a
                    :class="{ 'bg-blue-500 text-white hover:bg-blue-500': isMultiSelect}"
                    @click="toggleMultiSelect"
                >多选</a></li>
                <li><a href="/optometry-record/add">新增档案</a></li>
                <li><a>编辑档案</a></li>
                <li><a>查看挂号信息</a></li>
                <li><a @click="toggleShowFinish">{{ showFinish ? '隐藏诊毕' : '显示诊毕' }}</a></li>
                <li><a @click="toggleShowErbao">{{ showErbao ? '隐藏儿保挂号' : '显示儿保挂号' }}</a></li>
            </ul>
            <div class="w-32 pt-1.5">
                <vue-tailwind-datepicker
                    v-model="dateValue"
                    as-single
                    :formatter="formatter"
                    i18n="zh-cn"
                    @update:model-value="onDateSelect"
                    input-classes="py-1 relative block w-full opacity-100
                    rounded-lg overflow-hidden
                    border-solid text-xs text-vtd-secondary-700
                    placeholder-vtd-secondary-400 transition-colors
                    bg-white border border-vtd-secondary-300
                    focus:border-vtd-primary-300 focus:ring
                    focus:ring-vtd-primary-500 focus:ring-opacity-10
                    focus:outline-none dark:bg-vtd-secondary-800
                    dark:border-vtd-secondary-700 dark:text-vtd-secondary-100
                    dark:placeholder-vtd-secondary-500 dark:focus:border-vtd-primary-500
                    dark:focus:ring-opacity-20"
                    placeholder="选择挂号日期"
                />
            </div>
        </div>

        <div
            :class="{'flex flex-row justify-center':isSingleSelect}">
            <div
                :class="{'basis-1/3 lg:pr-2':isSingleSelect}"
                class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                    <div class="overflow-x-auto h-screen-minus-15">
                        <dialog id="my_modal_1" class="modal">
                            <div class="modal-box">
                                <h3 class="font-bold text-lg">出错了！</h3>
                                <p class="py-4" id="modalErrorMessage"></p>
                                <div class="modal-action">
                                    <form method="dialog">
                                        <!-- if there is a button in form, it will close the modal -->
                                        <button class="btn">我知道了</button>
                                    </form>
                                </div>
                            </div>
                        </dialog>
                        <table class="table table-sm table-pin-rows">
                            <thead>
                            <tr>
                                <th>
                                    <label>
                                        <input
                                            v-if="isMultiSelect"
                                            type="checkbox"
                                           class="checkbox checkbox-xs"
                                           @change="toggleAll($event)"/>
                                    </label>
                                </th>
                                <th>序号</th>
                                <th>科别</th>
                                <th>状态</th>
                                <th v-if="!isSingleSelect">病历编号</th>
                                <th v-if="!isSingleSelect">档案姓名</th>
                                <th>挂号姓名</th>
                                <th v-if="!isSingleSelect">性别</th>
                                <th v-if="!isSingleSelect">年龄</th>
                                <th v-if="!isSingleSelect">就诊ID</th>
                                <th v-if="!isSingleSelect">挂号时间</th>
                                <th v-if="!isSingleSelect">挂号电话</th>
                                <th v-if="!isSingleSelect">档案电话</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr
                                v-for="(patient, index) in activePatients"
                                :key="patient.opcId"
                                v-show="patient.state !== '3'||showFinish"
                                @mousedown="startPressTimer(index)"
                                @mouseup="endPressTimer(index)"
                                @mouseleave="clearPressTimer"
                                @click="handleClick(index)"
                                :class="{
                                        'bg-blue-500 text-white': patient.selected,
                                        'hover': !patient.selected,
                                      }"
                                class="cursor-pointer">
                                <th>
                                    <label>
                                        <input
                                                v-if="isMultiSelect"
                                                type="checkbox"
                                               class="checkbox checkbox-xs "
                                               v-model="patient.selected"
                                               @change="onCheckboxChange(index, patient.selected)"/>
                                    </label>
                                </th>
                                <td>{{ patient.mzsj }}</td>
                                <td>{{ patient.regName }}</td>
                                <td :class="patient.state === '1' ? 'bg-red-400' : (patient.state === '3' ? '' : 'bg-green-400')">
                                    {{ patient.state === '1' ? '诊中' : (patient.state === '3' ? '诊毕' : '待诊') }}
                                </td>
                                <td v-if="!isSingleSelect"
                                    :class="patient.info_check ==='强相关'?'':'text-red-600'"
                                    >
                                    <div v-if="patient.info_check !==undefined">
                                        <div v-if="patient.info_check ==='强相关'">
                                            {{ patient.optometry_record[0].medical_record_number }}
                                        </div>
                                        <div v-else-if="patient.info_check ==='多个相关记录'">
                                            <button class="link relative">
                                                <div class="dropdown dropdown-left dropdown-hover">
                                                    <div tabindex="0" class="link">多条记录</div>
                                                    <ul tabindex="0"
                                                        style="width: min-content;"
                                                        class="dropdown-content z-[100] menu p-2 shadow bg-base-100 rounded-box w-auto" >
                                                        <li v-for="(record, index) in patient.optometry_record" :key="index">
                                                            <a style="white-space: nowrap;">{{ record.medical_record_number }} {{ record.name }} {{ record.phone }}</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <span
                                                    style="line-height: 0.1;"
                                                    class="absolute top-[-0.2rem] right-[-0.6rem] py-1 bg-red-500/60
                                                    text-white rounded-full h-3 w-3 flex items-center
                                                    justify-center text-sm" >&times;
                                                ️</span>
                                            </button>
                                        </div>
                                        <div v-else class="tooltip tooltip-error" :data-tip="patient.info_check">
                                            <button class="link relative">
                                                {{ patient.optometry_record[0].medical_record_number }}
                                                <span
                                                    style="line-height: 0.1;"
                                                    class="absolute top-[-0.2rem] right-[-0.6rem] py-1 bg-red-500/60
                                                    text-white rounded-full h-3 w-3 flex items-center
                                                    justify-center text-sm" >&times;
                                                ️</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td v-if="!isSingleSelect">
                                    <div v-if="patient.info_check !==undefined">
                                        {{ patient.info_check === "多个相关记录" ?'':(patient.optometry_record[0]?.name ?? '') }}
                                    </div>
                                </td>
                                <td>{{ patient.patName }}</td>
                                <td v-if="!isSingleSelect">{{ patient.sex === 1 ? '男' : '女' }}</td>
                                <td v-if="!isSingleSelect">{{ patient.age }}</td>
                                <td v-if="!isSingleSelect">{{ patient.cardData }}</td>
                                <td v-if="!isSingleSelect">{{ formatDate(patient.patRegTime) }}</td>
                                <td v-if="!isSingleSelect">{{ patient.telePhone }}</td>
                                <td v-if="!isSingleSelect">
                                    <div v-if="patient.info_check !==undefined">
                                        {{ patient.info_check === "多个相关记录" ?'':(patient.optometry_record[0]?.phone ?? '') }}
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr>
                                <th></th>
                                <th>序号</th>
                                <th>科别</th>
                                <th>状态</th>
                                <th v-if="!isSingleSelect">病历编号</th>
                                <th v-if="!isSingleSelect">档案姓名</th>
                                <th>挂号姓名</th>
                                <th v-if="!isSingleSelect">性别</th>
                                <th v-if="!isSingleSelect">年龄</th>
                                <th v-if="!isSingleSelect">就诊ID</th>
                                <th v-if="!isSingleSelect">挂号时间</th>
                                <th v-if="!isSingleSelect">挂号电话</th>
                                <th v-if="!isSingleSelect">档案电话</th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div
                v-if="isSingleSelect"
                class="basis-2/3 max-w-7xl mx-auto sm:px-6 lg:px-8 lg:pl-2">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">

                    <div class="overflow-x-auto h-screen-minus-15 p-2">

                        <div class="flex flex-row justify-between items-center">

                            <div class="flex flex-row items-center">
<!--                                <div role="alert" class="alert alert-success px-1 py-0 text-sm ">-->
<!--                                    <svg-->
<!--                                        xmlns="http://www.w3.org/2000/svg"-->
<!--                                        class="h-6 w-6 shrink-0 stroke-current"-->
<!--                                        fill="none"-->
<!--                                        viewBox="0 0 24 24">-->
<!--                                        <path-->
<!--                                            stroke-linecap="round"-->
<!--                                            stroke-linejoin="round"-->
<!--                                            stroke-width="2"-->
<!--                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />-->
<!--                                    </svg>-->
<!--                                    <span class="loading loading-bars loading-xs"></span>-->
<!--                                    <span>正在自动建立电子档案</span>-->
<!--                                </div>-->
<!--                                <div role="alert" class="alert alert-warning px-1 py-0 text-sm">-->
<!--                                    <svg-->
<!--                                        xmlns="http://www.w3.org/2000/svg"-->
<!--                                        class="h-6 w-6 shrink-0 stroke-current"-->
<!--                                        fill="none"-->
<!--                                        viewBox="0 0 24 24">-->
<!--                                        <path-->
<!--                                            stroke-linecap="round"-->
<!--                                            stroke-linejoin="round"-->
<!--                                            stroke-width="2"-->
<!--                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />-->
<!--                                    </svg>-->
<!--                                    <span>请点击档案基本信息手动建档</span>-->
<!--                                </div>-->

                            </div>
                            <div class="flex flex-row gap-4 text-sm">
                                <div class="dropdown dropdown-hover dropdown-left">
                                    <div tabindex="0" role="button" class="link link-primary link-hover">挂号详情</div>
                                    <div
                                        tabindex="0"
                                        class="dropdown-content card card-compact bg-primary text-primary-content z-[1] w-96 p-2 shadow">
                                        <div class="card-body">
                                            <ShowData :data="activePatients.filter((patient) => patient.selected)"/>
                                        </div>
                                    </div>
                                </div>
                                <div>纸质档案号：000100110</div>
                                <div>电子档案号：000100110</div>
                            </div>
                        </div>
                        <div>
                            <!-- Tab Header -->
                            <div class="border-b border-gray-200 dark:border-gray-700">
                                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-gray-500 dark:text-gray-400">
                                    <li
                                        v-for="tab in tabs"
                                        :key="tab.id"
                                        class="me-2"
                                    >
                                        <button
                                            @click="setActiveTab(tab.id)"
                                            :class="[
                                                  'inline-flex items-center justify-center p-4 py-1 border-b-2 rounded-t-lg group',
                                                  activeTab === tab.id
                                                    ? 'text-blue-600 border-blue-600 dark:text-blue-500 dark:border-blue-500'
                                                    : 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300'
                                                ]"
                                            :aria-current="activeTab === tab.id ? 'page' : false"
                                        >
                                            <svg
                                                class="w-4 h-4 me-2"
                                                :class="activeTab === tab.id
                                                ? 'text-blue-600 dark:text-blue-500'
                                                : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300'"
                                                aria-hidden="true"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="currentColor"
                                                :viewBox="tab.iconViewBox"
                                            >
                                                <path :d="tab.iconPath" />
                                            </svg>
                                            {{ tab.name }}
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <!-- Tab Content -->
                            <div id="default-tab-content">
                                <div
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    :class="[
                                              'p-4 rounded-lg bg-gray-50 dark:bg-gray-800',
                                              activeTab === tab.id ? 'block' : 'hidden'
                                            ]"
                                    :id="tab.id"
                                    role="tabpanel"
                                    :aria-labelledby="`${tab.id}-tab`"
                                >
                                    {{ tab.content }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style scoped>
::v-deep(.vtd-datepicker) {
    /* 修改样式 */
    width: 338px;
}
</style>
