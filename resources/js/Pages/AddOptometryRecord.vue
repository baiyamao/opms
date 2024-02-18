<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {ref} from "vue";
const isChecked = ref(true); // 初始为true，表示复选框默认被选中
</script>
<template>
        <Head title="新增视光档案" />

        <AuthenticatedLayout>
            <template #header>
                <h2 class="font-semibold text-gray-800 dark:text-gray-200 leading-tight">新增视光档案</h2>
            </template>

            <div class="py-6">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 lg:w-128">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <form class="mx-auto w-full max-w-xs">
                            <div class="flex flex-row gap-4">
                                <label class="form-control w-full max-w-xs pt-2">

                                    <div class="label py-1">
                                        <span class="label-text">姓名:</span>
                                    </div>
                                    <input type="text" placeholder="" class="input input-sm input-bordered w-full max-w-xs" />
                                </label>
                                <label class="form-control w-full max-w-xs pt-2">
                                    <div class="label py-1">
                                        <span class="label-text">电话:</span>
                                    </div>
                                    <input type="text" placeholder="" class="input input-sm input-bordered w-full max-w-xs" />
                                </label>
                            </div>
                            <label class="form-control w-full max-w-xs pt-2">
                                <div class="label py-1">
                                    <span class="label-text">身份证号码:</span>
                                </div>
                                <input type="text" placeholder="" class="input input-sm input-bordered w-full max-w-xs" />
                            </label>
                            <div class="flex flex-row justify-center pt-2">
                                <label class="label cursor-pointer basis-1/2 justify-center">
                                    <span style="white-space: nowrap;" class="label-text px-1">连续新增</span>
                                    <select class="select select-bordered select-sm w-full max-w-xs py-0">
                                        <option selected>无</option>
                                        <option>CRT</option>
                                        <option>P</option>
                                        <option>阿尔法</option>
                                    </select>
                                </label>
                                <label class="label cursor-pointer basis-1/2 justify-center">
                                    <span class="label-text px-1">自动编号</span>
                                    <input type="checkbox" v-model="isChecked" class="checkbox checkbox-sm" />
                                </label>
                            </div>
                            <label class="form-control w-full max-w-xs pt-2">
                                <div class="label py-1">
                                    <span class="label-text">病历编号:</span>
                                </div>
                            </label>
                            <table class="table table-sm">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>前缀</th>
                                    <th>编号</th>
                                    <th>操作</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>
                                        <select class="select select-bordered select-sm w-full max-w-xs py-0">
                                            <option selected>A</option>
                                            <option>CRT</option>
                                            <option>P</option>
                                            <option>阿尔法</option>
                                        </select>
                                    </td>
                                    <td>3333</td>
                                    <td>新增 删除</td>
                                </tr>
                                </tbody>

                            </table>
                            <div class="flex justify-center ">
                                <button class="btn btn-outline btn-sm btn-error w-full  max-w-xs">添加+</button>
                            </div>

                            <a class="btn btn-outline btn-primary btn-sm my-2 mb-4">保存</a>

                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
</template>
