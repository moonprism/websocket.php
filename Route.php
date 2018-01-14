<?php

class Route
{
    /**
     * 用户注册
     */
    public function login(User $u, ID $i, Socket $s, $name, $img = 'def')
    {
        // 通知其他用户新增用户
        $u->event(0, ['e'=>'online', 'a'=>['uid'=>$u, 'name'=>$name, 'img'=>$img]]);
        // 获取所有用户列表
        $u->event($i, ['e'=>'login', 'a'=>['u_li'=>$u->get_list()]);
        // 登录成功
        $u->login($i, $name, $img, $s);
    }

    /**
     * 向其他用户发送消息
     */
    public function send(User $u, ID $uid, $rid, $message)
    {
        $u->send($rid, $uid, $message);
    }
}