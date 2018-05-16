<?php
/**
 * ONE的Swiper管理
 */
class WXAppOneSwiper extends Base {
    public function __construct() {
        global $zbp;
        parent::__construct($zbp->table['os_wxapp_one_swiper'], $zbp->datainfo['os_wxapp_one_swiper'], __CLASS__);
    }
}
