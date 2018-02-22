<?php
define( "CLIENTAREA", false );
require_once("../WeChat_Class.php");

use \Illuminate\Database\Capsule\Manager as Capsule;

$userID = $_SESSION['uid'];

$action = $_SERVER['QUERY_STRING'];
	
if ($action) {
    switch ($action) {
        case 'login':
            $qc = new \WeChatLogin\WeLogin();
			$qc->wechat_login();
            break;
        case 'bind':
		    // 获取 UID
		    $userinfo 	= Capsule::table('mod_wechat_login')->where('uid', $userID)->first();
			
			// 数据库 UID 是否存在
			if ( $userinfo ) {
				
				// 数据库存在 UID 代表已经储存过数据，现在是解绑操作。
				
				Capsule::table('mod_wechat_login')->where('uid', $_SESSION['uid'])->delete();
			    
			    //提示
				die( WeChatMessage('success', '取消关联成功!') );
				
			} else {
			// 数据库 UID 不存在
            	$qc = new \WeChatLogin\WeLogin();
				$qc->wechat_login();
			}
            break;
        default:
    
		    //提示
			die( WeChatMessage('error', '未知错误！') );
		    
            break;
    }
}
