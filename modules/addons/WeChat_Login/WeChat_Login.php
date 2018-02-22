<?php
if (!defined('WHMCS')) {
	die('This file cannot be accessed directly');
}
use WHMCS\Database\Capsule;
// NeWorld Manager 开始

// 引入文件
require  ROOTDIR . '/modules/addons/NeWorld/library/class/NeWorld.Common.Class.php';

// NeWorld Manager 结束

function WeChat_Login_config() {
	$configarray = array(
		'name' 			=> 'WeChat Login',
		'description' 	=> 'This module allows your customers to use WeChat account login WHMCS.',
		'version' 		=> '1.0',
		'author' 		=> '<a href="http://neworld.org" target="_blank">NeWorld</a>',
		'fields' 		=> []
	);
	
	$configarray['fields']['appkey'] = [
		'FriendlyName' 	=> 'App Key',
		'Type' 			=> 'text',
		'Size' 			=> '25',
		'Description' 		=> '请输入 APPKEY，请前往 <a href="http://open.weixin.qq.com" target="_blank">微信开放平台</a> 获取'
	];

	$configarray['fields']['appsecret'] = [
        "FriendlyName" 	=> "App Secret",
        "Type" 			=> "text",
        "Size" 			=> "25",
        "Description" 	=> "请输入 App Secret",
	];
	
	return $configarray;
}

function WeChat_Login_activate() {
	try {
		if (!Capsule::schema()->hasTable('mod_wechat_login')) {
			Capsule::schema()->create('mod_wechat_login', function ($table) {
				$table->increments('uid');
				$table->text('openid');
				$table->text('nickname');
				$table->text('avatar');
			});
		}
		if (!Capsule::schema()->hasTable('mod_wechat_login_setting')) {
			
			Capsule::schema()->create('mod_wechat_login_setting', function ($table) {
				$table->text('login');
				$table->text('bind');
				$table->text('logout');
			});
			
		}
	} catch (Exception $e) {
		return [
			'status' => 'error',
			'description' => '不能创建表 mod_wechat_login_setting: ' . $e->getMessage()
		];
	}
	return [
		'status' => 'success',
		'description' => '模块激活成功. 点击 配置 对模块进行设置。'
	];
}

function WeChat_Login_deactivate() {
	try {
		Capsule::schema()->dropIfExists('mod_wechat_login');
		Capsule::schema()->dropIfExists('mod_wechat_login_setting');
		return [
			'status' => 'success',
			'description' => '模块卸载成功'
		];
	} catch (Exception $e) {
		return [
			'status' => 'error',
			'description' => 'Unable to drop tables: ' . $e->getMessage()
		];
	}
}

function WeChat_Login_output($vars) {
    $systemurl = \WHMCS\Config\Setting::getValue('SystemURL');
    $modulelink = $vars['modulelink'];
    $result = '<link rel="stylesheet" href="'.$systemurl.'/modules/addons/WeChat_Login/style.css?v3">';
    
    if (isset($_REQUEST['action'])) {
        switch ($_REQUEST['action']) {
            case 'init':
            	$action = Capsule::table('mod_wechat_login_setting')
		            ->insert([
		            	'login'		=> "&lt;a href=\"javascript:WeChat_Login('login');\" class=\"btn btn-block btn-wechat\"&gt;&lt;i class=\"fa fa-wechat\"&gt;&lt;/i&gt; 微信登陆&lt;/a&gt;",
		            	'bind' 	=> "&lt;a href=\"javascript:WeChat_Login('bind');\" class=\"btn btn-sm btn-wechat\"&gt;&lt;i class=\"fa fa-wechat\"&gt;&lt;/i&gt; 绑定微信&lt;/a&gt;",
		            	'logout'	=> "&lt;a href=\"javascript:if(confirm('您确定取消 微信 账号绑定吗？'))WeChat_Login('bind');\" class=\"btn btn-sm btn-wechat\"&gt;&lt;i class=\"fa fa-wechat\"&gt;&lt;/i&gt; 解绑微信&lt;/a&gt;",
		            ]);
		        if ( $action ) {
                    $alert = Message('success', '<p>初始化按钮样式成功，请在模板文件 clientareahome.tpl 和 login.tpl 中合适的地方加入 </p>
                    	<p>{$wechatlink} 是登录按钮，绑定按钮，解绑按钮，一个按钮多用。</p>
                    	<p>{$weavatar} 是头像，{$wenickname} 是昵称，例如</p>
                    	<code style="margin-top: 10px;">{if $weavatar}
&lt;span class="avatars"&gt;
&lt;img src="{$weavatar}" alt="{$wenickname}" /&gt;
{/if}
</code>');
                } else {
                    $alert = Message('danger', '初始化失败');
                }
            	breeak;
            case 'edit':
            	$setting = Capsule::table('mod_wechat_login_setting')->first();
                if ( $setting ) {
                    $login 	= $setting->login;
                    $bind 	= $setting->bind;
                    $logout = $setting->logout;
                }
                $editor = '
				<div class="panel-body">
				    <form action="'.$modulelink.'" method="post">
				      <input type="hidden" name="action" value="submitedit">
				      <div class="form-group">
				        <label>登录页按钮</label>
				        <textarea class="form-control" rows="3" name="login">'.$login.'</textarea>
				      </div>
				      <div class="form-group">
				        <label>客户中心绑定按钮</label>
				        <textarea class="form-control" rows="3" name="bind">'.$bind.'</textarea>
				      </div>
				      <div class="form-group">
				        <label>客户中心解除绑定</label>
				        <textarea class="form-control" rows="3" name="logout">'.$logout.'</textarea>
				      </div>
				        <button type="submit" class="btn btn-primary">提交修改</button>
				    </form>
				</div>';
                break;
            case 'submitedit':
                if (empty($_POST['login']) || empty($_POST['bind']) || empty($_POST['logout'])) {
	                
                    $alert = Message('danger', '修改按钮样式失败，不允许修改值为空。');
                    
                } else {
	                
                    $login = html_entity_decode($_POST['login']);
                    $bind = html_entity_decode($_POST['bind']);
                    $logout = html_entity_decode($_POST['logout']);

                    $action = Capsule::table('mod_wechat_login_setting')
                            ->update([
                            	'login'		=> $login,
                            	'bind' 		=> $bind,
                            	'logout'	=> $logout,
                            ]);
                    if ( $action ) {
                        $alert = Message('success', '<p>修改按钮样式成功，请在模板文件 clientareahome.tpl 和 login.tpl 中合适的地方加入 </p>
                        	<p>{$wechatlink} 是登录按钮，绑定按钮，解绑按钮，一个按钮多用。</p>
                        	<p>{$weavatar} 是头像，{$wenickname} 是昵称，例如</p>
                        	<code style="margin-top: 10px;">{if $avatar}
&lt;span class="avatars"&gt;
&lt;img src="{$avatar}" alt="{$nickname}" /&gt;
{/if}
</code>');
                    } else {
                        $alert = Message('danger', '按钮样式没有修改。');
                    }
                }
                break;
            case 'count':
                $qqconnect = Capsule::table('mod_wechat_login')->orderBy('uid','ASC')->get();
                //print_r($qqconnect);die();
                foreach ($qqconnect as $key => $value) {
	                $getName = Capsule::table('tblclients')->where('id', $value->uid)->first();
	                $info[$key]['name']			= $getName->firstname . ' ' . $getName->lastname;
					$info[$key]['id'] 			= $value->uid;
					$info[$key]['openid'] 		= $value->openid;
					$info[$key]['avatar'] 		= $value->avatar;
					$info[$key]['nickname'] 	= $value->nickname;
                	$list .= "<tr>
					    <td>{$info[$key]['id']}</td>
					    <td><a href='clientssummary.php?userid={$info[$key]['id']}'>{$info[$key]['name']}</a></td>
					    <td>{$info[$key]['openid']}</td>
					    <td>{$info[$key]['nickname']}</td>
					    <td><img src='{$info[$key]['avatar']}' style='width: 36px;' /></td>
					</tr>";
				}
                break;
            default:
                break;
        }
    }
    $count = Capsule::table('mod_wechat_login')->count();
    if ($count == 0) {
        $count = '暂无记录';
    } else {
        $count = '<a href="'.$modulelink.'&action=count" class="btn btn-info btn-xs">'.$count.'</a>';
    }
    
    $setting = Capsule::table('mod_wechat_login_setting')->first();
    if ( !$setting ) {
	     $button = '<a href="'.$modulelink.'&action=init" class="btn btn-xs btn-default">初始化按钮</a>';
    } else {
	     $button = '<a href="'.$modulelink.'&action=edit" class="btn btn-xs btn-default">编辑按钮样式</a>';
    }
    
    $header = '<div class="alert alert-info"><strong>回调地址</strong> '.$systemurl.'/modules/addons/WeChat_Login/oauth/callback.php</div>
    	<a href="'.$modulelink.'" class="btn btn-default btn-xs" style="margin-bottom: 20px;"><i class="fa fa-chevron-circle-left" aria-hidden="true"></i> 返回</a>
    <div class="panel panel-default">';
    if ( $editor ) {
		$result .= '<div class="panel-heading">编辑按钮信息</div>'.$editor;
	} elseif ( $list ) {
		$result .= '<table class="table">
		    <thead>
			    <tr>
			        <th>UID</th>
			        <th>用户名</th>
			        <th>OPENID</th>
			        <th>昵称</th>
			        <th>头像</th>
			    </tr>
		    </thead>
		    <tbody>
		    '.$list.'
		    </tbody>
		</table>';
	} else {
		$result .= '<table class="table">
		    <thead>
			    <tr>
			        <th>模块名称</th>
			        <th>绑定数量</th>
			        <th>按钮信息</th>
			    </tr>
		    </thead>
		    <tbody>
				<tr>
				    <td>WeChat Login</td>
				    <td>
				        '.$count.'
				    </td>
				    <td>
				        '.$button.'
				    </td>
				</tr>
		    </tbody>
		</table>';
	}
	$footer = '</div>';

    echo $alert.$header.$result.$footer;
}

if ( !function_exists('Message') ) {
	function Message( $type, $value ) {
		return "<div class=\"alert alert-{$type} alert-dismissible fade in\" role=\"alert\">{$value}</div>";
	}
}