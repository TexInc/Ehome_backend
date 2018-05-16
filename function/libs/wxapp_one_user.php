<?php
/**
 * 微信小程序用户操作
 */
class WXAppOneUser extends Base {
    public function __construct() {
        global $zbp;
        parent::__construct($zbp->table['os_wxapp_users'], $zbp->datainfo['os_wxapp_users'], __CLASS__);

        $this->UpdateTime = time();
    }

    /**
     * 获取数据库内指定UID的数据
     * @param int $id 指定UID
     * @return bool
     */
    public function LoadInfoByUID($id) {
        $id = (int) $id;
        $s = $this->db->sql->Select($this->table, array('*'), array(array('=', 'wxapp_user_UID', $id)), null, null, null);

        $array = $this->db->Query($s);
        if (count($array) > 0) {
            $this->LoadInfoByAssoc($array[0]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取数据库内指定OpenID的数据
     * @param int $openid 指定OpenID
     * @return bool
     */
    public function LoadInfoByOpenID($openid) {
        $s = $this->db->sql->Select($this->table, array('*'), array(array('=', 'wxapp_user_OpenID', $openid)), null, null, null);

        $array = $this->db->Query($s);
        if (count($array) > 0) {
            $this->LoadInfoByAssoc($array[0]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取数据库内指定UnionID的数据
     * @param int $unionid 指定UnionID
     * @return bool
     */
    public function LoadInfoByUnionID($unionid) {
        $s = $this->db->sql->Select($this->table, array('*'), array(array('=', 'wxapp_user_UnionID', $unionid)), null, null, null);

        $array = $this->db->Query($s);
        if (count($array) > 0) {
            $this->LoadInfoByAssoc($array[0]);
            return true;
        } else {
            return false;
        }
    }
}
