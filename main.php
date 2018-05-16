<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action='root';
if (!$zbp->CheckRights($action)) {$zbp->ShowError(6);die();}
if (!$zbp->CheckPlugin('os_wxapp_one')) {$zbp->ShowError(48);die();}

$blogtitle = '微信小程序 ONE 内容配置';
require $blogpath . 'zb_system/admin/admin_header.php';
require $blogpath . 'zb_system/admin/admin_top.php';
?>
<style>
.edit-input {
    display: block;
    width: 100%;
    height: 40px;
    line-height: 24px;
    font-size: 14px;
    padding: 8px;
    box-sizing: border-box;
}
</style>
<div id="divMain">
    <div class="divHeader"><?php echo $blogtitle;?></div>
    <div class="SubMenu"><?php os_wxapp_one_SubMenu(0);?></div>
    <div id="divMain2">
        <form action="./save.php?type=base" method="post">
            <table border="1" class="tableFull tableBorder tableBorder-thcenter" style="max-width: 1000px">
                <thead>
                    <tr>
                        <th width="200px">配置名称</th>
                        <th>配置内容</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>首页推荐文章</td>
                        <td>
                            <input name="tuis" type="text" class="edit-input" value="<?php echo $zbp->Config('os_wxapp_one')->tuis; ?>" placeholder="填写文章的ID，用英文逗号分隔" />
                        </td>
                    </tr>
                    <tr>
                        <td>首页过滤分类</td>
                        <td>
                            <input name="filter" type="text" class="edit-input" value="<?php echo $zbp->Config('os_wxapp_one')->filter; ?>" placeholder="填写分类的ID，用英文逗号分隔" />
                        </td>
                    </tr>
                    <tr>
                        <td>首页过滤文章</td>
                        <td>
                            <input name="filter_art" type="text" class="edit-input" value="<?php echo $zbp->Config('os_wxapp_one')->filter_art; ?>" placeholder="填写文章的ID，用英文逗号分隔" />
                        </td>
                    </tr>
                    <tr>
                        <td>分类阅读中心</td>
                        <td>
                            <input name="cates" type="text" class="edit-input" value="<?php echo $zbp->Config('os_wxapp_one')->cates; ?>" placeholder="填写分类的ID，用英文逗号分隔" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <input type="submit" value="保存配置" style="margin: 0; font-size: 1em;" />
        </form>
    </div>
</div>

<?php
require $blogpath . 'zb_system/admin/admin_footer.php';
RunTime();
?>
