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

// Update the saveEdit function to specify the type of 'account' parameter
const saveEdit = async (account: SystemAccount) => {
    try {
        await axios.put(`/api/system-accounts/${account.id}`, account);
        await fetchSystemAccounts();
        editingId.value = null;
    } catch (error) {
        console.error(error);
    }
};

const cancelEdit = () => {
    editingId.value = null;
};

// Define the deleteAccount function
const deleteAccount = async (id: number) => {
    try {
        await axios.delete(`/api/system-accounts/${id}`);
        await fetchSystemAccounts();
    } catch (error) {
        console.error(error);
    }
};

onMounted(() => {
    fetchSystemAccounts();
});
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
                    <div class="overflow-x-auto">
                        <!-- Open the modal using ID.showModal() method -->
                        <dialog id="my_modal_1" class="modal">
                            <div class="modal-box">
                                <h3 class="font-bold text-lg">Hello!</h3>
                                <p class="py-4">Press ESC key or click the button below to close</p>
                                <div class="modal-action">
                                    <form method="dialog">
                                        <!-- if there is a button in form, it will close the modal -->
                                        <button class="btn">Close</button>
                                    </form>
                                </div>
                            </div>
                        </dialog>
                        <ul class="menu lg:menu-horizontal pl-12">
                            <li><a onclick="my_modal_1.showModal()">新增</a></li>
                            <li><a>编辑</a></li>
                            <li><a>删除</a></li>
                        </ul>
                        <table class="table">
                            <thead>
                            <tr>
                                <th>
                                    <label>
                                        <input type="checkbox" class="checkbox checkbox-sm" />
                                    </label>
                                </th>
                                <th>ID</th>
                                <th>系统名称</th>
                                <th>账号</th>
                                <th>密码</th>
                                <th>Cookie</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="account in systemAccounts" :key="account.id">
                                <th>
                                    <label>
                                        <input type="checkbox" class="checkbox checkbox-sm" />
                                    </label>
                                </th>
                                <td>
                                    <span v-if="editingId !== account.id">{{ account.id }}</span>
                                    <input v-else v-model="account.id" />
                                </td>
                                <td>
                                    <span v-if="editingId !== account.id">{{ account.system_name }}</span>
                                    <input v-else v-model="account.system_name" />
                                </td>
                                <td>
                                    <span v-if="editingId !== account.id">{{ account.account }}</span>
                                    <input v-else v-model="account.account" />
                                </td>
                                <td>
                                    <span v-if="editingId !== account.id">{{ account.password }}</span>
                                    <input v-else v-model="account.password" />
                                </td>
                                <td>
                                    <span v-if="editingId !== account.id">{{ account.cookie }}</span>
                                    <input v-else v-model="account.cookie" />
                                </td>
                                <td>
                                    <button  v-if="editingId !== account.id" @click="startEdit(account.id)" class="btn btn-ghost">修改</button>
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
        </div>
    </AuthenticatedLayout>
</template>
