<?php

class Route
{
    /**
     * 用户注册
     */
    public function login(Re $r, $name, $img = 'def')
    {
        // 通知其他用户新增用户
        $r->users->event(0, ['e'=>'online', 'a'=>['uid'=>$r->uid, 'name'=>$name, 'img'=>$img]]);
        // 登录成功
        $r->users->login($r->uid, $name, $img, $r->socket);
        // 获取所有用户列表
        $r->users->event($r->uid, ['e'=>'login', 'a'=>['uid'=>$r->uid, 'u_li'=>$r->users->get_list()]]);
    }

    /**
     * 向其他用户发送消息
     */
    public function send(Re $r, $rid, $message)
    {
        $r->users->send($rid, $r->uid, $message);
    }
}