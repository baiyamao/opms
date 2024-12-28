<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { getRandomIV, encryptAES } from '@/utils/crypto-aes';

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
    mZSJ: string;       // 序号（假设为某种标识符）
    regName: string;    // 类别
    state: string;      // 状态
    optometry_record: OptometryRecord[]; // 视光档案数组
    patName: string;    // 挂号姓名
    sex: number;        // 性别
    age: string;        // 年龄
    cardData: string;   // 就诊ID
    telePhone: string;  // 挂号电话
    info_check:string;//一致性检查
    selected: boolean;
    // ...根据实际需求添加其他属性
}


const patientData = ref<Patient[]>([]);
const showErbao = ref<boolean>(false); // 控制是否显示儿保挂号
const showFinish = ref<boolean>(true); // 控制是否显示诊毕

// 切换显示儿保挂号
function toggleShowErbao() {
    showErbao.value = !showErbao.value;
    fetchData(); // 每次切换后重新获取数据
}

// 切换显示诊毕
function toggleShowFinish() {
    showFinish.value = !showFinish.value;
    fetchData(); // 每次切换后重新获取数据
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

// 切换多选模式
const toggleMultiSelect = () => {
    isMultiSelect.value = !isMultiSelect.value;
    isSingleSelect.value = false;

    // 如果退出多选模式，清除所有选中状态
    if (!isMultiSelect.value) {
        patientData.value.forEach(row => (row.selected = false));
        isSingleSelect.value = false;
    }
};

// 选中行的索引
const selectedRowIndex = ref<number | null>(null);

// 单选模式下单击行
const toggleSingleSelect = (index: number) => {
    if(patientData.value[index].selected){//再次单击取消选择
        patientData.value[index].selected = !patientData.value[index].selected;
        isSingleSelect.value = false;
    }else{
        patientData.value.forEach((row, i) => (row.selected = i === index));
        isSingleSelect.value = true;
    }

};

// 多选模式下切换选中状态
const toggleRowSelection = (index: number) => {
    patientData.value[index].selected = !patientData.value[index].selected;
};
// 单击复选框处理（防止双击和手动选中冲突）
const onCheckboxChange = (index: number, checked: boolean) => {
    patientData.value[index].selected = checked;
};

//全选和取消全选
const toggleAll = (event: Event) => {
    const checked = (event.target as HTMLInputElement).checked;
    patientData.value.forEach(row => {
        row.selected = checked;
    });
};

// fetchData函数负责从API获取数据，并更新patientData的值
async function fetchData() {
    try {
        const response = await axios.post('/api/get-register-list-with-optometry-record',{
            showErbao: showErbao.value,
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
</script>



<template>
    <Head title="视光门诊" />

    <AuthenticatedLayout class="overflow-hidden h-screen">
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
                        <table class="table table-sm table-pin-rows h-full">
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
                                v-for="(patient, index) in patientData"
                                :key="patient.opcId"
                                v-show="patient.state !== '3'||showFinish"
                                @click="isMultiSelect ? toggleRowSelection(index) : toggleSingleSelect(index)"
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
                                <td>{{ patient.mZSJ }}</td>
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
                        <div role="tablist" class="tabs tabs-lifted">
                            <input type="radio" name="my_tabs_2" role="tab" class="tab" aria-label="Tab 1" />
                            <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
                                Tab content 1
                            </div>

                            <input
                                type="radio"
                                name="my_tabs_2"
                                role="tab"
                                class="tab"
                                aria-label="挂号详情"
                                checked="checked" />
                            <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
                                {{patientData.filter((patient) => patient.selected)}}
                            </div>

                            <input type="radio" name="my_tabs_2" role="tab" class="tab" aria-label="Tab 3" />
                            <div role="tabpanel" class="tab-content bg-base-100 border-base-300 rounded-box p-6">
                                Tab content 3
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
<style scoped>
input[type="radio"]:focus {
    outline: none;
    box-shadow: none;
}
</style>
