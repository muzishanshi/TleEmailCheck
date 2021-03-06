<?php
/* 
Plugin Name: TleEmailCheck
Plugin URI: https://github.com/muzishanshi/TleEmailCheck
Description: TleEmailCheckForWordpress插件使用更加通用的发送邮件方式，实现带密码、邮箱验证码注册用户的功能，此插件解决了在localhost邮件不能发送成功的问题。
Version: 1.0.5
Author: 二呆
Author URI: http://www.tongleer.com
License: 
*/
define("TLE_EMAIL_CHECK_VERSION",5);
add_action( 'register_form', 'tle_email_check_form' );
function tle_email_check_form() {
	$tleEmailCheck_configs = get_settings('tle_email_check');
	?>
	<?php if($tleEmailCheck_configs["isEnableJQuery"]=="y"){?>
	<script src="https://libs.baidu.com/jquery/1.11.1/jquery.min.js"></script>
	<?php }?>
	<?php if($tleEmailCheck_configs["isAddPassword"]=="y"){?>
	<p>
		<label for="password">密码<br/>
			<input id="password" class="input" type="password" tabindex="30" size="25" value="" name="password" />
		</label>
    </p>
    <p>
		<label for="repeat_password">确认密码<br/>
			<input id="repeat_password" class="input" type="password" tabindex="40" size="25" value="" name="repeat_password" />
		</label>
    </p>
	<?php }?>
	<p>
		<label>邮箱验证码&nbsp;&nbsp;<button type="button" id="sendsmsmsg">发送</button><br />
			<input type="text" name="emailcode" id="emailcode" class="input" size="25" tabindex="20" />
			<input type="hidden" name="sitetitle" id="sitetitle" value="<?=bloginfo('name');?>" class="input" size="25" tabindex="20" />
			<input type="hidden" id="isAddPassword" value="<?=$tleEmailCheck_configs["isAddPassword"];?>" />
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
		$.post("<?=admin_url('options-general.php?page=tle-email-check&t=sendsms');?>",{user_email:user_email,sitetitle:$('#sitetitle').val()},function(data){
			var data=JSON.parse(data);
			if(data.error_code==0){
				settime();
			}else{
				alert(data.message);
			}
		});
	});
	var timer;
	var countdown=60;
	function settime() {
		if (countdown == 0) {
			$("#sendsmsmsg").html("重新发送");
			$("#sendsmsmsg").attr('disabled',false);
			countdown = 60;
			clearTimeout(timer);
			return;
		} else {
			$("#sendsmsmsg").html(countdown+"秒后重新发送");
			$("#sendsmsmsg").attr('disabled',true);
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
		if($("#password").val()==""&&$("#isAddPassword").val()=="y"){
			alert("请输入密码");
			return false;
		}
		if($("#repeat_password").val()==""&&$("#isAddPassword").val()=="y"){
			alert("请输入确认密码");
			return false;
		}
		if($("#password").val()!=""&&$("#password").val().length<8&&$("#isAddPassword").val()=="y"){
			alert("密码长度至少8位");
			return false;
		}
		if($("#password").val()!=$("#repeat_password").val()&&$("#isAddPassword").val()=="y"){
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
	$tleEmailCheck_configs = get_settings('tle_email_check');
	$emailcode = isset($_POST['emailcode']) ? addslashes(trim($_POST['emailcode'])) : '';
	$password = isset($_POST['password']) ? addslashes(trim($_POST['password'])) : '';
	$repeat_password = isset($_POST['repeat_password']) ? addslashes(trim($_POST['repeat_password'])) : '';
	if (!isset($emailcode) || empty($emailcode)) {
		return $errors->add( 'emailcodeempty', '<strong>错误</strong>: 请输入邮箱验证码。' );
	} elseif (strcasecmp($emailcode,$_SESSION['mailcode'])!=0) {
		return $errors->add( 'emailcodefail', '<strong>错误</strong>: 邮箱验证码不正确。' );
	}else if (empty($password)&&$tleEmailCheck_configs["isAddPassword"]=="y") {
        return $errors->add( 'passwords_null', "<strong>错误</strong>: 请输入密码" );
    }else if ( $password !== $repeat_password &&$tleEmailCheck_configs["isAddPassword"]=="y") {
        return $errors->add( 'passwords_not_matched', "<strong>错误</strong>: 两次输入密码不相同" );
    }else if ( $password!=""&&strlen( $password ) < 8 &&$tleEmailCheck_configs["isAddPassword"]=="y") {
        return $errors->add( 'password_too_short', "<strong>ERROR</strong>: 密码长度至少8位" );
    }else if(isset($_SESSION["newmail"])&&$user_email!=$_SESSION["newmail"]){
		return $errors->add( 'user_email_error', "<strong>ERROR</strong>: 填写邮箱和发送验证码的邮箱不一致" );
	}
}

add_action( 'user_register', 'ts_email_check_password', 100 );
function ts_email_check_password( $user_id ){
	$password = isset($_POST['password']) ? addslashes(trim($_POST['password'])) : '';
    $userdata = array();
    $userdata['ID'] = $user_id;
    if ( $password !== '' ) {
        $userdata['user_pass'] = $password;
    }
    $new_user_id = wp_update_user( $userdata );
	
	//重置短信验证码
	$_SESSION["mailcode"]=mt_rand(100000,999999);
}

add_filter( 'gettext', 'ts_email_check_password_text' );
function ts_email_check_password_text ( $text ) {
    if ( $text == '注册确认信将会被寄给您。'||$text=='A password will be e-mailed to you.' ) {
        $text = '若密码为空，则生成一个。密码长度至少8位';
    }
    return $text;
}
$tleEmailCheckT = isset($_GET['t']) ? addslashes(trim($_GET['t'])) : '';
if(isset($tleEmailCheckT)){
    if($tleEmailCheckT == 'configTleEmailCheck'){
        update_option('tle_email_check', array('isEnableJQuery' => $_REQUEST['isEnableJQuery'], 'isAddPassword' => $_REQUEST['isAddPassword'], 'mailsmtp' => $_REQUEST['mailsmtp'], 'mailport' => $_REQUEST['mailport'], 'mailuser' => $_REQUEST['mailuser'], 'mailpass' => $_REQUEST['mailpass'], 'mailsecure' => $_REQUEST['mailsecure']));
    }
	if($tleEmailCheckT == 'sendsms'){
        session_start();
		date_default_timezone_set('Asia/Shanghai');
		//重置短信验证码
		$_SESSION["mailcode"]=mt_rand(100000,999999);

		$user_email = isset($_POST['user_email']) ? addslashes(trim($_POST['user_email'])) : '';//发送到的用户名
		$sitetitle = isset($_POST['sitetitle']) ? addslashes(trim($_POST['sitetitle'])) : '';
		$result=sendMailCheck($user_email,$sitetitle.'注册验证码','您的注册验证码是'.$_SESSION['mailcode']);
		if($result){
			$_SESSION['newmail'] = $user_email;
			$json=json_encode(array("error_code"=>0,"message"=>"发送验证码成功"));
			echo $json;
			exit;
		}else{
			$json=json_encode(array("error_code"=>-1,"message"=>"发送验证码失败，请稍后再尝试。"));
			echo $json;
			exit;
		}
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

add_filter( 'plugin_action_links', 'tle_email_check_add_link', 10, 2 );
function tle_email_check_add_link( $actions, $plugin_file ) {
  static $plugin;
  if (!isset($plugin))
    $plugin = plugin_basename(__FILE__);
  if ($plugin == $plugin_file) {
	$settings = array('settings' => '<a href="admin.php?page=tle-email-check">' . __('Settings') . '</a>');
	$site_link  = array('version'=>'<span id="tle_email_check_updateinfo"></span><script>xmlHttp=new XMLHttpRequest();xmlHttp.open("GET","https://www.tongleer.com/api/interface/TleEmailCheck.php?action=update&version='.TLE_EMAIL_CHECK_VERSION.'",true);xmlHttp.send(null);xmlHttp.onreadystatechange=function () {if (xmlHttp.readyState ==4 && xmlHttp.status ==200){document.getElementById("tle_email_check_updateinfo").innerHTML=xmlHttp.responseText;}}</script>','contact' => '<a href="http://mail.qq.com/cgi-bin/qm_share?t=qm_mailme&email=diamond0422@qq.com" target="_blank">反馈</a>','club' => '<a href="http://club.tongleer.com" target="_blank">论坛</a>');
	$actions  = array_merge($settings, $actions);
	$actions  = array_merge($site_link, $actions);
  }
  return $actions;
}

add_action('admin_menu', 'tle_email_check_menu');
function tle_email_check_menu(){
    add_options_page('邮箱验证', '邮箱验证', 'manage_options', 'tle-email-check', 'tle_email_check_options');
}
function tle_email_check_options(){
    $tleEmailCheck_configs = get_settings('tle_email_check');
	?>
	<div class="wrap">
		<h2>邮箱验证设置:</h2>
		<form method="get" action="">
			<p>
				是否加载jquery:<br />
				<input type="radio"  value="n" name="isEnableJQuery" <?php if($tleEmailCheck_configs["isEnableJQuery"]=='n'){?>checked<?php }?>> 关闭
				<input type="radio" value="y" name="isEnableJQuery" <?php if($tleEmailCheck_configs["isEnableJQuery"]!='n'){?>checked<?php }?>> 开启
				<br />对于登陆页面已被其他插件加载jquery的可选择性关闭
			</p>
			<p>
				是否增加密码表单项:<br />
				<input type="radio"  value="n" name="isAddPassword" <?php if($tleEmailCheck_configs["isAddPassword"]=='n'){?>checked<?php }?>> 关闭
				<input type="radio" value="y" name="isAddPassword" <?php if($tleEmailCheck_configs["isAddPassword"]!='n'){?>checked<?php }?>> 开启
				<br />对于其他插件已添加密码框的情况可选择性关闭
			</p>
			<p>
				smtp服务器:<br /><input type="text" name="mailsmtp" value="<?=$tleEmailCheck_configs["mailsmtp"];?>" required placeholder="smtp服务器" size="50" /><br />已验证QQ、126、yandex邮箱可成功发送
			</p>
			<p>
				smtp服务器端口:<br /><input type="text" name="mailport" value="<?=$tleEmailCheck_configs["mailport"];?>" required placeholder="smtp服务器端口" size="50" /><br />465、25等
			</p>
			<p>
				smtp服务器邮箱用户名：<br /><input type="text" name="mailuser" value="<?=$tleEmailCheck_configs["mailuser"];?>" required placeholder="smtp服务器邮箱用户名" size="50" />
			</p>
			<p>
				smtp服务器邮箱密码：<br /><input type="password" name="mailpass" value="<?=$tleEmailCheck_configs["mailpass"];?>" required placeholder="smtp服务器邮箱密码" size="50" />
			</p>
			<p>
				安全类型：<br />
				<select name="mailsecure">
					<option value="ssl" <?=$tleEmailCheck_configs["mailsecure"]=="ssl"?"selected":"";?>>ssl</option>
					<option value="tls" <?=$tleEmailCheck_configs["mailsecure"]=="tls"?"selected":"";?>>tls</option>
					<option value="none" <?=$tleEmailCheck_configs["mailsecure"]=="none"?"selected":"";?>>none</option>
				</select>
			</p>
			<p>
				<input type="hidden" name="t" value="configTleEmailCheck" />
				<input type="hidden" name="page" value="tle-email-check" />
				<input type="submit" value="修改配置" />
			</p>
		</form>
		<h3>问答</h3>
		<p>
			<font color="red">1、关于发送邮件问题</font><br />
			（1）若发送邮件验证码失败，则可以考虑是否跟其他插件或主题目录functions.php中的配置有冲突；<br />
			（2）关于smtp服务器和端口配置方法可联系diamond0422@qq.com以协助解决。<br />
			<font color="red">2、SMTP搭配</font><br />
			（1）smtp.exmail.qq.com:465	企业邮箱 	SSL	登陆密码<br />
			（2）smtp.qq.com:465		个人邮箱 	SSL	授权码<br />
			（3）smtp.126.com:465		个人邮箱	SSL	授权码<br />
			（4）smtp.126.com:25		个人邮箱	SSL	登录密码<br />
			（5）smtp.yandex.com:465			个人邮箱	SSL 登陆密码<br />
			<font color="red">3、为何忘记密码、密码重置时，点击链接提示重置链接会无效？（我的WordPress版本是5.0）</font><br />
			在wp-login.php中找到下面这段代码，大概在369行。<br />
			<DIV class=dp-highlighter><DIV class=bar></DIV>
			<OL class=dp-c>
			<LI class=alt><SPAN><SPAN class=vars>$message</SPAN><SPAN>&nbsp;.=&nbsp;'&lt;'&nbsp;.&nbsp;network_site_url(&nbsp;</SPAN><SPAN class=string>"wp-login.php?action=rp&amp;key=$key&amp;login="</SPAN><SPAN>&nbsp;.&nbsp;rawurlencode(&nbsp;</SPAN><SPAN class=vars>$user_login</SPAN><SPAN>&nbsp;),&nbsp;'login'&nbsp;)&nbsp;.&nbsp;</SPAN><SPAN class=string>"&gt;\r\n"</SPAN><SPAN>;&nbsp;&nbsp;</SPAN></SPAN></LI></OL></DIV>
			替换成
			<DIV class=dp-highlighter><DIV class=bar></DIV>
			<OL class=dp-c>
			<LI class=alt><SPAN><SPAN class=vars>$message</SPAN><SPAN>&nbsp;.=network_site_url(</SPAN><SPAN class=string>"wp-login.php?action=rp&amp;key=$key&amp;login="</SPAN><SPAN>&nbsp;.&nbsp;rawurlencode(</SPAN><SPAN class=vars>$user_login</SPAN><SPAN>),&nbsp;'login')&nbsp;.&nbsp;</SPAN><SPAN class=string>"\r\n"</SPAN><SPAN>;&nbsp;&nbsp;</SPAN></SPAN></LI></OL></DIV>
			然后在wp-includes/pluggable.php中找到下面这段代码，大概1903行。<br />
			<DIV class=dp-highlighter><DIV class=bar></DIV>
			<OL class=dp-c>
			<LI class=alt><SPAN><SPAN class=vars>$message</SPAN><SPAN>&nbsp;.=&nbsp;'&lt;'&nbsp;.&nbsp;network_site_url(</SPAN><SPAN class=string>"wp-login.php?action=rp&amp;key=$key&amp;login="</SPAN><SPAN>&nbsp;.&nbsp;rawurlencode(</SPAN><SPAN class=vars>$user</SPAN><SPAN>-&gt;user_login),&nbsp;'login')&nbsp;.&nbsp;</SPAN><SPAN class=string>"&gt;\r\n\r\n"</SPAN><SPAN>;&nbsp;&nbsp;</SPAN></SPAN></LI></OL></DIV>
			替换成
			<DIV class=dp-highlighter><DIV class=bar></DIV>
			<OL class=dp-c>
			<LI class=alt><SPAN><SPAN class=vars>$message</SPAN><SPAN>&nbsp;.=&nbsp;network_site_url(</SPAN><SPAN class=string>"wp-login.php?action=rp&amp;key=$key&amp;login="</SPAN><SPAN>&nbsp;.&nbsp;rawurlencode(</SPAN><SPAN class=vars>$user</SPAN><SPAN>-&gt;user_login),&nbsp;'login')&nbsp;.&nbsp;</SPAN><SPAN class=string>"\r\n\r\n"</SPAN><SPAN>;&nbsp;&nbsp;</SPAN></SPAN></LI></OL></DIV>
			即可，也就是把两段代码两端的尖括号删掉。
		</p>
	</div>
	<?php
}
//发送邮件
function sendMailCheck($email,$title,$content){
	require_once dirname(__FILE__).'/PHPMailer/PHPMailerAutoload.php';
	$phpMailer = new PHPMailer();
	$email_configs = get_settings('tle_email_check');
	$phpMailer->isSMTP();
	$phpMailer->SMTPAuth = true;
	$phpMailer->Host = $email_configs["mailsmtp"];
	$phpMailer->Port = $email_configs["mailport"];
	$phpMailer->Username = $email_configs["mailuser"];
	$phpMailer->Password = $email_configs["mailpass"];
	$phpMailer->isHTML(true);
	if ('none' != $email_configs["mailsecure"]) {
		$phpMailer->SMTPSecure = $email_configs["mailsecure"];
	}
	$phpMailer->setFrom($email_configs["mailuser"], $title);
	$phpMailer->addAddress($email, $email);
	$phpMailer->Subject = $title;
	$phpMailer->Body    = $content;
	if(!$phpMailer->send()) {
		return false;
	} else {
		return true;
	}
}
//SMTP邮箱设置-修复系统邮件系统
if(!function_exists('mailer2smtp')){
	function mailer2smtp( $phpmailer ){
		$email_configs = get_settings('tle_email_check');
		$phpmailer->From = $email_configs["mailuser"];//发件人地址
		$phpmailer->FromName = $email_configs["mailuser"];//发件人昵称
		$phpmailer->Host = $email_configs["mailsmtp"];//SMTP服务器地址
		$phpmailer->Port = $email_configs["mailport"];
		//SMTP邮件发送端口, 常用端口有：25、465、587, 具体联系邮件服务商
		$phpmailer->SMTPSecure = $email_configs["mailsecure"];
		//SMTP加密方式(SSL/TLS)没有为空即可，
		//具体联系邮件服务商, 以免设置错误, 无法正常发送邮件
		$phpmailer->Username = $email_configs["mailuser"];//邮箱帐号
		$phpmailer->Password = $email_configs["mailpass"];//SMTP的授权码
		$phpmailer->IsSMTP();
		$phpmailer->SMTPAuth = true;//启用SMTPAuth服务
	}
	add_action('phpmailer_init','mailer2smtp');
}
?>