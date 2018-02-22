<?php
define( "CLIENTAREA", false );

require_once( "../WeChat_Class.php" );

use \Illuminate\Database\Capsule\Manager as Capsule;

$qc = new \WeChatLogin\WeLogin();

$code = $_GET['code'];
$state = $_GET['state'];
//换成自己的接口信息
if (empty($code)) $this->error('授权失败');
$token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$qc->readInc("appid").'&secret='.$qc->readInc("appsecret").'&code='.$code.'&grant_type=authorization_code';
$token = json_decode(file_get_contents($token_url));
if (isset($token->errcode)) {
    echo '<h1>错误：</h1>'.$token->errcode;
    echo '<br/><h2>错误信息：</h2>'.$token->errmsg;
    exit;
}
$access_token_url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid='.$qc->readInc("appid").'&grant_type=refresh_token&refresh_token='.$token->refresh_token;
//转成对象
$access_token = json_decode(file_get_contents($access_token_url));
if (isset($access_token->errcode)) {
    echo '<h1>错误：</h1>'.$access_token->errcode;
    echo '<br/><h2>错误信息：</h2>'.$access_token->errmsg;
    exit;
}
$user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token->access_token.'&openid='.$access_token->openid.'&lang=zh_CN';
//转成对象
$user_info = json_decode(file_get_contents($user_info_url));
if (isset($user_info->errcode)) {
    echo '<h1>错误：</h1>'.$user_info->errcode;
    echo '<br/><h2>错误信息：</h2>'.$user_info->errmsg;
    exit;
}

$info =  json_decode(json_encode($user_info),true);//返回的json数组转换成array数组
$oid = $info['openid'];

/*
//打印用户信息
echo '<pre>';
print_r($user_info);
echo '</pre>';
*/

$weurl = explode('http:', $info['headimgurl']);
$nikename = $info["nickname"]; // 昵称
$avatar = $weurl[1]; // 头像

$openID = Capsule::table('mod_wechat_login')->where('openid', $oid)->first();

// 数据库 OPENID 是否存在
//print_r($openID['SELECT']['result']);die();

if ( $openID ) {
    
    $uid = $openID->uid;
	
	// 更新内容到数据库
	Capsule::table('mod_wechat_login')->where('uid', $uid)->update([
		'nickname' 	=> $nikename,
		'avatar'	=> $avatar,
		'openid'	=> $oid,
	]);
    
    // 写入 SESSION UID
    $_SESSION['uid'] = $uid;
	
	// 获取 UID
	
	$userinfo 	= Capsule::table('tblclients')->where('id', $uid)->first();
	$username 	= $userinfo->firstname . ' ' . $userinfo->lastname;
	
	// 取出值
	$login_uid 	= $userinfo->id;
	$login_pwd 	= $userinfo->password;
	$language 	= $userinfo->language;

	// 更新登录时间登录IP
	$fullhost = gethostbyaddr($remote_ip);
	Capsule::table('tblclients')->where('id', $login_uid)->update([
		'lastlogin' 	=> date('Y-m-d H:i:s'),
		'ip'			=> $remote_ip,
		'host'			=> $fullhost,
	]);
    $_SESSION['uid'] = $login_uid;
	if ($login_cid) {
		$_SESSION['cid'] = $login_cid;
	}
	// 写入登录数据
    $_SESSION['upw'] = \WHMCS\Authentication\Client::generateClientLoginHash($login_uid, $login_cid, $login_pwd);
	$_SESSION['tkval'] = genRandomVal();
	if ($language) {
		$_SESSION['Language'] = $language;
	}
	run_hook('ClientLogin', ['userid' => $login_uid]);
	logActivity($username . ' - 通过 微信扫码 登录');
	$loginsuccess = true;
    
    //提示
    die( $qc->WeMessage('success', '登录成功！') );
	
} else {
// 数据库 OPENID 不存在

	// 判断当前是否登录
	if ( $_SESSION['uid'] ) {
		// 不存在 UID,存在 $_SESSION['uid'] 代表已登录为绑定，执行绑定操作。		
		Capsule::table('mod_wechat_login')->insert([
        	'uid'		=> $_SESSION['uid'],
        	'openid' 	=> $oid,
        	'nickname'	=> $nikename,
        	'avatar'	=> $avatar,
        ]);
    
	    //提示
		die( $qc->WeMessage('success', '关联成功！') );
	    
	} else {
	// 未登录
	
		// 不存在UID，不存在 $_SESSION['uid'] 代表未登录，未绑定，输出需先绑定再登录页面。
    
	    //提示
	    die( $qc->WeMessage('error', '尚未绑定QQ<br/>请前往用户中心进行绑定', false) );
	}
	
}


?>