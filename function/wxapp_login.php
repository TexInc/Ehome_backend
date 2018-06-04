<?php
/**
 * 小程序登录状态维护插件
 */

/**
 * 确认微信小程序用户的登录状态，并返回对应用户的信息
 */
function os_wxapp_one_CheckSession(&$json = []) {
    global $zbp;
    $sessionid = GetVars('sessionid', 'POST');
    if (empty($sessionid)) {
        $json['code'] = 200000;
        $json['message'] = "您尚未登录";
        return false;
    }

    $wxSession = new WXAppOneSession;
    $wxSession->LoadInfoByToken($sessionid);
    if ($wxSession->ID == 0) {
        $json['session'] = $sessionid;
      	$json['wxSession->ID'] = $wxSession->ID;
        $json['code'] = 200000;
        $json['message'] = "登录验证失败";
        return false;
    }
    if (time() - $wxSession->UpdateTime > 15 * 24 * 60 * 60) {
        $json['code'] = 200000;
        $json['message'] = "登录信息超时";
        return false;
    }

    $wxUser = new WXAppOneUser;
    $wxUser->LoadInfoByID($wxSession->WXUID);

    $mem = new Member;
    $mem->LoadInfoByID($wxUser->UID);
    if ($mem->ID == 0) {
        $mem->Name = $wxUser->Nickname;
        $mem->Avatar = $wxUser->Avatar;
        $mem->Metas->os_wxapp_avatar = $wxUser->Avatar;
    }
    $zbp->user = $mem;
    return $mem;
}

/**
 * 用户登录
 */
function os_wxapp_one_Login(&$json = []) {
    global $zbp;
    // 获取传入数据
    $code = trim(GetVars('code', 'POST'));
    $rawData = trim(GetVars('rawData', 'POST'));
    $signature = trim(GetVars('signature', 'POST'));
    $encryptedData = trim(GetVars('encryptedData', 'POST'));
    $iv = trim(GetVars('iv', 'POST'));

    $params = array(
        "appid"    => $zbp->Config('os_wxapp_one')->appid,
        "secret"   => $zbp->Config('os_wxapp_one')->secret,
        "js_code"  => $code,
        "grant_type"   => "authorization_code",
    );

    $res = os_wxapp_one_makeRequest("https://api.weixin.qq.com/sns/jscode2session", $params);

    if ($res['code'] !== 200 || !isset($res['result']) || !isset($res['result'])) {
        $json['code'] = 200200;
        $json['message'] = "请求微信服务器失败";
        return false;
    }
    $reqData = json_decode($res['result'], true);
    if (!isset($reqData['session_key'])) {
        $json['code'] = 200201;
        $json['message'] = "请求微信服务器异常";
        return false;
    }

    $sessionKey = $reqData['session_key'];

    $signature2 = sha1($rawData . $sessionKey);

    if ($signature2 !== $signature) {
        $json['code'] = 200202;
        $json['message'] = "验证信息不匹配";
        return false;
    }

    $pc = new WXBizDataCrypt($params['appid'], $sessionKey);
    $errCode = $pc->decryptData($encryptedData, $iv, $data);
    $data = json_decode($data);

    if ($errCode !== 0) {
        $json['code'] = 200203;
        $json['message'] = "解析微信用户信息失败";
        return false;
    }

    $sessionid = os_wxapp_one_randomFromDev(16);

    if (!$sessionid) {
        $sessionid = os_wxapp_one_NormalRandom();
    }

    $u = os_wxapp_one_Event_wxappLogin($data, $sessionKey);
    os_wxapp_one_Event_wxappSession($u, $sessionid);

    $result = (Object) array();
    $result->sessionid = $sessionid;
    if ($u->UID > 0) {
        $mem = new Member;
        $mem->LoadInfoByID($u->UID);
        $result->userinfo = os_wxapp_one_JSON_UserToJson($mem);
    } else {
        $result->userinfo = (Object) array(
            "Nickname" => $u->Nickname,
            "Avatar"   => $u->Avatar,
        );
    }
	$json['data'] = $data;
    $json['code'] = 100000;
    $json['result'] = $result;
    return true;
}



/**
 * 发起http请求
 * @param string $url 访问路径
 * @param array $params 参数，该数组多于1个，表示为POST
 * @param int $expire 请求超时时间
 * @param array $extend 请求伪造包头参数
 * @param string $hostIp HOST的地址
 * @return array    返回的为一个请求状态，一个内容
 */
function os_wxapp_one_makeRequest($url, $params = array(), $expire = 0, $extend = array(), $hostIp = ''){
    global $zbp;
    if (empty($url)) {
        return array('code' => '100');
    }

    $_curl = curl_init();
    $_header = array(
        'Accept-Language: zh-CN',
        'Connection: Keep-Alive',
        'Cache-Control: no-cache'
    );
    // 方便直接访问要设置host的地址
    if (!empty($hostIp)) {
        $urlInfo = parse_url($url);
        if (empty($urlInfo['host'])) {
            $urlInfo['host'] = substr(DOMAIN, 7, -1);
            $url = "http://{$hostIp}{$url}";
        } else {
            $url = str_replace($urlInfo['host'], $hostIp, $url);
        }
        $_header[] = "Host: {$urlInfo['host']}";
    }

    // 只要第二个参数传了值之后，就是POST的
    if (!empty($params)) {
        curl_setopt($_curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($_curl, CURLOPT_POST, true);
    }

    if (substr($url, 0, 8) == 'https://') {
        curl_setopt($_curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($_curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    }
    curl_setopt($_curl, CURLOPT_URL, $url);
    curl_setopt($_curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($_curl, CURLOPT_USERAGENT, 'API PHP CURL');
    curl_setopt($_curl, CURLOPT_HTTPHEADER, $_header);

    if ($expire > 0) {
        curl_setopt($_curl, CURLOPT_TIMEOUT, $expire); // 处理超时时间
        curl_setopt($_curl, CURLOPT_CONNECTTIMEOUT, $expire); // 建立连接超时时间
    }

    // 额外的配置
    if (!empty($extend)) {
        curl_setopt_array($_curl, $extend);
    }

    $result['result'] = curl_exec($_curl);
    $result['code'] = curl_getinfo($_curl, CURLINFO_HTTP_CODE);
    $result['info'] = curl_getinfo($_curl);
    if ($result['result'] === false) {
        $result['result'] = curl_error($_curl);
        $result['code'] = -curl_errno($_curl);
    }

    curl_close($_curl);
    return $result;
}

/**
 * 读取/dev/urandom获取随机数
 * @param $len
 * @return mixed|string
 */
function os_wxapp_one_randomFromDev($len) {
    $fp = @fopen('/dev/urandom','rb');
    $result = '';
    if ($fp !== FALSE) {
        $result .= @fread($fp, $len);
        @fclose($fp);
    } else {
        return false;
    }
    // convert from binary to string
    $result = base64_encode($result);
    // remove none url chars
    $result = strtr($result, '+/', '-_');

    return substr($result, 0, $len);
}

/**
 * 没有/dev/urandom的时候，返回普通的随机
 */
function os_wxapp_one_NormalRandom() {
    $num = mt_rand(0, time());
    $num = str_shuffle("os_wxapp_one".$num."os369.com");
    $res = md5($num);
    return substr($res, 8, 16);
}
