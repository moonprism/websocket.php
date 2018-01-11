<?php

class Libevent
{
    private static $instance;

    public $base = null;
    public $events = [];

    private function __construct(){}
 
    /**
     * 单例
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->base = event_base_new();
        }
        return self::$instance;
    }
 
    /**
     * 添加事件轮询
     * @param socket $fd 需要监听的socket操作
     * @param string|[class, string] $fun 回调函数名
     */
    public function add($fd, $fun)
    {
        // 创建一个新的事件
        $event = event_new();
        event_set($event, $fd, EV_READ | EV_PERSIST, $fun);
        event_base_set($event, $this->base);
        event_add($event);
        $this->events[intval($fd)] = $event;
    }
 
    /**
     * 启动事件轮询
     */
    public function loop()
    {
        event_base_loop($this->base);
    }
 
    /**
     * 移除事件轮询
     * @param int $flag 事件标志intval($fd)
     */
    public function del($flag)
    {
        event_del($this->events[$flag]);
        unset($this->events[$flag]);
    }
}