<?php
// 在线用户类

class User{
    
    private static $users = [];

    /**
     * 向指定客户端发送信息
     * @param int $rid 接收用户ID 为0的话则向所有客户端推送消息
     * @param int $sid 发送用户ID
     * @param string $data 发送数据
     */
    public function send($rid, $sid, $data)
    {
        $send_message = json_encode(['e'=>'message', 'a'=>['uid'=>$sid, 'data'=>$data]]);
        if ($rid !== 0) @fwrite(self::$users[$rid]['socket'], $send_message);
        else foreach (self::$users as $key => $value) {
            @fwrite(self::$users[$key]['socket'], $send_message);
        }
    }

    /**
     * 用户处理事件
     */
    public function event($eid, $data)
    {
        $send_message = json_encode($data);
        if ($eid !== 0) @fwrite(self::$users[$eid]['socket'], $send_message);
        else foreach (self::$users as $key => $value) {
            @fwrite(self::$users[$key]['socket'], $send_message);
        }
    }

    /**
     * 获取用户列表
     */
    public function get_list()
    {
        $user_list = [];
        foreach (self::users as $key => $value) {
            // $uid, $name, $img
            $user_list[] = [$key, $value['name'], $value['img']];
        }
        return $user_list;
    }

    /**
     * 注册用户
     */
    public function login($uid, $name, $img, $socket)
    {
        self::$users[$uid] = ['name'=>$name, 'img'=>$img, 'socket'=>$socket];
    }

    /**
     * 删除用户
     */
    public function del($uid)
    {
        unset(self::$users[$uid]);
        $u->event(0, ['e'=>'offline', 'a'=>['uid'=>$uid]]);
    }

    /**
     * 是否存在用户
     */
    public function has($uid)
    {
        return isset(self::$users[$uid]);
    }

}