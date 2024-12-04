<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FindHuZhouFuYouRecordByRegisterRecordController extends Controller
{
    public function huZhouFuYouLogin(Request $request)
    {
        // 获取表单输入的用户名和密码
//        $username = $request->input('username');
//        $password = $request->input('password');
        $iv = $request->input('iv');


        // 构造登录请求的数据
        $data = [
            // 4018010 !H
            'username' => "4018010",
            'iv' => $iv,
            'password' => "!Hatey0u"
        ];

        // 示例数据
        $key = '385f33cb91484b04a177828829081ab7'; // 与JavaScript中一致的密钥（32字符对应16字节）

        // 发送登录请求
//        $response = Http::withHeaders([
//            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
//            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7',
//            'Cache-Control' => 'no-cache',
//            'Connection' => 'keep-alive',
//            'Content-Type' => 'application/x-www-form-urlencoded',
//            'Origin' => 'http://10.172.252.142:8082',
//            'Pragma' => 'no-cache',
//            'Referer' => 'http://10.172.252.142:8082/fypt-web/login',
//            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
//        ])
//            ->asForm()  // 设置表单提交方式
//            ->withCookies([
//                'jeesite.session.id' => '755dde4f-f4c5-4ae4-a5e4-c988ee1ef254'  // 如果有其他 session id
//            ], '10.172.252.142')  // 设置 Cookie 域名
//            ->post('http://10.172.252.142:8082/fypt-web/login', $data);

        return response()->json($this->encryptData($data,$key), 200);

        // 检查响应是否成功
//        if ($response->successful()) {
//            // 获取 jeesite.session.id cookie
//            $cookies = $response->cookies();
//            $sessionId = $cookies->get('jeesite.session.id', null); // 获取 jeesite.session.id
//
//            if ($sessionId) {
//                return response()->json([
//                    'message' => 'Login successful',
//                    'session_id' => $sessionId
//                ]);
//            } else {
//                return response()->json([
//                    'message' => 'Login successful, but session id not found'
//                ], 200);
//            }
//        } else {
//            return response()->json([
//                'message' => 'Login failed',
//                'status' => $response->status(),
//                'body' => $response->body()
//            ], 400);
//        }
    }


    // AES 加密函数
    private function aes_encrypt($data, $key, $iv)
    {
        // 确保密钥和IV为16字节长度
        $key = hex2bin($key);
        $iv = hex2bin($iv);

        // 使用 AES-128-CBC 加密，并使用 PKCS7 填充
        $encrypted = openssl_encrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);

        // 将加密后的数据转换为十六进制字符串
        return bin2hex($encrypted);
    }

    // 随机生成64比特（8字节）的IV（CryptoJS中的IV长度为16字节）
    private function getRandomIV()
    {
        $characters = '0123456789ABCDEF';
        $randomString = '';
        for ($i = 0; $i < 16; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;

    }

    // 加密数据函数
    private function encryptData($data, $key)
    {
        // 生成16字节的随机IV
        if($data['iv']){
            $iv=$data['iv'];
        }else{
            $iv = $this->getRandomIV();
        }


        // 加密用户名和密码
        $encryptedUsername = $this->aes_encrypt($data['username'], $key, $iv);
        $encryptedPassword = $this->aes_encrypt($data['password'], $key, $iv);

        // 返回加密后的数据
        return [
            'username' => $encryptedUsername,
            'iv' => $iv,  // 保留 IV 供解密时使用
            'password' => $encryptedPassword
        ];
    }





}


