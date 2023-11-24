<script setup lang="ts">
import { ref, onMounted, Ref } from 'vue';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

interface SystemAccount {
    id: number;
    system_name: string;
    account: string;
    password: string;
    cookie: string;
}

interface Props {
    mustVerifyEmail?: boolean;
    status?: string;
}

const props = defineProps<Props>();

const systemAccounts: Ref<SystemAccount[]> = ref([]);
const fetchSystemAccounts = async () => {
    try {
        const response = await axios.get('/api/system-accounts');
        systemAccounts.value = response.data ?? [];
    } catch (error) {
        console.error(error);
    }
};

const editingId: Ref<number | null> = ref(null);

const startEdit = (id: number) => {
    editingId.value = id;
};

const saveEdit = async (account) => {
    try {
        // 发送 PUT 请求到服务器，这里的 URL 和请求体需要根据您的 API 进行调整
        await axios.put(`/api/system-accounts/${account.id}`, account);
        // 数据更新成功后，重新加载系统账号列表
        await fetchSystemAccounts();
        // 退出编辑模式
        editingId.value = null;
    } catch (error) {
        console.error(error);
        // 可以添加一些错误处理逻辑，比如显示错误消息
    }
};

const cancelEdit = () => {
    editingId.value = null;
};

onMounted(() => {
    fetchSystemAccounts();
    // console.log(systemAccounts.value); // 调试输出
});

// 添加更多方法（新增、编辑、删除）
</script>

<template>
    <Head title="系统账号管理" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">系统账号管理</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <button class="mb-4 bg-blue-400">新增系统账号</button>
                    <table class="border border-collapse border-gray-300">
                        <thead>
                        <tr>
                            <th class="border border-gray-300 px-4 py-2 text-left">系统名称</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">账号</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">密码</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">Cookie</th>
                            <th class="border border-gray-300 px-4 py-2 text-left">操作</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="account in systemAccounts" :key="account.id" class="border-b border-gray-300">
                            <td>
                                <span v-if="editingId !== account.id">{{ account.system_name }}</span>
                                <input v-else v-model="account.system_name" class="bg-green-300 w-full h-full border-0 p-0 text-left" />
                            </td>
                            <td>
                                <span v-if="editingId !== account.id">{{ account.account }}</span>
                                <input v-else v-model="account.account" class="bg-green-300 w-full h-full border-0 p-0 text-left" />
                            </td>
                            <td>
                                <span v-if="editingId !== account.id">{{ account.password }}</span>
                                <input v-else v-model="account.password" class="bg-green-300 w-full h-full border-0 p-0 text-left" />
                            </td>
                            <td>
                                <span v-if="editingId !== account.id">{{ account.cookie }}</span>
                                <input v-else v-model="account.cookie" class="bg-green-300 w-full h-full border-0 p-0 text-left" />
                            </td>
                            <td>
                                <button v-if="editingId !== account.id" @click="startEdit(account.id)">修改</button>
                                <button v-else @click="saveEdit(account)">保存</button>
                                <button v-if="editingId !== account.id" @click="deleteAccount(account.id)">删除</button>
                                <button v-else @click="cancelEdit">取消</button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
