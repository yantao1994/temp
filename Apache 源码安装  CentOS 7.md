# CentOS 7 源码安装 apache 及调试

作者：闫涛

E-mail：coderyantao@qq.com

备注：实验环境为selinux关闭、firewalld已关闭。生产环境不得关闭firewall，而是开启80端口，结尾将介绍如何开启80端口。



# 准备

将源码包上传到root家目录

```shell
[root@localhost ~]# ll
总用量 13148
-rw-r--r--. 1 root root 1073556 3月  14 17:03 apr-1.6.5.tar.gz
-rw-r--r--. 1 root root  554301 3月  14 17:03 apr-util-1.6.1.tar.gz
-rw-r--r--. 1 root root 9267917 3月  14 17:03 httpd-2.4.41.tar.gz
-rw-r--r--. 1 root root 2085854 3月  14 17:03 pcre-8.43.tar.gz
-rw-r--r--. 1 root root  467960 3月  14 17:03 zlib-1.2.11.tar.xz
```

安装 gcc 、gcc-c++、 make等编译工具

```shell
[root@localhost ~]# yum install gcc gcc-c++ make
```



# 一、安装

## 1. 安装 pcre

```shell
[root@localhost ~]# tar -xf pcre-8.43.tar.gz 
[root@localhost ~]# cd pcre-8.43/
[root@localhost pcre-8.43]# ./configure 
[root@localhost pcre-8.43]# make
[root@localhost pcre-8.43]# make install
```

## 2. 安装 zlib

```shell
[root@localhost ~]# tar -xf zlib-1.2.11.tar.xz 
[root@localhost ~]# cd zlib-1.2.11/
[root@localhost zlib-1.2.11]# ./configure 
[root@localhost zlib-1.2.11]# make
[root@localhost zlib-1.2.11]# make install
```

## 3. 安装 openssl-devel

```shell
[root@localhost ~]# yum install openssl-devel
```

## 4. 安装 expat

```shell
[root@localhost ~]# yum install expat-devel
```

## 5. 安装Apache

### 5.1. 解压 httpd、apr、apr-util 三个包

```shell
[root@localhost ~]# tar -xf httpd-2.4.41.tar.gz 
[root@localhost ~]# tar -xf apr-1.6.5.tar.gz 
[root@localhost ~]# tar -xf apr-util-1.6.1.tar.gz 
[root@localhost ~]# ls
apr-1.6.5         apr-util-1.6.1         httpd-2.4.41         pcre-8.43         zlib-1.2.11
apr-1.6.5.tar.gz  apr-util-1.6.1.tar.gz  httpd-2.4.41.tar.gz  pcre-8.43.tar.gz  zlib-1.2.11.tar.xz
```

### 5.2. 分别将 apr 和apr-util 目录拷贝到 httpd下的srclib目录

```shell
[root@localhost ~]# cp -a /root/apr-1.6.5 /root/httpd-2.4.41/srclib/apr
[root@localhost ~]# cp -a /root/apr-util-1.6.1 /root/httpd-2.4.41/srclib/apr-util
```

### 5.3. 安装 Apache

```shell
[root@localhost ~]# cd httpd-2.4.41/
[root@localhost httpd-2.4.41]# ./configure --prefix=/usr/local/apache2 --sysconfdir=/usr/local/apache2/etc --with-included-apr --enable-so --enable-deflate=shared --enable-expires=shared --enable-rewrite=shared --enable-ssl
[root@localhost httpd-2.4.41]# make
[root@localhost httpd-2.4.41]# make install
```

## 6. 启动Apache服务

```shell
[root@localhost httpd-2.4.41]# /usr/local/apache2/bin/apachectl start
AH00558: httpd: Could not reliably determine the server's fully qualified domain name, using localhost.localdomain. Set the 'ServerName' directive globally to suppress this message
```

提示去设置 ServerName

```shell
[root@localhost httpd-2.4.41]# cd /usr/local/apache2/etc/
[root@localhost etc]# ls
extra  httpd.conf  magic  mime.types  original
```

备份并修改配置文件 httpd.conf

```shell
[root@localhost etc]# cp httpd.conf httpd.conf.bak
[root@localhost etc]# vim httpd.conf
```

将下面的内容

```
#ServerName www.example.com:80
修改为
ServerName localhost:80
```

另外，在centos7上，Apache默认监听的是Ipv6的端口，不是Ipv4端口，所以需要修改52行

```
Listen 80
修改为
Listen 0.0.0.0:80
```

重启Apache，这次不报错了

```shell
[root@localhost etc]# /usr/local/apache2/bin/apachectl restart
```

使用进程命令查看httpd进程是否启动

```shell
[root@localhost etc]# ps aux |grep httpd
root      54749  0.0  0.0 112728   972 pts/0    S+   21:56   0:00 grep --color=auto httpd
```

在浏览器上输入ip就可看到 “It works!”字样表示安装完成。

## 7. 配置文件介绍

进入到配置文件目录

```shell
[root@localhost ~]# cd /usr/local/apache2/etc/
[root@localhost etc]# ls
extra  httpd.conf  httpd.conf.bak  magic  mime.types  original
```

其中httpd.conf是主配置文件，extra是子配置文件的目录。只有在主配置文件里面开启了相应的子配置文件，子配置文件才会生效。

## 8. 主配置文件

可参考 https://www.jianshu.com/p/ada351d0a02b

```shell
#apache的主目录
ServerRoot "/usr/local/apache2"

#监听端口
Listen 0.0.0.0:80

#加载相应的模块
LoadModule ***

#用户和用户组
User daemon
Group daemon

#管理员邮箱
ServerAdmin you@example.com

#服务器名
ServerName localhost:80

#服务器错误日志
ErrorLog "logs/error_log"

#访问日志
CustomLog "logs/access_log" common

#默认网页文件名，优先级顺序
<IfModule dir_module>
    DirectoryIndex index.html
</IfModule>

#加载子配置文件，当被注释的时候不生效
#Include etc/extra/httpd-vhosts.conf
```

主目录及权限

```shell
#网页文件存放目录
DocumentRoot "/usr/local/apache2/htdocs"

#对/usr/local/apache2/htdocs这个目录进行配置
<Directory "/usr/local/apache2/htdocs">

#		选项		第一列		第二列
    Options Indexes FollowSymLinks
#第一列可选的内容有：
#	None	没有任何额外权限：当该目录下没有默认网页文件时，显示Forbidden...
#	All		所有权限：当该目录下没有默认网页文件时，显示目录结构 
#	Indexes	浏览权限：当该目录下没有默认网页文件时，显示目录结构

#第二列可选内容有：
#	FollowSymLinks	允许链接到其他目录
# MultiViews			允许文件名匹配

#是否允许目录下.htaccess文件中的权限生效
    AllowOverride None
#可选内容有：
#None		.htaccess中的权限不生效
#All		.htaccess文件中所有权限都生效
#AuthConfig	文件中只有网页认证的权限生效
    
#访问控制列表    
    Require all granted
</Directory>
```



# 二、实验

## 1. Apache目录别名

当apache接受请求时，在默认情况下会将DocumentRoot目录中的文件传送到客户端，如果想将某一不在DocumentRoot 目录下的文件共享到网站上，并希望将他们保留在原来的目录的话，这时可以通过建立别名的方式将URL指向特定的目录。

### 1.1 编辑主配置文件

```shell
[root@localhost ~]# vim /usr/local/apache2/etc/httpd.conf
```

将下面的内容

```shell
#Include etc/extra/httpd-autoindex.conf
修改为
Include etc/extra/httpd-autoindex.conf
```

### 1.2 编辑子配置文件

```shell
[root@localhost ~]# vim /usr/local/apache2/etc/extra/httpd-autoindex.conf 
```

我们能看到这样的一段写好的模板

```shell
#声明别名 别名名称 别名实际目录
Alias /icons/ "/usr/local/apache2/icons/"
#别名的相关权限设置
<Directory "/usr/local/apache2/icons">
    Options Indexes MultiViews
    AllowOverride None
    Require all granted
</Directory>
```

我们按照这个模板，在下面再写一份,创建一个别名为 /a/ 的别名

```shell
Alias /a/ "/a/b/c/"

<Directory "/a/b/c">
    Options Indexes MultiViews
    AllowOverride None
    Require all granted
</Directory>
```

修改之后验证配置文件是否有语法错误

```shell
[root@localhost extra]# /usr/local/apache2/bin/apachectl -t
Syntax OK
```

创建目录 /a/b/c/a.html

```shell
[root@localhost extra]# mkdir -p /a/b/c
[root@localhost extra]# vim /a/b/c/a.html
#随便写一些文字，如：www.abc.com
```

重启apache

```shell
[root@localhost extra]# /usr/local/apache2/bin/apachectl restart
```

浏览器访问：http://192.168.1.47/a/ 即可访问这个别名目录



## 2. Apache的用户认证

对某个网站目录从Apache的层面进行验证

### 1. 创建需要认证的目录和文件

```shell
[root@localhost ~]# mkdir /usr/local/apache2/htdocs/admin
[root@localhost ~]# cd /usr/local/apache2/htdocs/admin/
[root@localhost admin]# vim index.html
#写入 www.admin.com
```

此时可以使用 http://192.168.1.47/admin/ 直接访问的

### 2. 编辑主配置文件

```shell
[root@localhost ~]# vim /usr/local/apache2/etc/httpd.conf
```

在文件的最后面添加如下代码

```shell
<Directory "/usr/local/apache2/htdocs/admin">
    Options Indexes FollowSymLinks
    AllowOverride All		#All为开启权限认证文件 .htaccess
    Require all granted
</Directory>
```

### 3. 在要保护的目录创建 .htaccess文件

```shell
[root@localhost ~]# cd /usr/local/apache2/htdocs/admin/
[root@localhost admin]# vim .htaccess
```

输入如下内容

```shell
#提示信息
Authname "欢迎来到管理后台！"
#加密类型
AuthType basic
#密码文件，文件名自定义。（使用绝对路径）
AuthUserFile /usr/local/apache2/htdoces/admin/apache.passwd
#允许密码文件中所有用户访问
require valid-user
```

### 4. 创建密码文件

```shell
#使用httpd的命令 htpasswd
[root@localhost ~]# /usr/local/apache2/bin/htpasswd -c   	/usr/local/apache2/htdocs/admin/apache.passwd user1
New password: 
Re-type new password: 
Adding password for user user1
```

第二次创建使用 -m

```shell
[root@localhost ~]# /usr/local/apache2/bin/htpasswd -m /usr/local/apache2/htdocs/admin/apache.passwd user2
New password: 
Re-type new password: 
Adding password for user user2
```

现在使用apache的二进制文件都需要绝对路径，太麻烦了。所以我们对apache的可执行文件做一下软链接

```shell
[root@localhost ~]# ln -s /usr/local/apache2/bin/* /usr/local/bin/
```

### 5. 检查语法错误

```shell
[root@localhost ~]# apachectl -t
Syntax OK
```

### 6. 重启Apache

```shell
[root@localhost ~]# apachectl restart
```

### 7. 浏览器访问

```
http://192.168.1.47/admin/
```

这是就需要输入我们创建的账号密码了，然后才能继续浏览其他文件



## 3. 虚拟主机

基于域名的虚拟主机：为一台服务器，一个ip，搭建多个网站，每个网站使用不同域名访问

实验准备：

a: 域名解析：准备2个域名

www.test1.com

www.test2.com

b: 网站主页目录规划：

在 htdocs 下分别创建 test1 和 test2 两个目录，并在新建目录内创建index.html文件，并分别写入不同的内容。

```shell
[root@localhost ~]# mkdir /usr/local/apache2/htdocs/test1
[root@localhost ~]# vim /usr/local/apache2/htdocs/test1/index.html

[root@localhost ~]# mkdir /usr/local/apache2/htdocs/test2
[root@localhost ~]# vim /usr/local/apache2/htdocs/test2/index.html
```

### 1. 修改主配置文件

```shell
[root@localhost ~]# vim /usr/local/apache2/etc/httpd.conf
#Include etc/extra/httpd-vhosts.conf
修改为
Include etc/extra/httpd-vhosts.conf
```

### 2. 编辑子配置文件

这里有2个已经写好的模板，先复制粘贴一份，然后一定要将模板注释掉，否则报错。

```shell
[root@localhost ~]# vim /usr/local/apache2/etc/extra/httpd-vhosts.conf
#对/usr/local/apache2/htdocs/test1这个目录进行配置
<Directory "/usr/local/apache2/htdocs/test1">
    Options Indexes FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>

<VirtualHost *:80>
    ServerAdmin webmaster@dummy-host.example.com
    DocumentRoot "/usr/local/apache2/docs/dummy-host.example.com"
    ServerName dummy-host.example.com
    ServerAlias www.dummy-host.example.com
    ErrorLog "logs/dummy-host.example.com-error_log"
    CustomLog "logs/dummy-host.example.com-access_log" common
</VirtualHost>

<VirtualHost *:80>
    ServerAdmin webmaster@dummy-host2.example.com
    DocumentRoot "/usr/local/apache2/docs/dummy-host2.example.com"
    ServerName dummy-host2.example.com
    ErrorLog "logs/dummy-host2.example.com-error_log"
    CustomLog "logs/dummy-host2.example.com-access_log" common
</VirtualHost>
```

将粘贴的模板修改如下

```shell
<VirtualHost *:80>
    ServerAdmin webmaster@test1.com
    DocumentRoot "/usr/local/apache2/htdocs/test1"
    ServerName www.test1.com
    ErrorLog "logs/test1-error_log"
    CustomLog "logs/test1-access_log" common
</VirtualHost>

<VirtualHost *:80>
    ServerAdmin webmaster@test2.com
    DocumentRoot "/usr/local/apache2/htdocs/test2"
    ServerName www.test2.com
    ErrorLog "logs/test2-error_log"
    CustomLog "logs/test2-access_log" common
</VirtualHost>
```

### 3. 检查语法，重启apache

```shell
[root@localhost ~]# apachectl -t
Syntax OK
[root@localhost ~]# apachectl restart
```

### 4. 修改自己电脑的 hosts 文件

```shell
#添加如下内容
192.168.1.47    www.test1.com
192.168.1.47    www.test2.com
```

然后用浏览器分别访问 http://www.test1.com 和 http://www.test2.com 就可得到不同内容。



## 4. 域名跳转

访问www.test2.com 会自动跳转到 www.test1.com 

### 1. 修改apache主配置文件

```shell
[root@localhost ~]# vim /usr/local/apache2/etc/httpd.conf
```

```shell
#LoadModule rewrite_module modules/mod_rewrite.so
修改为
LoadModule rewrite_module modules/mod_rewrite.so
```

### 2. 修改虚拟主机的配置文件

开启test2的 .htaccess权限文件

```shell
[root@localhost ~]# vim /usr/local/apache2/etc/extra/httpd-vhosts.conf
```

```shell
#对/usr/local/apache2/htdocs/test2这个目录进行配置
<Directory "/usr/local/apache2/htdocs/test2">
    Options Indexes FollowSymLinks
    AllowOverride All 
    Require all granted
</Directory>
```

### 3. 在test2目录下创建.htaccess文件

```shell
[root@localhost ~]# vim /usr/local/apache2/htdocs/test2/.htaccess
```



```shell
#开启rewrite功能
RewriteEngine on

#把www.test2.com 开头的内容赋值给HTTP_HOST变量
RewriteCond %{HTTP_HOST} ^www.test2.com

RewriteRule ^(.*) http://www.test1.com/$1 [R=permanent,L]
# ^(.*)$ 代指客户端要访问的资源
# $1 把 .* 所指代的内容赋值给 $1 变量中
# Rpermanent 永久重定向 = 301
# L 指定该规则为最后一条生效的规则，下面的不再生效
```

### 4. 测试

浏览器访问 http://www.test2.com/ 会跳转到 http://www.test1.com/



## 5. Apache+openssl 实现 https

### 1. 准备工作

#### 1.1. 开启Apache 的 ssl

```shell
[root@localhost ~]# vim /usr/local/apache2/etc/httpd.conf
```

```shell
#LoadModule ssl_module modules/mod_ssl.so
修改为
LoadModule ssl_module modules/mod_ssl.so
```

#### 1.2. 检查ssl模块是否存在

```shell
[root@localhost ~]# ls /usr/local/apache2/modules/ |grep ssl
mod_ssl.so
```

#### 1.3. 检查模块是否启用

```shell
[root@localhost ~]# apachectl -M |grep ssl
 ssl_module (shared)
```

### 2. CA证书申请

生产环境的证书需要去专门的厂商购买，然后放到服务器上使用，最后浏览器会校验证书。

实验环境为自己创建即可。

#### 2.1. 创建 cert目录用来存放证书

```shell
[root@localhost ~]# mkdir /usr/local/apache2/cert
[root@localhost ~]# cd /usr/local/apache2/cert/
```

#### 2.2. 生成sra密钥

名字自己定义

```shell
[root@localhost cert]# openssl genrsa -out ca.key 1024
Generating RSA private key, 1024 bit long modulus
..++++++
..................................++++++
e is 65537 (0x10001)
[root@localhost cert]# ls
ca.key
```

#### 2.3. 证书文件的创建

名字自己定义

```shell
[root@localhost cert]# openssl req -new -key ca.key -out mycompany.csr
You are about to be asked to enter information that will be incorporated
into your certificate request.
What you are about to enter is what is called a Distinguished Name or a DN.
There are quite a few fields but you can leave some blank
For some fields there will be a default value,
If you enter '.', the field will be left blank.
-----
#国家
Country Name (2 letter code) [XX]:CN
#省份
State or Province Name (full name) []:LN
#城市
Locality Name (eg, city) [Default City]:SY
#组织
Organization Name (eg, company) [Default Company Ltd]:mycompany
#单位
Organizational Unit Name (eg, section) []:yw
#域名
Common Name (eg, your name or your server's hostname) []:www.test1.com
#邮箱
Email Address []:coderyantao@qq.com

Please enter the following 'extra' attributes
to be sent with your certificate request
A challenge password []:#本次实验未加密，直接回车
An optional company name []:#本次实验未加密，直接回车
[root@localhost cert]# ls
#密钥		证书
ca.key  mycompany.csr
```

#### 2.4. 利用密钥和证书生成签字证书

名字自己定义

```shell
[root@localhost cert]# cd /usr/local/apache2/etc/extra/
```

```shell
[root@localhost cert]# openssl x509 -req -days 365 -sha256 -in mycompany.csr -signkey ca.key -out mycompany.crt
Signature ok
subject=/C=CN/ST=LN/L=SY/O=mycompany/OU=yw/CN=www.test1.com/emailAddress=coderyantao@qq.com
Getting Private key
[root@localhost cert]# ls
ca.key  mycompany.crt  mycompany.csr
```

### 3. 修改配置文件

#### 3.1. 主配置文件

```shell
[root@localhost cert]# vim /usr/local/apache2/etc/httpd.conf
```

```shell
#Include etc/extra/httpd-ssl.conf
修改为
Include etc/extra/httpd-ssl.conf
```

#### 3.2. 子配置文件

可参考 https://www.jianshu.com/p/32d0eca02775

```shell
[root@localhost cert]# cd /usr/local/apache2/etc/extra/
[root@localhost extra]# cp httpd-ssl.conf httpd-ssl.conf.bak 
[root@localhost extra]# vim httpd-ssl.conf
```

找到下面5行注释掉

```
SSLCipherSuite HIGH:MEDIUM:!MD5:!RC4:!3DES
SSLProtocol all -SSLv3
SSLHonorCipherOrder on
SSLCertificateFile "/usr/local/apache2/etc/server.crt"
SSLCertificateKeyFile "/usr/local/apache2/etc/server.key"
```

然后找个空地，写入我们自己的内容

```shell
# 添加 SSL 协议支持协议，去掉不安全的协议
SSLProtocol all -SSLv2 -SSLv3
# 修改加密套件如下
SSLCipherSuite HIGH:!RC4:!MD5:!aNULL:!eNULL:!NULL:!DH:!EDH:!EXP:+MEDIUM
SSLHonorCipherOrder on
# 证书公钥配置
SSLCertificateFile cert/mycompany.crt
# 证书私钥配置
SSLCertificateKeyFile cert/ca.key
```

#### 3.3. 修改主配置文件，添加虚拟主机

```shell
[root@localhost extra]# vim /usr/local/apache2/etc/httpd.conf
```

添加如下内容

```shell
<VirtualHost _default_:443>
DocumentRoot "/usr/local/apache2/htdocs"
ServerName localhost:443
SSLCertificateFile cert/mycompany.crt
SSLCertificateKeyFile cert/ca.key
SSLCertificateChainFile cert/mycompany.crt
</VirtualHost>
```

语法检查，发现报错，原因是没有开启mod_socache_shmcb

```shell
[root@localhost extra]# apachectl -t
AH00526: Syntax error on line 92 of /usr/local/apache2/etc/extra/httpd-ssl.conf:
SSLSessionCache: 'shmcb' session cache not supported (known names: ). Maybe you need to load the appropriate socache module (mod_socache_shmcb?).
```

开启mod_socache_shmcb

```shell
[root@localhost extra]# vim /usr/local/apache2/etc/httpd.conf
```

```shell
#LoadModule socache_shmcb_module modules/mod_socache_shmcb.so
修改为
LoadModule socache_shmcb_module modules/mod_socache_shmcb.so
```

再次检查，重启

```shell
[root@localhost extra]# apachectl -t
Syntax OK
[root@localhost extra]# apachectl restart
```

检查端口，80和443端口都开启.并且也要确保firewall已提前开启了443

```shell
[root@localhost extra]# netstat -antp
Active Internet connections (servers and established)
Proto Recv-Q Send-Q Local Address           Foreign Address         State       PID/Program name    
tcp        0      0 0.0.0.0:111             0.0.0.0:*               LISTEN      1/systemd           
tcp        0      0 0.0.0.0:80              0.0.0.0:*               LISTEN      54773/httpd         
tcp        0      0 0.0.0.0:22              0.0.0.0:*               LISTEN      6585/sshd           
tcp        0      0 127.0.0.1:25            0.0.0.0:*               LISTEN      6853/master         
tcp        0    208 192.168.1.47:22         192.168.1.42:50525      ESTABLISHED 92152/sshd: root@pt 
tcp        0      0 192.168.1.47:22         192.168.1.42:52562      ESTABLISHED 92344/sshd: root@pt 
tcp6       0      0 :::111                  :::*                    LISTEN      1/systemd           
tcp6       0      0 :::22                   :::*                    LISTEN      6585/sshd           
tcp6       0      0 ::1:25                  :::*                    LISTEN      6853/master         
tcp6       0      0 :::443                  :::*                    LISTEN      54773/httpd 
```

#### 3.4. 测试

http://192.168.1.47/ 可以正常访问

https://192.168.1.47/ 由于证书是我们自己生成的，所以显示“您的连接不是私密连接”

目前谷歌浏览器是不能继续访问下去了。

但是火狐可以继续访问，可以点击高级，查看证书、继续访问

#### 3.5. 强制跳转https

```shell
[root@localhost extra]# vim /usr/local/apache2/etc/httpd.conf
```

```shell
<Directory "/usr/local/apache2/htdocs">
    Options Indexes FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>

修改如下:

<Directory "/usr/local/apache2/htdocs">
    Options Indexes FollowSymLinks
    AllowOverride None
    Require all granted
    RewriteEngine on #开启转发规则
    RewriteCond %{SERVER_PORT} !^443$ #检查访问端口只要不是443的
    RewriteRule ^(.*)?$ https://%{SERVER_NAME}/$1 [R=301,L] #全部使用https重新访问
</Directory>
```

语法检测，重启

```shell
[root@localhost extra]# apachectl -t
Syntax OK
[root@localhost extra]# apachectl restart
```

这是当我们访问 http://192.168.1.47 时，会自动跳转到 https://192.168.1.47

为了后面的实验方便进行，实验完成时候恢复一下

## 6. Apache 配置静态缓存

设置静态文件在客户端的浏览器的缓存时间

#### 6.1. 修改主配置文件

```shell
[root@localhost ~]# vim /usr/local/apache2/etc/httpd.conf
```

```shell
#LoadModule expires_module modules/mod_expires.so
修改为
LoadModule expires_module modules/mod_expires.so

并添加如下内容，找个空地
<IfModule mod_expires.c>
        ExpiresActive on	
        ExpiresByType image/jpeg "access plus 24 hours"
        ExpiresByType image/png "access plus 24 hours"
        ExpiresByType text/css  "now plus 2 hours"
        ExpiresByType application/javascript "now plus 2 hours"
</IfModule>
```

#### 6.2验证

```shell
[root@localhost test1]# curl -x192.168.1.47:80 'http://www.test1.com/timg.jpeg' -I
HTTP/1.1 200 OK
Date: Tue, 17 Mar 2020 06:48:03 GMT
Server: Apache/2.4.41 (Unix)
Last-Modified: Tue, 17 Mar 2020 05:56:15 GMT
ETag: "513e-5a10697fbdc34"
Accept-Ranges: bytes
Content-Length: 20798
Cache-Control: max-age=86400
Expires: Wed, 18 Mar 2020 06:48:03 GMT
Content-Type: image/jpeg
```







