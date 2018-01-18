<?php

namespace core;

// util工具类 关于 websocket

class WS{

    /**
     * WebSocket握手处理
     */
    public static function handShakeData($buffer) {
        preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $keys);
        if (!isset($keys[1])) return false;
        $key = base64_encode(sha1($keys[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        return "HTTP/1.1 101 Switching Protocol\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: ".$key."\r\n\r\n";
    }

    /**
     * 将接收消息解码
     */
    public static function decode($buffer) {
        $len = $masks = $data = $decoded = null;
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
     * 编码需要发送的数据
     */
    public static function encode($s) {
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

}