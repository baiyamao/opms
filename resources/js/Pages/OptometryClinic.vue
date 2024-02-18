<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

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
    // ...根据实际需求添加其他属性
}


const patientData = ref<Patient[]>([]);

// fetchData函数负责从API获取数据，并更新patientData的值
async function fetchData() {
    try {
        const response = await axios.post('/api/get-register-list-with-optometry-record');

        if (response.data && Array.isArray(response.data)) {
            patientData.value = response.data as Patient[];
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
</script>



<template>
    <Head title="视光门诊" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
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

                        <ul class="menu menu-xs lg:menu-horizontal pl-12">
                            <li><a href="/optometry-record/add">新增档案</a></li>
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
                                <th>年龄</th>
                                <th>就诊ID</th>
                                <th>挂号时间</th>
                                <th>挂号电话</th>
                                <th>档案电话</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr
                                v-for="(patient, index) in patientData" :key="patient.opcId" class="hover">
                                <th>
                                    <label>
                                        <input type="checkbox" class="checkbox checkbox-xs" />
                                    </label>
                                </th>
                                <td>{{ patient.mZSJ }}</td>
                                <td>{{ patient.regName }}</td>
                                <td :class="patient.state === '1' ? 'bg-red-400' : (patient.state === '3' ? '' : 'bg-green-400')">
                                    {{ patient.state === '1' ? '诊中' : (patient.state === '3' ? '诊毕' : '待诊') }}
                                </td>
                                <td :class="patient.info_check ==='强相关'?'':'text-red-600'"
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
                                <td>
                                    <div v-if="patient.info_check !==undefined">
                                        {{ patient.info_check === "多个相关记录" ?'':(patient.optometry_record[0]?.name ?? '') }}
                                    </div>
                                </td>
                                <td>{{ patient.patName }}</td>
                                <td>{{ patient.sex === 1 ? '男' : '女' }}</td>
                                <td>{{ patient.age }}</td>
                                <td>{{ patient.cardData }}</td>
                                <td>{{ patient.patRegTime }}</td>
                                <td>{{ patient.telePhone }}</td>
                                <td>
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
                                <th>类别</th>
                                <th>状态</th>
                                <th>病历编号</th>
                                <th>档案姓名</th>
                                <th>挂号姓名</th>
                                <th>性别</th>
                                <th>年龄</th>
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
