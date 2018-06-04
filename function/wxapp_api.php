<?php
/**
 * 监听请求是否符合API规则
 */
function os_wxapp_one_WatchApi($url) {
    global $zbp, $os_wxapp_one;
    $status = strripos($url, '/os_wxapi');
    if ($status !== 0) {
        $status2 = strripos($url, '/index.php/os_wxapi');
        if ($status2 !== 0) {
            return false;
        }
    }

    // 匹配路由
    $regexp = "/\/os_wxapi\/([a-z0-9\-\_]*)\/([a-z0-9\-\_]*)\/?([a-z0-9\-\_]*)/";
    $routes = array();
    preg_match_all($regexp, $url, $routes);

    $version = null;
    if (isset($routes[1]) && count($routes[1]) > 0) {
        $version = $routes[1][0];
    }
    $type = null;
    if (isset($routes[2]) && count($routes[2]) > 0) {
        $type = $routes[2][0];
    }
    $param = null;
    if (isset($routes[3]) && count($routes[3]) > 0) {
        $param = $routes[3][0];
    }

    $json = array();

    switch ($version) {
        case 'v1':
            os_wxapp_one_api_v1($type, $param, $json);
        break;
        default:
            $json['code'] = -1;
            $json['message'] = "未找到定义接口";
        break;
    }

    echo json_encode($json);
    exit;
}

/**
 * 监听cmd接口上api
 */
function os_wxapp_one_WatchCmdApi() {
    global $zbp;
    $action = GetVars('act','GET');
    if ($action != "os_wxapi") {
        return false;
    }
    $version = GetVars('v','GET');
    $mode = GetVars('mode','GET');

    $json = array();

    switch ($version) {
        case 'v1':
            os_wxapp_one_api_v1($mode, null, $json);
        break;
        default:
            $json['code'] = -1;
            $json['message'] = "未找到定义接口";
        break;
    }

    echo json_encode($json);
    exit;
}

/**
 * v1版本接口处理
 */
function os_wxapp_one_api_v1($type, $param, &$json = []) {
    global $zbp;
    switch ($type) {
        case 'home':
            os_wxapp_one_APIHome($json);
        break;
        case 'list':
            os_wxapp_one_APIList($json);
        break;
        case 'search':
            os_wxapp_one_APISearch($json);
        break;
        case 'catelist':
            os_wxapp_one_APICateList($json);
        break;
        case 'article':
            os_wxapp_one_APIArticle($json);
        break;
        case 'page':
            os_wxapp_one_APIPage($json);
        break;
        case 'comment':
            os_wxapp_one_APIComment($json);
        break;
        case 'postcomment':
            os_wxapp_one_APIPostComment($json);
        break;
        case 'user':
            os_wxapp_one_APIUserInfo($json);
        break;
        case 'login':
            os_wxapp_one_Login($json);
        break;
        case 'reg':
            os_wxapp_one_reg($json);
        break;
        case 'repair':
            os_wxapp_one_repair($json);
        break;
        case 'feedback':
            os_wxapp_one_feedback($json);
        break;
	    case 'facereg':
        	os_wxapp_one_facereg($json);
        break;
        case 'getinfo':
        	os_wxapp_one_GetUserInfo($json);
        break;
        case 'bind':
            os_wxapp_one_APIBind($json);
        break;
        case 'unbind':
            os_wxapp_one_APIUnBind($json);
        case 'gettable':
            os_wxapp_one_gettable($json);
        break;
        default:
            $json['code'] = -2;
            $json['message'] = "未找到定义接口";
        break;
    }
}

/**
 * 首页数据输出
 */
function os_wxapp_one_APIHome(&$json = []) {
    global $zbp, $os_wxapp_one;

    $page = GetVars("page", "GET");
    $page = (int)$page>0 ? (int)$page : 1;

    // 首页过滤分类
    $filter = array();
    if ($zbp->Config('os_wxapp_one')->filter) {
        $list = explode(",", $zbp->Config('os_wxapp_one')->filter);
        foreach ($list as $id) {
            $filter[] = array("<>", "log_CateID", $id);
            $childs = os_wxapp_one_Event_GetCategoryChilds($id);
            foreach ($childs as $item) {
                $filter[] = array("<>", "log_CateID", $item->ID);
            }
        }
    }
    // 首页过滤文章
    $filter_art = array();
    if ($zbp->Config('os_wxapp_one')->filter_art) {
        $list = explode(",", $zbp->Config('os_wxapp_one')->filter_art);
        foreach ($list as $id) {
            $filter_art[] = array("<>", "log_ID", $id);
        }
    }

    $result = os_wxapp_one_JSON_GetArticleList(10, null, $page, null, $filter, $filter_art);

    // 首页加载其它内容
    if ($page == 1) {
        $tuis = array();
        $w = array();
        $w[] = array("=", "log_Status", "0");
        $list = array();
        if ($zbp->Config('os_wxapp_one')->tuis) {
            $list = explode(",", $zbp->Config('os_wxapp_one')->tuis);
        }
        if (count($list) > 0) {
            $w[] = array("IN", "log_ID", $list);
            $zbp->GetArticleList(null, $w);
            foreach ($list as $v) {
                if (isset($zbp->posts[$v])) {
                    $tuis[] = os_wxapp_one_JSON_PostToJson($zbp->posts[$v]);
                }
            }
        } else {
            $w[] = array(">", "log_PostTime", time() - 365 * 24 * 60 * 60);
            $order = array("log_ViewNums" => "DESC");
            $list = $zbp->GetArticleList(null, $w, $order, array(4));
            foreach ($list as $item) {
                $tuis[] = os_wxapp_one_JSON_PostToJson($item);
            }
        }
        $result->medias = $tuis;

        $swipers = $os_wxapp_one->GetSwiperList(null, array(
            array("=", "wxapp_swiper_Status", "1"),
        ), array("wxapp_swiper_Order" => "DESC"));
        if (count($swipers) > 0) {
            $result->swiper = array();
            foreach ($swipers as $item) {
                $result->swiper[] = os_wxapp_one_JSON_SwiperToJson($item);
            }
        }
    }

    $json['code'] = 100000;
    $json['result'] = $result;

    return true;
}

/**
 * 首页数据输出
 */
function os_wxapp_one_APIList(&$json = []) {
    global $zbp;

    $page = GetVars("page", "GET");
    $page = (int)$page>0 ? (int)$page : 1;

    $cateid = GetVars("cateid", "GET");

    if (empty($cateid)) {
        $json['code'] = 200100;
        $json['message'] = "分类ID异常";
        return false;
    }
    $cate = $zbp->GetCategoryByID((int) $cateid);
    if (empty($cate->ID)) {
        $json['code'] = 200101;
        $json['message'] = "分类不存在";
        return false;
    }

    $result = os_wxapp_one_JSON_GetArticleList(10, $cateid, $page, true);
    $result->cate = os_wxapp_one_JSON_CateToJson($cate);

    $json['code'] = 100000;
    $json['result'] = $result;

    return true;
}

/**
 * 搜索功能
 */
function os_wxapp_one_APISearch(&$json = []) {
    global $zbp;
    $keyword = GetVars("keyword", "GET");
    if (empty($keyword)) {
        $json['code'] = 200300;
        $json['message'] = "请输入关键词";
        return false;
    }

    $page = GetVars("page", "GET");
    $page = (int)$page>0 ? (int)$page : 1;

    $data = (Object) array();
    $data->list = array();

    $w = array();
    $w[] = array("=", "log_Status", "0");
    $w[] = array("=", "log_Type", "0");
    $w[] = array("search", "log_Title", "log_Alias", "log_ID", "log_Intro", "log_Content", $keyword);

    $order = array("log_PostTime" => "DESC");
    $pagebar = new Pagebar('');
    $pagebar->PageNow = $page;
    $pagebar->PageCount = 10;

    $limit = array(($page -1 ) * $pagebar->PageCount, $pagebar->PageCount);
    $option = array('pagebar' => $pagebar);

    $list = $zbp->GetArticleList('*', $w, array('log_PostTime' => 'DESC'), $limit, $option);
    foreach ($list as $item) {
        $data->list[] = os_wxapp_one_JSON_PostToJson($item);
    }

    $data->page = $page;
    $data->pages = $pagebar->PageAll;
    $data->pagenext = $pagebar->PageNext;

    $json['code'] = 100000;
    $json['result'] = $data;

    return true;
}

/**
 * 分类列表
 */
function os_wxapp_one_APICateList(&$json = []) {
    global $zbp;

    $w = array();
    $w[] = array("=", "cate_ParentID", "0");

    if ($zbp->Config('os_wxapp_one')->cates) {
        $list = explode(",", $zbp->Config('os_wxapp_one')->cates);
        $show = array();
        foreach ($list as $v) {
            $num = (int) $v;
            if ($num > 0) {
                array_push($show, $v);
            } else {
                $w[] = array("<>", "cate_ID", abs($v));
            }
        }
        if (count($show) > 0) {
            $w[] = array("IN", "cate_ID", $show);
        }
    }

    $cates = $zbp->GetCategoryList(null, $w, array("cate_Order" => "ASC"));
    $result = array();

    foreach ($cates as $item) {
        $result[] = os_wxapp_one_JSON_CateToJson($item);
    }

    $json['code'] = 100000;
    $json['result'] = $result;

    return true;
}

/**
 * 获取文章实例
 */
function os_wxapp_one_APIArticle(&$json = []) {
    global $zbp;

    $id = GetVars("id", "GET");
    if (empty($id)) {
        $json['code'] = 200400;
        $json['message'] = "文章ID不能为空";
      	$json['param'] = $id;
        return false;
    }

    $result = os_wxapp_one_JSON_GetPost($id);

    if (empty($result)) {
        $json['code'] = 200401;
        $json['message'] = "请求文章不存在";
      	$json['param'] = $id;
        return false;
    }

    $json['code'] = 100000;
    $json['result'] = $result;

    return true;
}

/**
 * 获取单页实例
 */
function os_wxapp_one_APIPage(&$json = []) {
    global $zbp;

    $id = GetVars("id", "GET");
    if (empty($id)) {
        $json['code'] = 200402;
        $json['message'] = "页面ID不能为空";
        return false;
    }

    $result = os_wxapp_one_JSON_GetPost($id);

    if (empty($result)) {
        $json['code'] = 200403;
        $json['message'] = "请求页面不存在";
        return false;
    }

    $json['code'] = 100000;
    $json['result'] = $result;

    return true;
}

/**
 * 获取留言实例
 */
function os_wxapp_one_APIComment(&$json = []) {
    global $zbp;

    $id = GetVars("id", "GET");
    if (empty($id)) {
        $json['code'] = 200400;
        $json['message'] = "文章ID不能为空";
        return false;
    }
    $page = GetVars("page", "GET");

    $result = os_wxapp_one_JSON_GetCommentList($id, $page);

    $json['code'] = 100000;
    $json['result'] = $result;

    return true;
}

/**
 * 提交评论
 */
function os_wxapp_one_APIPostComment(&$json = []) {
    global $zbp;

    $mem = os_wxapp_one_CheckSession($json);
    if (!$mem) {
        return false;
    }

    $postid = GetVars("postid", "POST");
    $replyid = GetVars("replyid", "POST");
    $content = GetVars("content", "POST");

    $postid = TransferHTML($postid, '[nohtml]');
    $replyid = TransferHTML($replyid, '[nohtml]');
    $content = TransferHTML($content, '[nohtml]');

    if (empty($replyid)) {
        $replyid = 0;
    }

    if (mb_strlen($content, 'utf-8') < 1) {
        $json['code'] = 200500;
        $json['message'] = "留言正文不能为空";
        return false;
    }

    $_POST = array();

    $_POST['LogID'] = $postid;
    if ($replyid == 0) {
        $_POST['RootID'] = 0;
        $_POST['ParentID'] = 0;
    } else {
        $_POST['ParentID'] = $replyid;
        $c = $zbp->GetCommentByID($replyid);
        $_POST['RootID'] = Comment::GetRootID($c->ID);
    }

    $_POST['AuthorID'] = $mem->ID;
    $_POST['Name'] = $mem->Name;
    $_POST['Email'] = $mem->Email;
    $_POST['HomePage'] = $mem->HomePage;
    $_POST['meta_os_wxapp_status'] = 1;
    $_POST['meta_os_wxapp_avatar'] = $mem->Metas->os_wxapp_avatar;

    $_POST['Content'] = $content;
    $_POST['PostTime'] = Time();
    $_POST['IP'] = GetGuestIP();
    $_POST['Agent'] = GetGuestAgent();

    $cmt = new Comment;

    foreach ($zbp->datainfo['Comment'] as $key => $value) {
        if ($key == 'ID' || $key == 'Meta') { continue; }
        if ($key == 'IsChecking') { continue; }

        if (isset($_POST[$key])) {
            $cmt->$key = GetVars($key, 'POST');
        }
    }

    if ($zbp->option['ZC_COMMENT_AUDIT'] && !$zbp->CheckRights('root')) {
        $cmt->IsChecking = true;
    }

    foreach ($GLOBALS['hooks']['Filter_Plugin_PostComment_Core'] as $fpname => &$fpsignal) {
        $fpname($cmt, $json);
    }

    FilterComment($cmt);
    FilterMeta($cmt);

    $cmt->Save();

    $json['code'] = 100000;
    $json['result'] = os_wxapp_one_JSON_CommentToJson($cmt);

    if ($cmt->IsChecking) {
        CountCommentNums(0, +1);
        $json['message'] = "成功发表留言，但需要审核以后才能显示";
        return false;
    }

    CountPostArray(array($cmt->LogID), +1);
    CountCommentNums(+1, 0);

    $zbp->AddBuildModule('comments');

    $zbp->comments[$cmt->ID] = $cmt;

    foreach ($GLOBALS['hooks']['Filter_Plugin_PostComment_Succeed'] as $fpname => &$fpsignal) {
        $fpname($cmt, $json);
    }

    return true;
}

/**
 * 获取用户信息
 */
function os_wxapp_one_APIUserInfo(&$json = []) {
    global $zbp;
    $mem = os_wxapp_one_CheckSession($json);
    if (!$mem) {
        return false;
    }
    $json['code'] = 100000;
    $json['result'] = os_wxapp_one_JSON_UserToJson($mem);

    return true;
}

/**
 * 获取用户学号信息
 */
function os_wxapp_one_GetUserInfo(&$json = []) {
    global $zbp;
    $mem = os_wxapp_one_CheckSession($json);
    if (!$mem) {
        return false;
    }
  	$openid = GetVars("openid", "POST");
  	$openid = TransferHTML($openid, '[nohtml]');
  	
  	$s = $zbp->db->sql->Select('eh_member', array('*'), array(array('=', 'mem_Openid', $openid)), null, null, null);
  	$array = $zbp->db->Query($s);
    $json['code'] = 100000;
    $json['result'] = $array;

    return true;
}

/**
 * 绑定网站用户
 */
function os_wxapp_one_APIBind(&$json = []) {
    global $zbp;
    $mem = os_wxapp_one_CheckSession($json);
    if (!$mem) {
        return false;
    }
    if ($mem->ID > 0) {
        $json['code'] = 200600;
        $json['message'] = "已有绑定账户";
        return false;
    }
    $status = os_wxapp_one_EventBindUser();
    if ($status) {
        $json['code'] = 100000;
        $json['result'] = $zbp->user->ID;
        return true;
    } else {
        $json['code'] = 200601;
        $json['result'] = "绑定失败，请检查您的账户或密码";
        return false;
    }
}

/**
 * 解绑网站用户
 */
function os_wxapp_one_APIUnBind(&$json = []) {
    global $zbp;
    $mem = os_wxapp_one_CheckSession($json);
    if (!$mem) {
        return false;
    }
    if ($mem->ID == 0) {
        $json['code'] = 100000;
        $json['result'] = "解绑成功";
        return true;
    }
    os_wxapp_one_EventUnBindUser();
    $json['code'] = 100000;
    $json['result'] = "解绑成功";
}


/**
 * 用户注册
 */
function os_wxapp_one_reg(&$json = []) {
    global $zbp;
    // 获取传入数据
  	$mem = os_wxapp_one_CheckSession($json);
    if (!$mem) {
        return false;
    }
  	$openid = GetVars("openid", "POST");
    $name = GetVars("name", "POST");
    $stu_id = GetVars('stu_id', "POST");
  	$gender = GetVars("gender", "POST");
    $department = GetVars('department', 'POST');
    $major = GetVars('major', 'POST');
    $class = GetVars('class', 'POST');
    $dormitory = GetVars('dormitory', 'POST');
    $room = GetVars('room', 'POST');
  	$qqnumber = GetVars("qqnumber", "POST");
    $phonenumber = GetVars('phonenumber', 'POST');
  
  	$openid = TransferHTML($openid, '[nohtml]');
  	$name = TransferHTML($name, '[nohtml]');
    $stu_id = TransferHTML($stu_id, '[nohtml]');
    $gender = TransferHTML($gender, '[nohtml]');
  	$department = TransferHTML($department, '[nohtml]');
    $major = TransferHTML($major, '[nohtml]');
    $class = TransferHTML($class, '[nohtml]');
    $dormitory = TransferHTML($dormitory, '[nohtml]');
    $room = TransferHTML($room, '[nohtml]');
    $qqnumber = TransferHTML($qqnumber, '[nohtml]');
  	$phonenumber = TransferHTML($phonenumber, '[nohtml]');
  	if (empty($openid)) {
        $json['code'] = 2004001;
        $json['message'] = "没有获取到openid";
        return false;
    }
  	$s = $zbp->db->sql->Select('eh_member', array('mem_ID'), array(array('=', 'mem_Openid', $openid)), null, null, null);
  	$array = $zbp->db->Query($s);
  	if (empty($array)) {
        $json['code'] = 2004020;
        $json['message'] = "已经录入过信息";
        return false;
    }
  
    if (empty($name)) {
      	$json['openid'] = $openid;
        $json['code'] = 2004001;
        $json['message'] = "姓名不能为空";
        return false;
    }
  	if (empty($stu_id)) {
        $json['code'] = 200410;
        $json['message'] = "学号不能为空";
        return false;
    }
  	if (empty($name)) {
        $json['code'] = 200402;
        $json['message'] = "性别不能为空";
        return false;
    }
    if (empty($department)) {
        $json['code'] = 200403;
        $json['message'] = "学院不能为空";
        return false;
    }
    if (empty($major)) {
        $json['code'] = 200404;
        $json['message'] = "专业不能为空";
        return false;
    }
    if (empty($class)) {
        $json['code'] = 200405;
        $json['message'] = "班级不能为空";
        return false;
    }
    if (empty($dormitory)) {
        $json['code'] = 200406;
        $json['message'] = "楼栋号不能为空";
        return false;
    }
    if (empty($room)) {
        $json['code'] = 200407;
        $json['message'] = "寝室号不能为空";
        return false;
    }
    if (empty($qqnumber)) {
        $json['code'] = 200408;
        $json['message'] = "QQ号不能为空";
        return false;
    }
    if (empty($phonenumber)) {
        $json['code'] = 200409;
        $json['message'] = "手机号不能为空";
        return false;
    }




    $member = new Member;

    if ($zbp->GetMemberByName($name)->ID > 0) {
        $json['code'] = 200411;
        $json['message'] = "用户名已存在";
        return false;
    }
	
	
    $member->Name = $name;
    $member->Gender = $gender;

    $member->Department = $department;

    $member->Major = $major;

    $member->Class = $class;

    $member->Dormitory = $dormitory;
    $member->Room = $room;

    $member->Qqnumber = $qqnumber;

    $member->Phonenumber = $phonenumber;

    $member->Password = $phonenumber;


    $member->Save();

	$keyvalue['mem_Studentid'] = $stu_id;
	$sql = $zbp->db->sql->Update('eh_member', $keyvalue, array(array('=', 'mem_Name', $name)));
    $zbp->db->Update($sql);
  
  	$keyvalue['mem_Openid'] = $openid;
	$sql = $zbp->db->sql->Update('eh_member', $keyvalue, array(array('=', 'mem_Name', $name)));
    $zbp->db->Update($sql);
  
    $result = (Object) array();
    $json['message'] = "恭喜你提交成功";
    $json['code'] = 100000;
    $json['result'] = $result;
    return true;
}


/**
 * 用户提交反馈
 */
function os_wxapp_one_feedback(&$json = []) {
    global $zbp;
    // 获取传入数据
    $content = GetVars("content", "GET");
    $contact = GetVars("contact", "GET");
    if (empty($content)) {
        $json['code'] = 2004001;
        $json['message'] = "内容不能为空";
        return false;
    }
    if (empty($contact)) {
        $json['code'] = 200402;
        $json['message'] = "没有联系方式";
        $json['param'] = $content;
        return false;
    }
  
    $keyvalue['feedb_Content'] = $content;
    $keyvalue['feedb_Contact'] = $contact;
    $sql = $zbp->db->sql->Insert('eh_wxapp_feedback', $keyvalue);
    $zbp->db->Insert($sql);
    $result = (Object) array();
  	$result->content = $content;
    $result->contact = $contact;
    $json['message'] = "恭喜提交成功";
    $json['code'] = 100000;
    $json['result'] = $result;
    return true;
}


/**
 * 用户提交维修
 */
function os_wxapp_one_repair(&$json = []) {
    global $zbp;
    // 获取传入数据
    $dormitory = GetVars("dormitory", "GET");
    $room = GetVars("room", "GET");
    $content = GetVars("content", "GET");
    $contact = GetVars("contact", "GET");
    if (empty($dormitory)) {
        $json['code'] = 2004001;
        $json['message'] = "楼栋号为空";
        $json['param'] = $dormitory;
        return false;
    }
    if (empty($room)) {
        $json['code'] = 200402;
        $json['message'] = "寝室号为空";
        $json['param'] = $room;
        return false;
    }
    if (empty($content)) {
        $json['code'] = 200403;
        $json['message'] = "报修内容为空";
        $json['param'] = $content;
        return false;
    }
    if (empty($contact)) {
        $json['code'] = 200404;
        $json['message'] = "联系方式为空";
        $json['param'] = $contact;
        return false;
    }
    $keyvalue['rpr_Dormitory'] = $dormitory;
    $keyvalue['rpr_Room'] = $room;
    $keyvalue['rpr_Content'] = $content;
    $keyvalue['rpr_Contact'] = $contact;
    $sql = $zbp->db->sql->Insert('eh_wxapp_repair', $keyvalue);
    $zbp->db->Insert($sql);

    $result = (Object) array();
    $result->dormitory = $dormitory;
    $result->room = $room;
    $result->content = $content;
    $result->contact = $contact;
    $json['message'] = "恭喜提交成功";
    $json['code'] = 100000;
    $json['result'] = $result;
    return true;
}

/**
 * 用户提交人脸信息
 */
function os_wxapp_one_facereg(&$json = []) {
    global $zbp;
    // 获取传入数据
    $dormitory = GetVars("dormitory", "GET");
    $room = GetVars("room", "GET");
    $content = GetVars("content", "GET");
    $contact = GetVars("contact", "GET");
    if (empty($dormitory)) {
        $json['code'] = 2004001;
        $json['message'] = "楼栋号不能为空";
        $json['param'] = $dormitory;
        return false;
    }
    if (empty($room)) {
        $json['code'] = 200402;
        $json['message'] = "寝室号不能为空";
        $json['param'] = $room;
        return false;
    }
    if (empty($content)) {
        $json['code'] = 200403;
        $json['message'] = "报修内容不能为空";
        $json['param'] = $content;
        return false;
    }
    if (empty($contact)) {
        $json['code'] = 200404;
        $json['message'] = "联系方式不能为空";
        $json['param'] = $contact;
        return false;
    }
    $keyvalue['rpr_Dormitory'] = $dormitory;
    $keyvalue['rpr_Room'] = $room;
    $keyvalue['rpr_Content'] = $content;
    $keyvalue['rpr_Contact'] = $contact;
    $sql = $zbp->db->sql->Insert('eh_wxapp_repair', $keyvalue);
    $zbp->db->Insert($sql);

    $result = (Object) array();
    $result->dormitory = $dormitory;
    $result->room = $room;
    $result->content = $content;
    $result->contact = $contact;
    $json['message'] = "恭喜你提交成功";
    $json['code'] = 100000;
    $json['result'] = $result;
    return true;
}


/**
 * 用户查询课表
 */
function os_wxapp_one_gettable(&$json = []) {
    global $zbp;
    // 获取传入数据
    $stu_id = GetVars("stu_id", "GET");
    $stu_password = GetVars("stu_password", "GET");
    if (empty($stu_id)) {
        $json['code'] = 2004001;
        $json['message'] = "学号不能为空";
        return false;
    }
    if (empty($stu_password)) {
        $json['code'] = 2004001;
        $json['message'] = "密码不能为空";
        return false;
    }
  	$keyvalue['mem_Studentid'] = $stu_id;
    $keyvalue['mem_Stupasswd'] = $stu_password;
	$sql = $zbp->db->sql->Update('eh_member', $keyvalue, array(array('=', 'mem_Studentid', $stu_id)));
    $zbp->db->Update($sql);

    $result = (Object) array();
    $result->stu_id = $stu_id;
    $json['message'] = "恭喜你提交成功";
    $json['code'] = 100000;
    $json['result'] = $result;
    return true;
}



/**
 * 发送post请求
 * @param string $url 请求地址
 * @param array $post_data post键值对数据
 * @return string
 */
function send_post($url, $post_data){
    $postdata = http_build_query($post_data);
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type:application/x-www-form-urlencoded',
            'content' => $postdata,
            'timeout' => 15 * 60 // 超时时间（单位:s）
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return $result;
}


