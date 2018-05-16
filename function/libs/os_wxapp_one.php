<?php

include_once __DIR__.'/wxapp_one_user.php';
include_once __DIR__.'/wxapp_one_session.php';
include_once __DIR__.'/wxapp_one_swiper.php';

/**
 * 微信小程序 one 操作类
 */
class OSWXAppOne extends ZBlogPHP {
    /**
     * GetWXUserList
     * 获取小程序用户列表
     */
    public function GetWXUserList($select = null, $w = null, $order = null, $limit = null, $option = null) {
        global $zbp;
        if (empty($select)) {
            $select = array('*');
        }
        if (empty($w)) {
            $w = array();
        }

        $sql = $zbp->db->sql->Select(
            $zbp->table['os_wxapp_users'],
            $select,
            $w,
            $order,
            $limit,
            $option
        );
        $result = $zbp->GetListType('WXAppOneUser', $sql);

        return $result;
    }

    /**
     * GetSwiperList
     * 获取Swiper列表
     */
    public function GetSwiperList($select = null, $w = null, $order = null, $limit = null, $option = null) {
        global $zbp;
        if (empty($select)) {
            $select = array('*');
        }
        if (empty($w)) {
            $w = array();
        }

        $sql = $zbp->db->sql->Select(
            $zbp->table['os_wxapp_one_swiper'],
            $select,
            $w,
            $order,
            $limit,
            $option
        );
        $result = $zbp->GetListType('WXAppOneSwiper', $sql);

        return $result;
    }
}
