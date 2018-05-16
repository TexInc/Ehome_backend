<?php
/**
 * 微信小程序用户验证
 */
class WXAppOneSession extends Base {
    public function __construct() {
        global $zbp;
        parent::__construct($zbp->table['os_wxapp_session'], $zbp->datainfo['os_wxapp_session'], __CLASS__);
        $this->UpdateTime = time();
    }

    /**
     * 获取数据库内指定UID的数据
     * @param int $id 指定UID
     * @return bool
     */
    public function LoadInfoByUID($id) {
        $id = (int) $id;
        $s = $this->db->sql->Select($this->table, array('*'), array(array('=', 'wxapp_session_UID', $id)), null, null, null);

        $array = $this->db->Query($s);
        if (count($array) > 0) {
            $this->LoadInfoByAssoc($array[0]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取数据库内指定WXUID的数据
     * @param int $id 指定WXUID
     * @return bool
     */
    public function LoadInfoByWXUID($id) {
        $id = (int) $id;
        $s = $this->db->sql->Select($this->table, array('*'), array(array('=', 'wxapp_session_WXUID', $id)), null, null, null);

        $array = $this->db->Query($s);
        if (count($array) > 0) {
            $this->LoadInfoByAssoc($array[0]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取数据库内指定Token的数据
     * @param int $token 指定Token
     * @return bool
     */
    public function LoadInfoByToken($token) {
        $s = $this->db->sql->Select($this->table, array('*'), array(array('=', 'wxapp_session_Token', $token)), null, null, null);

        $array = $this->db->Query($s);
        if (count($array) > 0) {
            $this->LoadInfoByAssoc($array[0]);
            return true;
        } else {
            return false;
        }
    }
}
