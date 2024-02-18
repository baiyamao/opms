<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="视光档案管理" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-gray-800 dark:text-gray-200 leading-tight">视光档案管理</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 lg:w-128">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="menu menu-horizontal rounded-box">
                        <li><a href="add">新增</a></li>
                    </ul>
                    <div class="overflow-x-auto h-96">
                        <table class="table table-xs table-pin-rows table-pin-cols">
                            <thead>
                            <tr>
                                <th>患者ID</th>
                                <td>姓名</td>
                                <td>性别</td>
                                <td>病历编号</td>
                                <td>RID</td>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <th>1</th>
                                <td>测试的
                                    <br/>

                                </td>
                                <td>男</td>
                                <td>
                                    <span style="white-space: nowrap;" class="badge badge-ghost badge-sm">A-3333</span>
                                    <span style="white-space: nowrap;" class="badge badge-ghost badge-sm">CRT-9999</span>
                                </td>
                                <td>有</td>
                            </tr>
                            <tr>
                                <th>1</th>
                                <td>测试的
                                    <br/>

                                </td>
                                <td>男</td>
                                <td>
                                    <span class="badge badge-ghost badge-sm">A-3333</span>
                                </td>
                            </tr>
                            <tr>
                                <th>3</th>
                                <td>Brice Swyre</td>
                                <td>Tax Accountant</td>
                                <td>Carroll Group</td>
                            </tr>
                            <tr>
                                <th>4</th>
                                <td>Marjy Ferencz</td>
                                <td>Office Assistant I</td>
                                <td>Rowe-Schoen</td>
                            </tr>
                            <tr>
                                <th>5</th>
                                <td>Yancy Tear</td>
                                <td>Community Outreach Specialist</td>
                                <td>Wyman-Ledner</td>
                            </tr>
                            <tr>
                                <th>6</th>
                                <td>Irma Vasilik</td>
                                <td>Editor</td>
                                <td>Wiza, Bins and Emard</td>
                            </tr>
                            <tr>
                                <th>7</th>
                                <td>Meghann Durtnal</td>
                                <td>Staff Accountant IV</td>
                                <td>Schuster-Schimmel</td>
                            </tr>
                            <tr>
                                <th>8</th>
                                <td>Sammy Seston</td>
                                <td>Accountant I</td>
                                <td>O'Hara, Welch and Keebler</td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr>
                                <th></th>
                                <td>Name</td>
                                <td>Job</td>
                                <td>company</td>
                                <th></th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
