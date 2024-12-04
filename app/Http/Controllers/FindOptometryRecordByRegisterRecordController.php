<?php

namespace App\Http\Controllers;

use App\Models\OptometryRecord;
use App\Models\SystemAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FindOptometryRecordByRegisterRecordController extends Controller
{
    public function wdhisLogin(Request $request)
    {
        $systemAccount = SystemAccount::where('system_name', 'wdhis')->first();

        if (!$systemAccount) {
            return response()->json(['message' => 'System account not found'], 404);
        }

        $loginUrl = 'http://10.172.153.25:8080/wdhis-core-web/login-new/doAuth';
        $attemptCount = 0;
        $maxAttempts = 1;

        while ($attemptCount < $maxAttempts) {
            $attemptCount++;
            $response = $this->attemptLogin($systemAccount, $loginUrl);

            if ($response->successful()) {
                $responseData = $response->json();

                // 检查登录是否成功
                if (isset($responseData['status']) && $responseData['status'] === 0) {
                    $newCookie = $this->extractCookieValueFromResponse($response);

                    if ($newCookie) {
                        // 更新数据库中的 cookie
                        $systemAccount->update(['cookie' => $newCookie]);
                        return response()->json(['message' => 'Login successful', 'cookie' => $newCookie]);
                    }

                    return response()->json(['message' => '成功登录']);
                } else if (isset($responseData['msg']) && $responseData['status'] === 12910) {
                    //密码过期
                    return response()->json(['message' => 'wdhis'.$responseData['msg']], 401);
                } else if (isset($responseData['msg']) && $responseData['status'] === 12908) {
                    return response()->json(['message' => 'wdhis'.$responseData['msg']], 401);
                } else if (isset($responseData['msg']) && $responseData['status'] === 12903) {
                    //用户名或密码错误
                    return response()->json(['message' => 'wdhis'.$responseData['msg']], 401);
                }
            }
        }

        return response()->json(['message' => 'wdhis未响应'], 401);
    }

    private function attemptLogin($systemAccount, $loginUrl)
    {
        $headers = [];

        // 检查系统账号是否有有效的 cookie，并将其添加到请求头中
        if (!empty($systemAccount->cookie)) {
            $headers['Cookie'] = $systemAccount->cookie;
        }

        // 如果密码已经是 MD5 加密的，直接使用；否则，进行 MD5 加密
        $encryptedPassword = $systemAccount->password;
        if (!preg_match('/^[a-f0-9]{32}$/', $encryptedPassword)) {
            $encryptedPassword = md5($encryptedPassword);
        }

        return Http::withHeaders($headers)->asForm()->post($loginUrl, [
            'code' => $systemAccount->account,
            'password' => $encryptedPassword,
            'branchCode' => '1233052100',
            'bindComputer' => 'true'
        ]);
    }

    private function extractCookieValueFromResponse($response)
    {
        $responseHeaders = $response->headers();

        if (isset($responseHeaders['Set-Cookie'])) {
            foreach ($responseHeaders['Set-Cookie'] as $setCookieHeader) {
                $cookieValue = $this->parseCookieValue($setCookieHeader, 'WDJSESSIONID');
                if ($cookieValue) {
                    return "WDJSESSIONID=$cookieValue";
                }
            }
        }

        return null;
    }


    private function parseCookieValue($setCookieHeader, $cookieName)
    {
        $parts = explode(';', $setCookieHeader);
        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                list($name, $value) = explode('=', $part, 2);
                if (trim($name) === $cookieName) {
                    return trim($value);
                }
            }
        }

        return null;
    }

    public function getRegisterListWithOption(Request $request)
    {
        // 根据请求参数决定是否使用带儿保的 URL
        $showErbao = $request->input('showErbao', false);

        if ($showErbao) {
            // 目标 URL
            //%2C2020005->儿保 %2C2020011->专家免费
            // 使用带儿保的 URL
            $url = 'http://10.172.153.25:8085/wdhis-outpat-web/pat/list/getRegisterList?area=N&state=0&reExamDays=0&empId=202000028&empRegDeptIds=2020001%2C2020005%2C2020006%2C2020007%2C2020008%2C2020009%2C2020010&lookSelf=false';
        } else {
            // 使用默认不含儿保的 URL
            $url = 'http://10.172.153.25:8085/wdhis-outpat-web/pat/list/getRegisterList?area=N&state=0&reExamDays=0&empId=202000028&empRegDeptIds=2020001%2C2020006%2C2020007%2C2020008%2C2020009%2C2020010&lookSelf=false';
        }

        return $this->getRegisterList($url);
    }


    /**
     * 获取挂号列表并附加视光档案信息。
     */
    // 提取重复代码到私有方法中
    private function getRegisterList($url)
    {
        // 获取系统账号及其 cookie
        $systemAccount = SystemAccount::where('system_name', 'wdhis')->firstOrFail();
        $cookie = $systemAccount->cookie;

        // 发起 GET 请求
        $response = Http::withHeaders(['Cookie' => $cookie])->get($url);

        // 检查响应是否成功
        if ($response->successful()) {
            $responseData = $response->json();

            // 判断响应数据状态
            if ($responseData['status'] == 0) {
                // 数据获取成功，处理数据
                $data = $responseData['body']['data'] ?? [];
                return $this->appendOptometryRecords($data);
            } else {
                // 数据获取失败，尝试重新登录
                $loginResponse = $this->wdhisLogin(new Request());

                // 检查登录响应是否成功
                if ($loginResponse->status() != 200) {
                    // 登录失败返回信息
                    $loginMessage = json_decode($loginResponse->content(), true)['message'] ?? 'wdhis登录失败，请使用系统账号管理中用户名和密码尝试登录his系统查找原因';
                    return response()->json(['message' => $loginMessage], 401);
                }

                // 如果登录成功，再次尝试获取注册列表
                return $this->getRegisterList($url);
            }
        }

        // 请求失败返回错误消息
        return response()->json(['message' => 'Failed to retrieve data'], 500);
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


