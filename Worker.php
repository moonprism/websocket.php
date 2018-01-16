<?php

require 'Libevent.php';
require 'User.php';
require 'Route.php';

class Re{
    public $users = null;
    public $uid = 0;
    public $socket = null;
    public function __construct($users, $uid, $socket){
        $this->users = $users;
        $this->uid = $uid;
        $this->socket = $socket;
    }
}

class Worker
{
    public $socket = null;
    public $eve = null;
    public $users = null;
    public $links = [];

    /**
     * 开启监听端口
     * @param int $port 端口号
     */
    public function __construct($port)
    {
        $this->socket = stream_socket_server('tcp://0.0.0.0:'.$port, $errno, $errstr);
        // 设置为非阻塞模式
        stream_set_blocking($this->socket, 0);
        // 获取Libevent单例
        $this->eve = Libevent::getInstance();
        // 用户类
        $this->users = new User();
        // 监听事件
        $this->eve->add($this->socket, [$this, 'accept']);
        // 开启事件轮询
        $this->eve->loop();
    }

    /**
     * 用户连接回调
     */
    public function accept($socket, $flag)
    {
        // 接受连接
        $conn = @stream_socket_accept($socket, 0);
        // 设置为非阻塞
        stream_set_blocking($conn, 0);
        // 监听该连接消息事件
        $this->eve->add($conn, [$this, 'recv']);
    }

    /**
     * 用户发送消息回调
     */
    public function recv($conn, $flag)
    {
        // 用户、事件flag
        $uid = intval($conn);
        $buffer = @fread($conn, 1024);
        if (($buffer === '' || $buffer === false) && (feof($conn) || !is_resource($conn) || $buffer === false)) {
            // 接收完成,清除轮询事件,关闭 socket 连接
            $this->eve->del($uid);
            $this->users->del($uid);
            unset($this->links[$uid]);
            @fclose($conn);
            return;
        }
        if (isset($this->links[$uid])) {
            // 已经握手则发送消息
            //
            // -- 消息格式定义为JSON {c:$controller, a:[$arg]}
            // -- 转到Route处理
            // -- 这里还需要对发送来的路由信息通过反射过滤
            //
            $data = @json_decode($this->decode($buffer), true);
            if (isset($data['c'])) {
                // 处理控制器
                $rf = new ReflectionClass('Route');
                $actionRf = $rf->getMethod($data['c']);
                $params = $actionRf->getParameters();
                $i = 0;
                $pas = [];
                // 注入依赖User uid
                foreach ($params as $pa) {
                    if ($class = $pa->getClass()) {
                        switch ($class->getName()) {
                            case 'Re':
                                $pas[] = new Re($this->users, $uid, $conn);
                                break;
                        }
                    } else {
                        if (isset($data['a'][$i])) {
                            $pas[] = $data['a'][$i];
                        }
                        $i++;
                    }
                }
                $actionRf->invokeArgs($rf->newInstance(), $pas);
            }
        } else {
            // 握手
            if ($handS = $this->handShakeData($buffer)) {
                fwrite($conn, $this->handShakeData($buffer));
                //
                // -- 接收一个连接
                //
                $this->links[$uid] = $conn;
            }
        }
    }

    /**
     * WebSocket握手处理
     */
    public function handShakeData($buffer) {
        preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $keys);
        if (!isset($keys[1])) return false;
        $key = base64_encode(sha1($keys[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        return "HTTP/1.1 101 Switching Protocol\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: ".$key."\r\n\r\n";
    }

    /**
     * 将接收消息解码
     */
    public function decode($buffer) {
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

}

new Worker(2333);