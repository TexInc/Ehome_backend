<?php
/**
 * os_wxapp_one_Event_GetArticleCover - 输出文章的封面图片
 *
 * @param  {object} $article 文章对象
 * @param  {number} $num = 1 输出数量
 */
function os_wxapp_one_Event_GetArticleCover($article, $num = 1) {
    global $zbp;

    // 如果存在预先存储的缩略图，就使用准备好的
    if ($article->Metas->os_wxapp_images && $num > 3) {
        $covers = explode(',', $article->Metas->os_wxapp_images);
        return array_slice($covers, 0, (int)$num);
    } elseif ($article->Metas->os_wxapp_images_count > 0) {
        $covers = explode(',', $article->Metas->os_wxapp_images);
        if ($num == 1) {
            return $covers[0];
        } else {
            return array_slice($covers, 0, (int)$num);
        }
    }

    // 如果文章没有预先读取 开始正则匹配
    $pattern = "/<img.*?src=[\'|\"](.*?)[\'|\"].*?[\/]?>/i";
    $content = $article->Content;
    preg_match_all($pattern, $content, $matchContent);

    if(isset($matchContent[1][0])){

        // 匹配成功可以存起来，后面出现重新保存文章时，内容也会被另外一个函数更新
        $article->Metas->os_wxapp_images = implode(",", $matchContent[1]);
        $article->Metas->os_wxapp_images_count = count($matchContent[1]);
        $article->Save();

        if ($num == 1) {
            return $matchContent[1][0];
        }
        return array_slice($matchContent[1], 0, (int)$num);
    } else {
        if ($num == 1) {
            return $zbp->host.'zb_users/plugin/os_wxapp_one/images/noimg.png';
        }
        return array($zbp->host.'zb_users/plugin/os_wxapp_one/images/noimg.png');
    }
}

/**
 * 更新文章的图片到缩略图
 */
function os_wxapp_one_Event_PostArticleCore(&$article) {
    global $zbp;
    $pattern = "/<img.*?src=[\'|\"](.*?)[\'|\"].*?[\/]?>/i";
    $content = $article->Content;
    preg_match_all($pattern, $content, $matchContent);

    if(isset($matchContent[1][0])){
        $article->Metas->os_wxapp_images = implode(",", $matchContent[1]);
        $article->Metas->os_wxapp_images_count = count($matchContent[1]);
    }
}

/**
 * 登录/注册小程序用户
 */
function os_wxapp_one_Event_wxappLogin($data, $sessionKey) {
    global $zbp;
    $openid = $data->openId;
    $u = new WXAppOneUser;
    $u->LoadInfoByOpenID($openid);
    if ($u->ID == 0) {
        $u->OpenID = $openid;
    }
    if (isset($data->unionId)) {
        $u->UnionID = $data->unionId;
    }
    $u->Nickname = $data->nickName;
    $u->Avatar = $data->avatarUrl;
    $u->SessionKey = $sessionKey;
    $u->UpdateTime = time();
    $u->Save();

    return $u;
}

/**
 * 记录用户的sessionid
 */
function os_wxapp_one_Event_wxappSession($wxUser, $token) {
    global $zbp;
    $wxSession = new WXAppOneSession;
    $wxSession->LoadInfoByWXUID($wxUser->ID);
    if ($wxSession->ID == 0) {
        $wxSession->WXUID = $wxUser->ID;
    }
    // 当用户ID不为0时，更新到记录中
    if ($wxSession->UID == 0 && $wxUser->UID != 0) {
        $wxSession->UID = $wxUser->UID;
    }
    $wxSession->Token = $token;
    $wxSession->UpdateTime = time();
    $wxSession->Save();
}

/**
 * 绑定网站用户
 */
function os_wxapp_one_EventBindUser() {
    global $zbp;
    $status = os_wxapp_one_EventUserLogin();
    if (!$status) {
        return $status;
    }
    $sessionid = GetVars('sessionid', 'POST');
    $wxSession = new WXAppOneSession;
    $wxSession->LoadInfoByToken($sessionid);
    $wxSession->UID = $zbp->user->ID;
    $wxSession->Save();
    $wxUser = new WXAppOneUser;
    $wxUser->LoadInfoByID($wxSession->WXUID);
    $wxUser->UID = $zbp->user->ID;
    $wxUser->Save();
    $zbp->user->Metas->os_wxapp_avatar = $wxUser->Avatar;
    $zbp->user->Save();

    return true;
}

/**
 * 用户登录验证
 */
function os_wxapp_one_EventUserLogin() {
    global $zbp;
    $username = trim(GetVars("username", "POST"));
    $password = trim(GetVars("password", "POST"));
    if ($zbp->Verify_MD5(GetVars('username', 'POST'), GetVars('password', 'POST'), $m)) {
        $zbp->user = $m;
        $un = $m->Name;
        $ps = $m->PassWord_MD5Path;
        if ($zbp->user->Status != 0) {
            return false;
        }
        return true;
    } else {
        return false;
    }
}

/**
 * 解除绑定网站用户
 */
function os_wxapp_one_EventUnBindUser() {
    global $zbp;
    $sessionid = GetVars('sessionid', 'POST');

    $wxSession = new WXAppOneSession;
    $wxSession->LoadInfoByToken($sessionid);
    $wxSession->UID = 0;
    $wxSession->Save();
    $wxUser = new WXAppOneUser;
    $wxUser->LoadInfoByID($wxSession->WXUID);
    $wxUser->UID = 0;
    $wxUser->Save();

    return true;
}

/**
 * 输出子分类
 */
function os_wxapp_one_Event_GetCategoryChilds($id) {
    global $zbp;
    $result = $zbp->GetCategoryList(
        array('*'),
        array(
            array('=', 'cate_ParentID', $id)
        )
    );

    $childList = array();
    foreach ($result as $item) {
        $childs = os_wxapp_one_Event_GetCategoryChilds($item->ID);
        $childList = array_merge($childList, $childs);
    }
    $result = array_merge($result, $childList);

    return $result;
}
