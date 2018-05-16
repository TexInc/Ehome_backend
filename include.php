<?php

include_once __DIR__.'/database/index.php';
include_once __DIR__.'/function/main.php';
include_once __DIR__.'/third_libs/wxapp/wxBizDataCrypt.php';

#注册插件
RegisterPlugin("os_wxapp_one", "ActivePlugin_os_wxapp_one");
/**
 * 注册接收处理指令
 */
$GLOBALS['actions']['os_wxapi'] = 6;
function ActivePlugin_os_wxapp_one() {
    global $zbp;
    Add_Filter_Plugin('Filter_Plugin_ViewAuto_Begin','os_wxapp_one_WatchApi');
    Add_Filter_Plugin('Filter_Plugin_Cmd_Begin','os_wxapp_one_WatchCmdApi');
    Add_Filter_Plugin('Filter_Plugin_PostArticle_Core','os_wxapp_one_Event_PostArticleCore');
}

function os_wxapp_one_SubMenu($id){

	$arySubMenu = array(
		0 => array('内容设置', 'main', 'left', false),
		1 => array('Swiper设置', 'swiper', 'left', false),
		2 => array('插件设置', 'set', 'left', false),
		3 => array('配置帮助', 'https://www.os369.com/app/item/os_wxapp_one#help', 'right', true),
	);

	foreach($arySubMenu as $k => $v){
		echo '<a href="./'.$v[1].'.php" '.($v[3]==true?'target="_blank"':'').'><span class="m-'.$v[2].' '.($id==$k?'m-now':'').'">'.$v[0].'</span></a>';
	}
}

function InstallPlugin_os_wxapp_one() {
    os_wxapp_one_CreateTable();
}

function UninstallPlugin_os_wxapp_one() {}
