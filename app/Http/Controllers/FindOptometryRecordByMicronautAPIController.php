<?php

namespace App\Http\Controllers;

use App\Models\OptometryRecord;
use App\Models\SystemAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FindOptometryRecordByMicronautAPIController extends Controller
{
    //根据日期显示挂号列表
    public function getRegisterListByDate(Request $request)
    {
        // 根据请求参数决定是否使用带日期或收费号码的 URL
        $showRegDate = $request->input('regDate', false);
        //registrations?regDate=20250209 registrations?chargeNo=221115202000004

        if ($showRegDate) {
            // 目标 URL
            //%2C2020005->儿保 %2C2020011->专家免费

            $url = "http://10.172.151.159:8080/registrations?regDate=$showRegDate";
        } else {
            $url = 'http://10.172.151.159:8080/registrations';
        }

        return $this->getRegisterList($url);
    }

    //根据收费号码查找挂号患者信息
    public function searchSingleRegisterInfoByChargeNo(Request $request)
    {
        $showChargeNo = $request->input('chargeNo', false);
        //registrations?regDate=20250209 registrations?chargeNo=221115202000004

        if ($showChargeNo) {
            // 目标 URL
            //%2C2020005->儿保 %2C2020011->专家免费
            // 使用带儿保的 URL
            $url = "http://10.172.151.159:8080/registrations?chargeNo=$showChargeNo";
        } else {
            return response()->json(['message' => '未提供chargeNo参数'], 500);
        }

        return $this->getRegisterList($url);
    }


    /**
     * 获取挂号列表并附加视光档案信息。
     */
    // 提取重复代码到私有方法中
    private function getRegisterList($url)
    {

        // 发起 GET 请求
        $response = Http::timeout(10)->get($url);

        // 处理 HTTP 请求失败
        if ($response->failed()) {
            return response()->json([
                'message' => '挂号系统接口异常，错误码：' . $response->status(),
                'data' => null
                ], $response->status());
        }

        // 检查响应成功的挂号信息
        if ($response->successful()) {
            return $this->appendOptometryRecords($response->json());
        }

        // 请求失败返回错误消息
        return response()->json(['message' => 'Failed to retrieve data in getRegisterList'], 500);
    }

    /**
     * 附加视光档案信息到获取的挂号列表中。
     */
    private function appendOptometryRecords($data)
    {
        foreach ($data as &$item) {
            // 开始查询
            $query = OptometryRecord::query();

            // 添加“或”条件
            if (isset($item['patName'])) {
                $query->orWhere('name', $item['patName']);
            }
            if (isset($item['telePhone'])) {
                $query->orWhere('phone', $item['telePhone']);
            }
            if (isset($item['cardData']) && $this->isValidChineseID($item['cardData'])) {
                $query->orWhere('resident_id_number', $item['cardData']);
            }

            // 执行搜索
            $optometryRecords = $query->get();
            //病历信息一致性检查
            $infoCheck = [];
            // 基于结果数量或特定条件执行不同的逻辑
            if (!$optometryRecords->isEmpty()) {
                // 找到记录时的逻辑
                // 例如，可以根据记录的数量或特定字段来决定接下来的操作
                if ($optometryRecords->count() == 1) {
                    // 找到一个记录时的逻辑
                    $record = $optometryRecords->first();
                    if (isset($item['cardData'])
                        && $this->isValidChineseID($item['cardData'])
                        && $record->resident_id_number == $item['cardData']){
                        // 如果身份证匹配，则添加到过滤后的记录中
                        $infoCheck['info_check'] = "强相关";
                    }else{
                        if ($record->name == $item['patName'] && $record->phone == $item['telePhone']) {
                            $infoCheck['info_check'] = "强相关";
                            // 验证 cardData 是否符合中国居民身份证号码格式
                            if (isset($item['cardData']) && $this->isValidChineseID($item['cardData']) && empty($record->resident_id_number)) {
                                $record->resident_id_number = $item['cardData'];
                                $record->save(); // 保存更新
                            }
                        } else {
                            if ($record->name != $item['patName']){
                                $infoCheck['info_check'] = "姓名不一致";
                            }
                            if ($record->phone != $item['telePhone']){
                                $infoCheck['info_check'] = "电话不一致";
                            }
                        }
                    }

                } else {
                    // 初始化一个空数组来存储符合条件的记录
                    $filteredRecords = [];

                    foreach ($optometryRecords as $record) {
                        if (isset($item['cardData'])
                            && $this->isValidChineseID($item['cardData'])
                            && $record->resident_id_number == $item['cardData']){
                            // 如果身份证匹配，则添加到过滤后的记录中
                            $filteredRecords[] = $record;
                            $infoCheck['info_check'] = "强相关";
                        }else{
                            if ($record->name == $item['patName'] && $record->phone == $item['telePhone']) {


                                if (!empty($filteredRecords)) {//如果有多个强相关记录
                                    $infoCheck['info_check'] = "多个相关记录";
                                }else{
                                    $infoCheck['info_check'] = "强相关";
                                }

                                // 如果姓名和电话都匹配，则添加到过滤后的记录中
                                $filteredRecords[] = $record;

                                // 验证 cardData 是否符合中国居民身份证号码格式
                                if (isset($item['cardData']) && $this->isValidChineseID($item['cardData']) && empty($record->resident_id_number)) {
                                    $record->resident_id_number = $item['cardData'];
                                    $record->save(); // 保存更新
                                }
                            } else {
                                if (empty($filteredRecords)) {
                                    $infoCheck['info_check'] = "多个相关记录";
                                }

                            }
                        }

                    }
                    if (!empty($filteredRecords)) {
                        $optometryRecords = $filteredRecords;
                    }

                }
            }
            $item = array_merge($item, $infoCheck);
            $item = array_merge($item, $this->formatOptometryRecords($optometryRecords));
        }

        // 返回处理后的数据
        return response()->json($data);
    }


    /**
     * 格式化视光档案记录，用于附加到原始数据中。
     */
    private function formatOptometryRecords($optometryRecords)
    {
        $formatted = [];
        $formatted['optometry_record']=[];

        foreach ($optometryRecords as $record) {
            // 将每个记录作为一个独立元素添加到数组中
            $formatted['optometry_record'][] = $record;
        }

        // 返回格式化后的记录
        return $formatted;
    }

    /**
     * 检查字符串是否符合中国居民身份证号码格式
     */
    private function isValidChineseID($id)
    {
        return preg_match("/^\d{15}(\d{2}[\dXx])?$/i", $id);
    }
}
