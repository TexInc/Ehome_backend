<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action='root';
if (!$zbp->CheckRights($action)) {$zbp->ShowError(6);die();}
if (!$zbp->CheckPlugin('os_wxapp_one')) {$zbp->ShowError(48);die();}

$type = GetVars("type", "GET");
switch ($type) {
    case 'base':
        $save_param = array(
            "filter",
            "filter_art",
            "tuis",
            "cates"
        );

        foreach ($save_param as $v) {
            $zbp->Config('os_wxapp_one')->$v = GetVars($v, "post");
        }

        $zbp->SaveConfig('os_wxapp_one');
        $zbp->SetHint('good', "保存成功");
        Redirect("./main.php");
    break;
    case 'core':
        $save_param = array(
            "appid",
            "secret",
        );

        foreach ($save_param as $v) {
            $zbp->Config('os_wxapp_one')->$v = GetVars($v, "post");
        }

        $zbp->SaveConfig('os_wxapp_one');
        $zbp->SetHint('good', "保存成功");
        Redirect("./set.php");
    break;
    case "swiper":
        $id = GetVars("id", "GET");
        $save_param = array(
            "Imgurl",
            "Type",
            "Related",
            "Order",
            "Status"
        );
        $swiper = new WXAppOneSwiper;
        if (!empty($id)) {
            $swiper->LoadInfoByID((int) $id);
        }
        $imgurl = GetVars("Imgurl", "POST");
        if (empty($imgurl)) {
            $zbp->SetHint('bad', "请输入图片地址");
            Redirect("./swiper.php");
            break;
        }
        foreach ($save_param as $v) {
            $swiper->$v = GetVars($v, "POST");
        }
        FilterMeta($swiper);
        $swiper->Save();

        $zbp->SetHint('good', "保存成功");
        Redirect("./swiper.php");
    break;
    case "swiper_del":
        $id = GetVars("id", "GET");
        $swiper = new WXAppOneSwiper;
        if (!empty($id)) {
            $swiper->LoadInfoByID((int) $id);
        }
        $swiper->Del();

        $zbp->SetHint('good', "删除成功");
        Redirect("./swiper.php");
    break;
    default:
        Redirect("./main.php");
    break;
}
