# websocket.php

> 使用Libevent重写了一遍

考虑到效率的话还可以用pcntl_fork开启多进程Redis队列共享消息。。。

## 依赖

* PHP5.6
* libevent扩展

## 数据格式

### 客户端
```json
{
    c:'controller',
    a:[arg1, arg2 ...]
}
```
### 服务器
```json
{
    e:'event',
    a:[arg1, arg2 ...]
}
```
没环境，这是未测试的开发版。。。


