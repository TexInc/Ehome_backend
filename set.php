<?php
require '../../../zb_system/function/c_system_base.php';
require '../../../zb_system/function/c_system_admin.php';
$zbp->Load();
$action='root';
if (!$zbp->CheckRights($action)) {$zbp->ShowError(6);die();}
if (!$zbp->CheckPlugin('os_wxapp_one')) {$zbp->ShowError(48);die();}

$blogtitle = '微信小程序 ONE 插件配置';
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
    <div class="SubMenu"><?php os_wxapp_one_SubMenu(2);?></div>
    <div id="divMain2">
        <div class="tips">
            <?php
                $hasOpenSSL = extension_loaded("openssl");
                if (empty($hasOpenSSL)) {
                    echo '<p style="line-height: 40px; color: #f00; font-size: 16px; font-weight: 700;">Error: 请您开启PHP扩展的openssl</p>';
                }
                $hasCurl = extension_loaded("curl");
                if (empty($hasCurl)) {
                    echo '<p style="line-height: 40px; color: #f00; font-size: 16px; font-weight: 700;">Error: 请您开启PHP扩展的curl</p>';
                }
            ?>
        </div>
        <form action="./save.php?type=core" method="post">
            <table border="1" class="tableFull tableBorder tableBorder-thcenter" style="max-width: 1000px">
                <thead>
                    <tr>
                        <th width="200px">配置名称</th>
                        <th>配置内容</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>AppID</td>
                        <td>
                            <input name="appid" type="text" class="edit-input" value="<?php echo $zbp->Config('os_wxapp_one')->appid; ?>" placeholder="请填写小程序的AppID" />
                        </td>
                    </tr>
                    <tr>
                        <td>Secret</td>
                        <td>
                            <input name="secret" type="text" class="edit-input" value="<?php echo $zbp->Config('os_wxapp_one')->secret; ?>" placeholder="请填写小程序的授权Secret" />
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
