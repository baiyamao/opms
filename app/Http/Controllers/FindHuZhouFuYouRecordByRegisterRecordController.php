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
            ->withoutRedirecting() // 禁止自动跟随重定向
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

        if ($this->containsJeesiteSessionId($cookie)) {
            $keyValue = explode('=', $cookie);
            $cookies[$keyValue[0]] = $keyValue[1];
        }

        // Step 3: 发送数据查询请求
        $response = $this->sendHttpRequest(
            'http://10.172.252.142:8082/fypt-web/ccGeneralInfo/quickFindList',
            $data,
            $cookies,
            'POST'
        );

        // Step 4: 检查响应是否成功
        // 检查响应
        if ($response->status() === 200) {
            // 响应成功，直接返回响应内容
            return response()->json([
                'message' => 'Request successful',
                'data' => $response->json(),
            ]);
        } elseif ($response->status() === 302) {
            // 响应重定向，提取 set-cookie 并存储到数据库
            $setCookieHeader = $response->header('Set-Cookie');
            if ($setCookieHeader) {
                // 匹配 jeesite.session.id 的值
                $sessionId = $this->extractCookie($setCookieHeader, 'jeesite.session.id');
                if ($sessionId) {
                    // 存储到数据库
                    SystemAccount::updateOrCreate(
                        ['system_name' => 'huzhoufuyou'],
                        ['cookie' => $sessionId]
                    );

                    // 重新登录并发起获取数据的请求
                    $loginResult = $this->huZhouFuYouLogin($aesUserInfo);
                    if ($loginResult->getStatusCode() == 400) {
                        // 登录失败
                        return $loginResult;
                    }
                    // 使用新登录的 Cookie 重新发起数据查询请求
                    $cookies = [];

                    if ($this->containsJeesiteSessionId($sessionId)) {
                        $keyValue = explode('=', $sessionId);
                        $cookies[$keyValue[0]] = $keyValue[1];
                    }
                    $newResponse=$this->sendHttpRequest(
                        'http://10.172.252.142:8082/fypt-web/ccGeneralInfo/quickFindList',
                        $data,
                        $cookies,
                        'POST'
                    );
                    if ($newResponse->successful()) {
                        // 返回最终数据和登录结果
                        return response()->json([
                            'message' => 'Request successful after login',
                            'loginResult' => 'Login successful',
                            'data' => $newResponse->json(),
                        ]);
                    }
                    // 数据查询失败
                    return response()->json([
                        'message' => 'Data request failed after login',
                        'status' => $newResponse->status(),
                        'body' => $newResponse->body(),
                    ], 400);
                }
            }
            //如果set-cookie为空，说明数据库已有有效的cookie，可直接发起登录
            $loginResult = $this->huZhouFuYouLogin($aesUserInfo);
            if ($loginResult->getStatusCode() == 400) {
                // 登录失败
                return $loginResult;
            }
            // 使用新登录的 Cookie 重新发起数据查询请求
            $cookies = [];
            $systemAccount = SystemAccount::where('system_name', 'huzhoufuyou')->first();

            if (!$systemAccount) {
                return response()->json(['message' => 'System account not found'], 404);
            }
            $cookie = $systemAccount->cookie;

            if ($this->containsJeesiteSessionId($cookie)) {
                $keyValue = explode('=', $cookie);
                $cookies[$keyValue[0]] = $keyValue[1];
            }
            $newResponse=$this->sendHttpRequest(
                'http://10.172.252.142:8082/fypt-web/ccGeneralInfo/quickFindList',
                $data,
                $cookies,
                'POST'
            );
            if ($newResponse->successful()) {
                // 返回最终数据和登录结果
                return response()->json([
                    'message' => 'Request successful after login',
                    'loginResult' => 'Login successful',
                    'data' => $newResponse->json(),
                ]);
            }
            // 数据查询失败
            return response()->json([
                'message' => 'Data request failed after login',
                'status' => $newResponse->status(),
                'body' => $newResponse->body(),
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

    private function extractCookie($header, $key) {
        if (preg_match("/($key=[^;]+)/", $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * 检测字符串中是否包含指定的键名 'jeesite.session.id'
     *
     * @param string $cookie 要检测的字符串
     * @return bool 返回 true 表示包含，false 表示不包含
     */
    private function containsJeesiteSessionId(string $cookie): bool
    {
        // 使用 strpos 检查是否包含指定字符串
        return str_contains($cookie, 'jeesite.session.id=');
    }

    private function sendHttpRequest(string $url, array $data, array $cookies = [], string $method = 'POST')
    {
        $headers = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Content-Type' => 'application/json;charset=UTF-8',
            'Origin' => 'http://10.172.252.142:8082',
            'Pragma' => 'no-cache',
            'Referer' => 'http://10.172.252.142:8082/fypt-web/login',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        ];

        $request = Http::withHeaders($headers)
            ->withCookies($cookies, '10.172.252.142') // 添加 Set-Cookie
            ->withoutRedirecting(); // 禁止自动跟随重定向

        if ($method === 'POST') {
            $response = $request->withBody(json_encode($data), 'application/json')->post($url);
        } elseif ($method === 'GET') {
            $response = $request->get($url, $data); // 使用查询参数
        } else {
            throw new InvalidArgumentException("Unsupported HTTP method: $method");
        }

        return $response;
    }






}


