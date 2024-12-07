<?php

namespace App\Http\Controllers;

use App\Models\SystemAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FindHuZhouFuYouRecordByRegisterRecordController extends Controller
{
    public function systemAccount()
    {
        $systemAccount = SystemAccount::where('system_name', 'huzhoufuyou')->first();

        if (!$systemAccount) {
            return response()->json(['message' => 'System account not found'], 404);
        }
        return response()->json($systemAccount);
    }

    private function huZhouFuYouLogin(Array $aesUserInfo)
    {
        $systemAccount = SystemAccount::where('system_name', 'huzhoufuyou')->first();

        if (!$systemAccount) {
            return response()->json(['message' => 'System account not found'], 404);
        }

        $attemptCount = 0;
        $maxAttempts = 1;

        // 获取aes加密的的用户名和密码
        $username = $aesUserInfo['username'];
        $password = $aesUserInfo['password'];
        $iv = $aesUserInfo['iv'];
        $cookie = $systemAccount->cookie;


        // 构造登录请求的数据
        $data = [
            // 4018010 !H
            'username' => $username,
            'iv' => $iv,
            'password' => $password
        ];


// Step 1: 获取初始 Set-Cookie 信息
//        $initResponse = Http::withHeaders([
//            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
//            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7',
//            'Cache-Control' => 'no-cache',
//            'Connection' => 'keep-alive',
//            'Pragma' => 'no-cache',
//            'Referer' => 'http://10.172.252.142:8082/fypt-web/login',
//            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
//        ])->get('http://10.172.252.142:8082/fypt-web/login');
//
//        // 检查是否成功获取响应
//        if (!$initResponse->successful()) {
//            return response()->json([
//                'message' => 'Failed to fetch initial cookies',
//                'status' => $initResponse->status(),
//                'body' => $initResponse->body()
//            ], 400);
//        }
//
//        // 提取 Set-Cookie
//        $setCookieHeader = $initResponse->headers()['Set-Cookie'] ?? null;
//        if (!$setCookieHeader) {
//            return response()->json([
//                'message' => 'Set-Cookie header not found in initial response'
//            ], 400);
//        }
//
        // Step 2: 将获取到的 Set-Cookie 添加到登录请求中
//        $cookies = [];
//        foreach ($setCookieHeader as $cookie) {
//            $parts = explode(';', $cookie); // Cookie 以分号分隔
//            $keyValue = explode('=', $parts[0]); // 键值对分割
//            $cookies[$keyValue[0]] = $keyValue[1] ?? '';
//        }
        $cookies = [];
        $keyValue = explode('=', $cookie);
        $cookies[$keyValue[0]] = $keyValue[1];
        // Step 3: 发送登录请求
        $response = Http::withHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'http://10.172.252.142:8082',
            'Pragma' => 'no-cache',
            'Referer' => 'http://10.172.252.142:8082/fypt-web/login',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        ])
            ->asForm() // 设置表单提交方式
            ->withCookies($cookies, '10.172.252.142') // 添加 Set-Cookie
            ->post('http://10.172.252.142:8082/fypt-web/login', $data);

        // Step 4: 检查响应是否成功
        // 检查响应
        if ($response->successful() || $response->status() == 302) {
            // 直接返回请求头中的 Cookie 内容
            return response()->json([
                'message' => 'Login successful',
                'session_id' => $cookies, // 请求头中的 Cookie 值
//                'redirect_url' => $response->header('Location') // 如果有跳转地址，返回给前端
            ]);
        } else {
            return response()->json([
                'message' => 'Login failed',
                'status' => $response->status(),
                'body' => $response->body()
            ], 400);
        }
    }

    public function findProfileWithInfo(Request $request)
    {
        $systemAccount = SystemAccount::where('system_name', 'huzhoufuyou')->first();

        if (!$systemAccount) {
            return response()->json(['message' => 'System account not found'], 404);
        }
        // 获取aes加密的的用户名和密码
        $aesUserInfo=[];
        $aesUserInfo['username']=$request->input('username');
        $aesUserInfo['password']=$request->input('password');
        $aesUserInfo['iv']=$request->input('iv');
        $childbookid=$request->input('childbookid');
        $personName=$request->input('personName');
        $motherName=$request->input('motherName');
        $fatherName=$request->input('fatherName');
        $birthday=$request->input('birthday');
        $motherMobile=$request->input('motherMobile');
        $cookie = $systemAccount->cookie;


        // 构造登录请求的数据
        $data = [
            'childbookid'=>$childbookid,
            'personName'=>$personName,
            'idcard'=>$motherName,
            'fatherName'=>$fatherName,
            'birthday'=>$birthday,
            'motherMobile'=>$motherMobile
        ];

        $cookies = [];
        $keyValue = explode('=', $cookie);
        $cookies[$keyValue[0]] = $keyValue[1];
        // Step 3: 发送登录请求
        $response = Http::withHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Origin' => 'http://10.172.252.142:8082',
            'Pragma' => 'no-cache',
            'Referer' => 'http://10.172.252.142:8082/fypt-web/login',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        ])
            ->asForm() // 设置表单提交方式
            ->withCookies($cookies, '10.172.252.142') // 添加 Set-Cookie
            ->post('http://10.172.252.142:8082/fypt-web/ccGeneralInfo/quickFindList', $data);

        // Step 4: 检查响应是否成功
        // 检查响应
        if ($response->status() === 200) {
            // 响应成功，直接返回响应内容
            return response()->json([
                'message' => 'Request successful',
                'data' => $response->body(),
            ]);
        } elseif ($response->status() === 302) {
            // 响应重定向，提取 set-cookie 并存储到数据库
            $setCookieHeader = $response->header('Set-Cookie');
            if ($setCookieHeader) {
                // 匹配 jeesite.session.id 的值
                if (preg_match('/jeesite\.session\.id=([^;]+)/', $setCookieHeader, $matches)) {
                    $sessionId = $matches[1];

                    // 存储到数据库
                    SystemAccount::updateOrCreate(
                        ['system_name' => 'huzhoufuyou'], // 这里替换为实际的 system_name 条件
                        ['cookie' => $sessionId]
                    );

                    $this->huZhouFuYouLogin($aesUserInfo);
                }
            }

            return response()->json([
                'message' => 'Redirect but no valid set-cookie found',
            ], 400);
        } else {
            // 请求失败，返回错误信息
            return response()->json([
                'message' => 'Request failed',
                'status' => $response->status(),
                'body' => $response->body(),
            ], 400);
        }
    }






}


