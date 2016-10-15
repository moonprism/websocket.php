# websocket.php
一个简单的websocket聊天程序实现

## 使用
将ws.php最后一行`wstart('45.78.1.24', 2333);`修改为你的服务器地址与端口号
将该文件上传到服务器使用`php ws.php`执行该socket程序
修改demo.html中的`var ws = new WebSocket("ws://45.78.1.24:2333");`地址与端口号

## 注意
websocket的解析网上很多教程讲的很清楚了，但有一个地方

浏览器关闭后会向websocket服务器发送一串乱码，无法解析出来是什么，但通过直接对比正常信息，发现第一位固定码正常信息是1000 0001（'\x81'）但浏览器关闭时那串乱码却是（'\x88'）开头，在字符解码函数里判断这一点就好啦


