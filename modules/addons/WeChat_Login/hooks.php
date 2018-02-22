<?php
use WHMCS\Database\Capsule;

add_hook('ClientAreaHeadOutput', 1, function ($vars){
	return "
<script>
function WeChat_Login( val ) {
    $('body').append('<div class=\"login_Div\"><span class=\"close\" onclick=\"close_Login();\">&times;</span><iframe id=\"login_frame\" name=\"login_frame\" style=\"margin: 0;padding: 0;\" frameborder=\"0\" scrolling=\"no\" width=\"100%\" height=\"100%\" src=\"{$vars['systemurl']}/modules/addons/WeChat_Login/oauth/?'+val+'\"></iframe></div><div class=\"mask_Div\"></div>');
}
function WeChat_Login2( val ) {
	$('.whmcs-body').hide();
	$('.qq-body').remove();
	$('.wechat-body').remove();
    $('.login-content').append('<div class=\"wechat-body\"><iframe id=\"login_frame\" name=\"login_frame\" style=\"margin: 0;padding: 0;\" frameborder=\"0\" scrolling=\"no\" width=\"100%\" height=\"100%\" src=\"{$vars['systemurl']}/modules/addons/WeChat_Login/oauth/?'+val+'\"></iframe></div>');
}
function close_Login() {
    $('.login_Div').remove();
    $('.mask_Div').remove();
}
</script>
<link href=\"{$vars['systemurl']}/modules/addons/WeChat_Login/assets/css/style.css?v7\" rel=\"stylesheet\" type=\"text/css\">";
});

add_hook('ClientAreaPage', 1, function ($vars){
    $setting = Capsule::table('mod_wechat_login_setting')->first();

    if ($setting) {
	    $userID = $_SESSION['uid'];
	    
        if (isset($userID)) {
	        
			$info = Capsule::table('mod_wechat_login')->where('uid', $userID)->first();

            if ($info) {
                $link 		= $setting->logout;
                $status 	= 'check';
            } else {
                $link 		= $setting->bind;
                $status 	= 'alert';
            }
		    
			$avatar = $info->avatar;
			$nickname = $info->nickname;

        } else {
            $link = $setting->login;
        }
        
    } else {
        $link = "未设置按钮";
    }

    return [
        'wechatlink' 	=> $link,
        'weavatar' 		=> $avatar,
        'wenickname' 	=> $nickname,
        'westatus' 		=> $status,
    ];
});