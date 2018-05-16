<?php
/**
 * 相关数据库结构信息
 */
$os_wxapp_one_database = array(
    /**
     * 小程序用户表
     */
    'os_wxapp_users'     => array(
        'name'           => '%pre%os_wxapp_users',
        'info'           => array(
            'ID'          => array('wxapp_user_ID','integer','',0),
            'UID'         => array('wxapp_user_UID','integer','',0),
            'OpenID'      => array('wxapp_user_OpenID','string',255,''),
            'SessionKey'  => array('wxapp_user_SessionKey','string',255,''),
            'UnionID'     => array('wxapp_user_UnionID','string',255,''),
            'Nickname'    => array('wxapp_user_Nickname','string',255,''),
            'Avatar'      => array('wxapp_user_Avatar','string',255,''),
            'Lock'        => array('wxapp_user_Lock','integer','',0),
            'UpdateTime'  => array('wxapp_user_UpdateTime','integer','',0),
            'Meta'        => array('wxapp_user_Meta','string','',''),
        ),
    ),

    /**
     * 登录维护表
     */
    'os_wxapp_session'   => array(
        'name'           => '%pre%os_wxapp_session',
        'info'           => array(
            'ID'          => array('wxapp_session_ID','integer','',0),
            'UID'         => array('wxapp_session_UID','integer','',0),
            'WXUID'       => array('wxapp_session_WXUID','integer','',0),
            'Token'       => array('wxapp_session_Token','string',255,''),
            'UpdateTime'  => array('wxapp_session_UpdateTime','integer','',0),
            'Meta'        => array('wxapp_session_Meta','string','',''),
        ),
    ),

    /**
     * Swiper管理表
     */
    'os_wxapp_one_swiper'   => array(
        'name'           => '%pre%os_wxapp_one_swiper',
        'info'           => array(
            'ID'          => array('wxapp_swiper_ID','integer','',0),
            'Related'     => array('wxapp_swiper_Related','string',255,''),
            'Type'        => array('wxapp_swiper_Type','string',255,''),
            'Imgurl'      => array('wxapp_swiper_Imgurl','string',255,''),
            'Order'       => array('wxapp_swiper_Order','integer','',0),
            'Status'      => array('wxapp_swiper_Status','integer','',0),
            'Meta'        => array('wxapp_swiper_Meta','string','',''),
        ),
    ),
);
foreach ($os_wxapp_one_database as $k => $v) {
    $table[$k] = $v['name'];
    $datainfo[$k] = $v['info'];
}
/**
 * 检查是否有创建数据库
 */
function os_wxapp_one_CreateTable() {
    global $zbp, $os_wxapp_one_database;
    foreach ($os_wxapp_one_database as $k => $v) {
        if (!$zbp->db->ExistTable($v['name'])) {
            $s = $zbp->db->sql->CreateTable($v['name'],$v['info']);
            $zbp->db->QueryMulit($s);
        }
    }
}
