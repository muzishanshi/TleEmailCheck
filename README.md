### TleEmailCheckForWordpress邮箱验证插件
---

TleEmailCheck插件使用更加通用的发送邮件方式，实现带密码、邮箱验证码注册用户的功能，因修改密码、修改邮箱时，WordPress系统会自动发邮件进行验证，所以没必要在修改个人信息时增加邮箱验证，此插件解决了邮件不能发送成功的问题。

<img src="http://me.tongleer.com/content/uploadfile/201706/008b1497454448.png">

#### 使用方法：

	第一步：下载本WordPress插件，放在 `wp-content/plugins/` 目录中（插件文件夹名必须为TleEmailCheck）；
	第二步：激活插件；
	第三步：填写微博小号等等配置；
	第四步：完成。

#### 使用注意：

	版本推荐php5.6+mysql

#### 与我联系：

	作者：二呆
	网站：http://www.tongleer.com/
	Github：https://github.com/muzishanshi/TleEmailCheck

#### 更新记录：

2019-08-26 V1.0.3

	1、修复发送验证码按钮的禁用状态；
	2、修复发送验证码后更改邮箱依然可以注册的bug；
	3、优化更加通用的发送邮件方式等。

2019-01-18 V1.0.2

	修改发邮件的函数名和请求参数，以防止和其他插件冲突。

2018-08-09 第一版本实现