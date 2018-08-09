<?php
/* 
Plugin Name: TleEmailCheck
Plugin URI: https://github.com/muzishanshi/TleEmailCheck
Description:  TleEmailCheck插件可以实现带密码、邮箱验证码注册用户的功能，因修改密码、修改邮箱时，WordPress系统会自动发邮件进行验证，所以没必要在修改个人信息时增加邮箱验证，此插件解决了邮件不能发送成功的问题。
Version: 1.0.1
Author: 二呆
Author URI: http://www.tongleer.com
License: 
*/
function tle_email_check_head(){
	
}
add_action('login_head','tle_email_check_head');

add_action( 'register_form', 'tle_email_check_form' );
function tle_email_check_form() {
	?>
	<script src="http://libs.baidu.com/jquery/1.11.1/jquery.min.js"></script>
	<p>
		<label for="password">密码<br/>
			<input id="password" class="input" type="password" tabindex="30" size="25" value="" name="password" placeholder="可选" />
		</label>
    </p>
    <p>
		<label for="repeat_password">确认密码<br/>
			<input id="repeat_password" class="input" type="password" tabindex="40" size="25" value="" name="repeat_password" />
		</label>
    </p>
	<p>
		<label>邮箱验证码（<a id="sendsmsmsg" href="javascript:;">发送</a>）<br />
			<input type="text" name="emailcode" id="emailcode" class="input" size="25" tabindex="20" />
			<input type="hidden" name="sitetitle" id="sitetitle" value="<?=bloginfo('name');?>" class="input" size="25" tabindex="20" />
		</label>
	</p>
	<script>
	$("#sendsmsmsg").click(function(e){
		var user_email=$("#user_email").val();
		var myreg = /^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/;
		if (!myreg.test(user_email)) {
			alert('请输入有效的邮箱！'); 
			return; 
		}
		settime();
		$.post("<?=admin_url('options-general.php?page=tle-email-check&t=sendsms');?>",{user_email:user_email,sitetitle:$('#sitetitle').val()},function(data){
		});
	});
	var timer;
	var countdown=60;
	function settime() {
		if (countdown == 0) {
			$("#sendsmsmsg").html("刷新页面重新发送");
			countdown = 60;
			clearTimeout(timer);
			return;
		} else {
			$("#sendsmsmsg").html(countdown+"秒");
			$("#sendsmsmsg").unbind("click");
			countdown--; 
		} 
		timer=setTimeout(function() { 
			settime() 
		},1000) 
	}
	$("#registerform").submit(function(e){
		if($("#user_login").val()==""){
			alert("请输入用户名");
			return false;
		}
		/*
		if($("#password").val()==""){
			alert("请输入密码");
			return false;
		}
		if($("#repeat_password").val()==""){
			alert("请输入确认密码");
			return false;
		}
		*/
		if($("#password").val()!=""&&$("#password").val().length<8){
			alert("密码长度至少8位");
			return false;
		}
		if($("#password").val()!=$("#repeat_password").val()){
			alert("两次输入密码不相同");
			return false;
		}
		var user_email=$("#user_email").val();
		var myreg = /^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/;
		if (!myreg.test(user_email)) {
			alert('请输入有效的邮箱！'); 
			return false; 
		}
		var yzm = $("#emailcode").val().replace(/(^\s*)|(\s*$)/g, "");
		if(yzm==""){
			alert("请输入邮箱验证码");
			return false;
		}
	});
	</script>
	<?php
}
add_action( 'register_post', 'tle_email_check_validate', 10, 3 );
function tle_email_check_validate( $sanitized_user_login, $user_email, $errors) {
	session_start();
	if (!isset($_POST[ 'emailcode' ]) || empty($_POST[ 'emailcode' ])) {
		return $errors->add( 'emailcodeempty', '<strong>错误</strong>: 请输入邮箱验证码。' );
	} elseif (strcasecmp($_POST[ 'emailcode' ],$_SESSION['code'])!=0) {
		return $errors->add( 'emailcodefail', '<strong>错误</strong>: 邮箱验证码不正确。' );
	}else if ( $_POST['password'] !== $_POST['repeat_password'] ) {
        return $errors->add( 'passwords_not_matched', "<strong>错误</strong>: 两次输入密码不相同" );
    }else if ( $_POST[ 'password' ]!=""&&strlen( $_POST['password'] ) < 8 ) {
        return $errors->add( 'password_too_short', "<strong>ERROR</strong>: 密码长度至少8位" );
    }
}

add_action( 'user_register', 'ts_email_check_password', 100 );
function ts_email_check_password( $user_id ){
    $userdata = array();
    $userdata['ID'] = $user_id;
    if ( $_POST['password'] !== '' ) {
        $userdata['user_pass'] = $_POST['password'];
    }
    $new_user_id = wp_update_user( $userdata );
	
	//重置短信验证码
	$randCode = '';
	$chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPRSTUVWXYZ23456789';
	for ( $i = 0; $i < 5; $i++ ){
		$randCode .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	}
	$_SESSION['code'] = strtoupper($randCode);
}

add_filter( 'gettext', 'ts_email_check_password_text' );
function ts_email_check_password_text ( $text ) {
    if ( $text == '注册确认信将会被寄给您。'||$text=='A password will be e-mailed to you.' ) {
        $text = '若密码为空，则生成一个。密码长度至少8位';
    }
    return $text;
}

if(isset($_GET['t'])){
    if($_GET['t'] == 'config'){
        update_option('tle_email_check', array('mailsmtp' => $_REQUEST['mailsmtp'], 'mailport' => $_REQUEST['mailport'], 'mailuser' => $_REQUEST['mailuser'], 'mailpass' => $_REQUEST['mailpass']));
    }
	if($_GET['t'] == 'sendsms'){
        session_start();
		date_default_timezone_set('Asia/Shanghai');
		//重置短信验证码
		$randCode = '';
		$chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPRSTUVWXYZ23456789';
		for ( $i = 0; $i < 5; $i++ ){
			$randCode .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		$_SESSION['code'] = strtoupper($randCode);

		$user_email = isset($_POST['user_email']) ? addslashes(trim($_POST['user_email'])) : '';//发送到的用户名
		$sitetitle = isset($_POST['sitetitle']) ? addslashes(trim($_POST['sitetitle'])) : '';
		sendMail($user_email,$sitetitle.'注册验证码','您的注册验证码是'.$_SESSION['code']);
		return;
    }
}

//插件启用后自动跳转插件设置页面
register_activation_hook(__FILE__, 'tle_email_check_activate');
add_action('admin_init', 'tle_email_check_redirect');
function tle_email_check_activate() {
    add_option('tle_email_check_do_activation_redirect', true);
}
function tle_email_check_redirect() {
    if (get_option('tle_email_check_do_activation_redirect', false)) {
        delete_option('tle_email_check_do_activation_redirect');
        wp_redirect(admin_url( 'options-general.php?page=tle-email-check' ));
    }
}

add_action('admin_menu', 'tle_email_check_menu');
function tle_email_check_menu(){
    add_options_page('邮箱验证', '邮箱验证', 'manage_options', 'tle-email-check', 'tle_email_check_options');
}
function tle_email_check_options(){
    $weibo_configs = get_settings('tle_email_check');
	?>
	<div class="wrap">
		<h2>邮箱验证设置:</h2>
		作者：<a href="http://www.tongleer.com" target="_blank" title="邮箱验证">二呆</a><br />
		<?php
		$version=file_get_contents('http://api.tongleer.com/interface/TleEmailCheck.php?action=update&version=1');
		echo $version;
		?>
		<form method="get" action="">
			<p>
				<input type="text" name="mailsmtp" value="<?=$weibo_configs["mailsmtp"];?>" required placeholder="smtp服务器(已验证QQ企业邮箱和126邮箱可成功发送)" size="50" />
			</p>
			<p>
				<input type="text" name="mailport" value="<?=$weibo_configs["mailport"];?>" required placeholder="smtp服务器端口" size="50" />
			</p>
			<p>
				<input type="text" name="mailuser" value="<?=$weibo_configs["mailuser"];?>" required placeholder="smtp服务器邮箱用户名" size="50" />
			</p>
			<p>
				<input type="text" name="mailpass" value="<?=$weibo_configs["mailpass"];?>" required placeholder="smtp服务器邮箱密码" size="50" />
			</p>
			<p>
				<input type="hidden" name="t" value="config" />
				<input type="hidden" name="page" value="tle-email-check" />
				<input type="submit" value="修改配置" />
			</p>
		</form>
		<h6>备注：为何忘记密码、密码重置时，点击链接提示重置链接会无效？</h6>
		<p>
			wp-login.php文件下面这段代码，大概在369行，我的WordPress版本最新4.9.8版本。<br />
			<DIV class=dp-highlighter><DIV class=bar></DIV>
			<OL class=dp-c>
			<LI class=alt><SPAN><SPAN class=vars>$message</SPAN><SPAN>&nbsp;.=&nbsp;'&lt;'&nbsp;.&nbsp;network_site_url(&nbsp;</SPAN><SPAN class=string>"wp-login.php?action=rp&amp;key=$key&amp;login="</SPAN><SPAN>&nbsp;.&nbsp;rawurlencode(&nbsp;</SPAN><SPAN class=vars>$user_login</SPAN><SPAN>&nbsp;),&nbsp;'login'&nbsp;)&nbsp;.&nbsp;</SPAN><SPAN class=string>"&gt;\r\n"</SPAN><SPAN>;&nbsp;&nbsp;</SPAN></SPAN></LI></OL></DIV>
			网上有说把这行两头的尖括号去掉就可，但稍微试了下不管用，因为是小问题就没管它了，只要在邮件里的链接，稍微修改下，把&amp;改成&，再把&gt;去掉后链接就可以正常使用了（可能会有误差，自己看一下链接内容。）
		</p>
	</div>
	<?php
}
//发送邮件
function sendMail($email,$title,$content){
	require __DIR__ . '/email.class.php';
	
	$email_configs = get_settings('tle_email_check');
	
	$smtpserverport =$email_configs["mailport"];//SMTP服务器端口//企业QQ:465、126:25
	$smtpserver = $email_configs["mailsmtp"];//SMTP服务器//QQ:ssl://smtp.qq.com、126:smtp.126.com
	$smtpusermail = $email_configs["mailuser"];//SMTP服务器的用户邮箱
	$smtpemailto = $email;//发送给谁
	$smtpuser = $email_configs["mailuser"];//SMTP服务器的用户帐号
	$smtppass = $email_configs["mailpass"];//SMTP服务器的用户密码
	$mailtitle = $title;//邮件主题
	$mailcontent = $content;//邮件内容
	$mailtype = "HTML";//邮件格式（HTML/TXT）,TXT为文本邮件
	//************************ 配置信息 ****************************
	$smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
	$smtp->debug = false;//是否显示发送的调试信息
	$state = $smtp->sendmail($smtpemailto, $smtpusermail, $mailtitle, $mailcontent, $mailtype);
	return $state;
}
//SMTP邮箱设置
if(!function_exists('mail_smtp')){
	function mail_smtp( $phpmailer ){
		$email_configs = get_settings('tle_email_check');
		$phpmailer->From = $email_configs["mailuser"];//发件人地址
		$phpmailer->FromName = $email_configs["mailuser"];//发件人昵称
		$phpmailer->Host = $email_configs["mailsmtp"];//SMTP服务器地址
		$phpmailer->Port = $email_configs["mailport"];
		//SMTP邮件发送端口, 常用端口有：25、465、587, 具体联系邮件服务商
		$phpmailer->SMTPSecure = $email_configs["mailport"];
		//SMTP加密方式(SSL/TLS)没有为空即可，
		//具体联系邮件服务商, 以免设置错误, 无法正常发送邮件
		$phpmailer->Username = $email_configs["mailuser"];//邮箱帐号
		$phpmailer->Password = $email_configs["mailpass"];//SMTP的授权码
		$phpmailer->IsSMTP();
		$phpmailer->SMTPAuth = true;//启用SMTPAuth服务
	}
	add_action('phpmailer_init','mail_smtp');
}
?>