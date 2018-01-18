<?php

namespace app;

class Route
{
    private $users = null;

    private $uid = 0;

    private $socket = null;

    public function __construct()
    {
        $this->users = User::getInstance();
    }

    // 前置函数
    public function __before($uid, $socket)
    {
        $this->uid = $uid;
        $this->socket = $socket;
    }

    // 后置函数
    public function __after($uid, $socket)
    {

    }

    // 关闭连接函数
    public function __close($uid)
    {
        $inc = User::getInstance();
        if ($inc->has($uid)) $inc->del($uid);
    }
    /**
     * 用户注册
     */
    public function login($name, $img = 'def')
    {
        // 通知其他用户新增用户
        $this->users->event(0, ['e'=>'online', 'a'=>['uid'=>$this->uid, 'name'=>$name, 'img'=>$img]]);
        // 登录成功
        $this->users->login($this->uid, $name, $img, $this->socket);
        // 获取所有用户列表
        $this->users->event($this->uid, ['e'=>'login', 'a'=>['uid'=>$this->uid, 'uli'=>$this->users->get_list()]]);
    }

    /**
     * 向其他用户发送消息
     */
    public function send($rid, $message)
    {
        $this->users->send($rid, $this->uid, $message);
    }

}