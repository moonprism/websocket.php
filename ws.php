<?php

/**
 **  一个简单的websocket聊天程序，
 **   >screen -S 'WS' php ws.php
 */

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

// 接收的socket数组
$accept_array = array();
// 保存用户信息的数组
$user_array = array();

/**
 * 建立websocket握手函数
 * @param $socket 需要建立连接的socket
 * @param $buffer 发送的请求信息
 */
function handShake($socket, $buffer) {
    // 从中提取key
    preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $keys);
    // 将其加密为要发送的key
    $key = base64_encode(sha1($keys[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    // 整合为要发送的header
    $upgrade  = "HTTP/1.1 101 Switching Protocol\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: ".$key."\r\n\r\n";
    // 发送成功的话就和客户端建立websocket链接了
    $sent = socket_write($socket, $upgrade, strlen($upgrade));
}

/**
 * 握手后将客户端发送来的消息解密
 * @param string $buffer 客户端发送的消息
 * @return string 解密后的正常字符串 / false
 */
function decode($buffer) {
    $len = $masks = $data = $decoded = null;
    //浏览器关闭将发送该信息
    if(ord($buffer[0])!=129){
        return false;
    }
    $len = ord($buffer[1]) & 127;
    if ($len === 126) {
        $masks = substr($buffer, 4, 4);
        $data = substr($buffer, 8);
    } else if ($len === 127) {
        $masks = substr($buffer, 10, 4);
        $data = substr($buffer, 14);
    } else {
        $masks = substr($buffer, 2, 4);
        $data = substr($buffer, 6);
    }
    for ($index = 0; $index < strlen($data); $index++) {
        $decoded .= $data[$index] ^ $masks[$index % 4];
    }
    return $decoded;
}

/**
 * 握手后将要发送到客户端的消息加密
 * @param string $buffer 要发送的消息
 * @return string 加密后的字符串
 */
function encode($s) {
    $a = str_split($s, 125);
    if (count($a) == 1) {
        return "\x81".chr(strlen($a[0])).$a[0];
    }
    $ns = "";
    foreach ($a as $o) {
        $ns .= "\x81".chr(strlen($o)).$o;
    }
    return $ns;
}

/**
 * 关闭握手函数
 * 从$accept_array和$user_array中删除相应元素
 */
function cc($sock){
    global $accept_array, $user_array;
    foreach ($accept_array as $key => $value) {
        if ($value == $sock) {
            unset($accept_array[$key]);
        }
    }
    unset($user_array[(string)$sock]);
}

/**
 * websocket开启函数
 * @param string $address 地址
 * @param int $port 端口号
 */
function wstart($address, $port) {
    global $accept_array, $user_array;
    // 开启socket
    $master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1);
    socket_bind($master, $address, $port);
    socket_listen($master,20);
    $accept_array[] = $master;
    while(true) {
        $sock_array = $accept_array;
        $write = NULL;
        $except = NULL;
        socket_select($sock_array, $write, $except, NULL);
        foreach ($sock_array as $sock) {
            if ($sock == $master) {
                $client = socket_accept($master);
                if ($client >= 0) {
                    // 添加到需求数组中
                    $accept_array[] = $client;
                    $user_array[(string)$client] = array('isLink'=>false);
                }
                continue;
            } else {
                $bytes = @socket_recv($sock,$buffer,1024,0);
                if ($bytes == 0) { continue; }
                if ($user_array[(string)$sock]['isLink']) {
                    # 已经握手,将信息发送至所有用户
                    foreach($accept_array as $sender) {
                        if ($sender !== $master) {
                            $decodeMsg = decode($buffer);
                            if ($decodeMsg !== false) {
                                $msg = encode($decodeMsg);
                                socket_write($sender, $msg, strlen($msg));  
                            } else {
                                // 关闭该用户链接
                                cc($sock);
                            }
                         }
                    }
                } else {
                    # 建立握手链接
                    handShake($sock, $buffer);
                    $user_array[(string)$sock]['isLink'] = true;
                }
            }
        }
    }   
}
wstart('45.78.1.24', 2333);