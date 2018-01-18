<?php

namespace core;

class Worker
{
    public $socket = null;
    public $eve = null;
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
        // 获取反射路由实例
        $this->ref = new \ReflectionClass('app\\Route');
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
            // 用户关闭连接事件
            $this->refc('__close', [$uid], false);
            unset($this->links[$uid]);
            @fclose($conn);
            return;
        }
        if (isset($this->links[$uid])) {
            // 已经握手则发送消息
            $data = @json_decode(WS::decode($buffer), true);
            if (isset($data['c'])) {
                $this->refc($data['c'], $data['a'], [$uid, $conn]);
            }
        } else {
            // 没有握手
            if ($handS = WS::handShakeData($buffer)) {
                fwrite($conn, $handS);
                // -- 则建立握手连接
                $this->links[$uid] = $conn;
            }
        }
    }

    // 反射执行路由
    public function refc($fun, $arg, $call_arg = [])
    {
        // 判断是否有这个函数
        if ($this->ref->hasMethod($fun)) {
            $actionRf = $this->ref->getMethod($fun);
            $params = $actionRf->getParameters();
            $inc = $this->ref->newInstance();
            // 检测参数个数
            if (count($arg) >= $actionRf->getNumberOfRequiredParameters() && count($arg) <= $actionRf->getNumberOfParameters()) {
                if($call_arg) $this->ref->getMethod('__before')->invokeArgs($inc, $call_arg);
                $actionRf->invokeArgs($inc, $arg);
                if($call_arg) $this->ref->getMethod('__after')->invokeArgs($inc, $call_arg);
            }
            
        }
    }

}