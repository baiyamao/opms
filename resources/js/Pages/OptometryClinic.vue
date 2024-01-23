<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

// 定义接口类型来描述从 API 获取的病人数据的结构
interface Patient {
    opcId: string;
    patRegTime: string; // 挂号时间
    mZSJ: string;       // 序号（假设为某种标识符）
    regName: string;    // 类别
    state: string;      // 状态
    optometry_record_medical_record_number: string; // 病历编号
    optometry_record_name: string; // 档案姓名
    patName: string;    // 挂号姓名
    sex: string;        // 性别
    cardData: string;   // 就诊ID
    telePhone: string;  // 挂号电话
    optometry_record_phone: string; // 档案电话
    info_check:string;//一致性检查
    // ...根据实际需求添加其他属性
}

const patientData = ref<Patient[]>([]);

// fetchData函数负责从API获取数据，并更新patientData的值
async function fetchData() {
    try {
        const response = await axios.post('/api/get-register-list-with-optometry-record');
        // 检查响应数据是否为数组
        if (response.data && Array.isArray(response.data)) {
            // 使用断言来确保数据类型是Patient[]
            patientData.value = response.data as Patient[];
            // 对数据按照挂号时间进行排序
            patientData.value.sort((a, b) => new Date(b.patRegTime).getTime() - new Date(a.patRegTime).getTime());
        } else {
            console.error('Invalid data format received:', response.data);
        }
    } catch (error) {
        console.error('Error fetching data:', error);
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
</script>



<template>
    <Head title="视光门诊" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <ul class="menu menu-xs lg:menu-horizontal pl-12">
                            <li><a>新增档案</a></li>
                            <li><a>编辑档案</a></li>
                            <li><a>查看挂号信息</a></li>
                            <li><a>隐藏</a></li>
                            <li><a>取消隐藏</a></li>

                        </ul>
                        <table class="table table-sm table-zebra table-pin-rows">
                            <thead>
                            <tr>
                                <th>
                                    <label>
                                        <input type="checkbox" class="checkbox checkbox-xs" />
                                    </label>
                                </th>
                                <th>序号</th>
                                <th>类别</th>
                                <th>状态</th>
                                <th>病历编号</th>
                                <th>档案姓名</th>
                                <th>挂号姓名</th>
                                <th>性别</th>
                                <th>就诊ID</th>
                                <th>挂号时间</th>
                                <th>挂号电话</th>
                                <th>档案电话</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr :class="patient.state === '1' ? 'text-red-500' : (patient.state === '3' ? 'text-stone-500' : '')"
                                v-for="(patient, index) in patientData" :key="patient.opcId" class="hover">
                                <th>
                                    <label>
                                        <input type="checkbox" class="checkbox checkbox-xs" />
                                    </label>
                                </th>
                                <td>{{ patient.mZSJ }}</td>
                                <td>{{ patient.regName }}</td>
                                <td :class="patient.state === '1' ? '' : (patient.state === '3' ? '' : 'text-emerald-400')">
                                    {{ patient.state === '1' ? '诊中' : (patient.state === '3' ? '诊毕' : '待诊') }}
                                </td>
                                <td :class="patient.info_check ==='强相关'?'':'text-red-600'"
                                    >
                                    <div v-if="patient.info_check ==='强相关'">
                                        {{ patient.optometry_record_medical_record_number }}
                                    </div>
                                    <div v-else-if="patient.info_check !==undefined" class="tooltip tooltip-error" :data-tip="patient.info_check">
                                        <button class="link relative">
                                            {{ patient.optometry_record_medical_record_number }}
                                            <span
                                                style="line-height: 0.1;"
                                                class="absolute top-[-0.2rem] right-[-0.6rem] py-1 bg-red-500/60
                                                    text-white rounded-full h-3 w-3 flex items-center
                                                    justify-center text-sm" >&times;
                                                ️</span>
                                        </button>
                                    </div>
                                </td>
                                <td>{{ patient.optometry_record_name }}</td>
                                <td>{{ patient.patName }}</td>
                                <td>{{ patient.sex === '1' ? '男' : '女' }}</td>
                                <td>{{ patient.cardData }}</td>
                                <td>{{ patient.patRegTime }}</td>
                                <td>{{ patient.telePhone }}</td>
                                <td>{{ patient.optometry_record_phone }}</td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr>
                                <th></th>
                                <th>序号</th>
                                <th>类别</th>
                                <th>状态</th>
                                <th>病历编号</th>
                                <th>档案姓名</th>
                                <th>挂号姓名</th>
                                <th>性别</th>
                                <th>就诊ID</th>
                                <th>挂号时间</th>
                                <th>挂号电话</th>
                                <th>档案电话</th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
