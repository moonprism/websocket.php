# Nya

使用Libevent扩展做事件监听。

考虑到效率的话还可以用pcntl_fork开启多进程Redis队列共享消息。

## 依赖

* PHP5.6
* libevent扩展

## 运行

`php ws.php`

## 数据格式

#### 客户端发送

```
{
    c:'controller',
    a:[arg1, arg2 ...]
}
```

指向app目录下的Route.php

#### 服务器推送

```
{
    e:'event',
    a:[arg1, arg2 ...]
}
```

前台处理请参照public中的demo

> 目前还没有前端的v0.1

