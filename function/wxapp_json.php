<?php

/**
 * 获取文章列表
 */
function os_wxapp_one_JSON_GetArticleList($num = 10, $cateid = null, $page = 1, $hasSubcate = false, $filter = [], $filter_art = []) {
    global $zbp;

    $data = (Object) array();
    $data->list = array();

    $w = array();
    $w[] = array('=', 'log_Status', '0');
    $w[] = array('=', 'log_Type', '0');

    if (count($filter) > 0) {
        $w = array_merge($w, $filter);
    }
    if (count($filter_art) > 0) {
        $w = array_merge($w, $filter_art);
    }

    if (isset($cateid)) {
        $cates = array();
        $cates[] = $cateid;
        $subcate = GetVars('subcate', 'GET');
        if ($hasSubcate) {
            if (isset($zbp->categories[$cateid])) {
                foreach ($zbp->categories[$cateid]->ChildrenCategories as $subcate) {
                    $cates[] = $subcate->ID;
                }
            }
        }
        $w[] = array('IN', 'log_CateID', $cates);
    }

    $pagebar = new Pagebar('');
    $pagebar->PageNow = $page;
    $pagebar->PageCount = $num;

    $limit = array(($page -1 ) * $pagebar->PageCount, $pagebar->PageCount);
    $option = array('pagebar' => $pagebar);

    $list = $zbp->GetArticleList('*', $w, array('log_PostTime' => 'DESC'), $limit, $option);
    foreach ($list as $item) {
        $data->list[] = os_wxapp_one_JSON_PostToJson($item);
    }

    $data->page = $page;
    $data->pages = $pagebar->PageAll;
    $data->pagenext = $pagebar->PageNext;

    return $data;
}

/**
 * os_wxapp_one_JSON_GetPost
 * 获取Post实例的可转换对象
 */
function os_wxapp_one_JSON_GetPost($id) {
    global $zbp;
    $id = (int) $id;
    $post = new Post;
    $post->LoadInfoById($id);
    if ($post->ID == $id && $id > 0 && $post->Status == 0) {
        $post->ViewNums += 1;
        $post->Save();
        $data = os_wxapp_one_JSON_PostToJson($post, true);
        if ($post->Type == 0) {
            // 相关文章
            $relatedList = $post->RelatedList;
            $relatedList = array_slice($relatedList, 0, 4);
            $data->RelatedList = array();
            foreach ($relatedList as $item) {
                $data->RelatedList[] = os_wxapp_one_JSON_PostToJson($item);
            }
        }
        return $data;
    } else {
        return null;
    }
}

/**
 * os_wxapp_one_JSON_GetCommentList
 * 获取评论列表
 */
function os_wxapp_one_JSON_GetCommentList($postid, $page) {
    global $zbp;

    $data = (Object) array();
    $data->list = array();

    $page = (int)$page > 0 ? (int)$page : 1;

    $w = array();
    $w[] = array('=', 'comm_LogID', $postid);
    $w[] = array('=', 'comm_IsChecking', '0');
    $order = array('comm_PostTime' => 'DESC');

    $pagebar = new Pagebar('');
    $pagebar->PageNow = $page;
    $pagebar->PageCount = 10;

    $limit = array(($page -1 ) * $pagebar->PageCount, $pagebar->PageCount);
    $option = array('pagebar' => $pagebar);

    $comments = $zbp->GetCommentList('*', $w, $order, $limit, $option);

    foreach ($comments as $item) {
        $data->list[] = os_wxapp_one_JSON_CommentToJson($item);
    }

    $data->page = $page;
    $data->pages = $pagebar->PageAll;
    $data->pagenext = $pagebar->PageNext;

    return $data;
}

/**
 * os_wxapp_one_JSON_PostToJson
 * Post转为Json
 */
function os_wxapp_one_JSON_PostToJson($item, $hasContent = false) {
    global $zbp;
    $data = json_decode($item->__toString());

    $data->Metas = (Object) array();
    $metas = $item->Meta;
    $metas = unserialize($metas);
    if ($metas) {
        $metas = (array) $metas;
        foreach ($metas as $k => $v) {
            $data->Metas->$k = $v;
            $data->Metas->$k = str_replace('{#ZC_BLOG_HOST#}', $zbp->host, $data->Metas->$k);
        }
    }

    if ($item->Type == 0) {
        $data->Thumb = os_wxapp_one_Event_GetArticleCover($item);
        $data->Category = os_wxapp_one_JSON_CateToJson($item->Category);
    }
    $data->Author = os_wxapp_one_JSON_UserToJson($item->Author);

    if (empty($hasContent)) {
        unset($data->Content);
    }
    unset($data->Meta);
    unset($data->Tag);
    unset($data->Template);

    return $data;
}

/**
 * os_wxapp_one_JSON_CateToJson
 * 转换分类对象
 */
function os_wxapp_one_JSON_CateToJson($item) {
    global $zbp;
    $data = json_decode($item->__toString());
    $data->Metas = (Object) array();
    $metas = $item->Meta;
    $metas = unserialize($metas);
    if ($metas) {
        $metas = (array) $metas;
        foreach ($metas as $k => $v) {
            $data->Metas->$k = $v;
            $data->Metas->$k = str_replace('{#ZC_BLOG_HOST#}', $zbp->host, $data->Metas->$k);
        }
    }
    unset($data->Template);
    unset($data->LogTemplate);
    unset($data->Meta);

    return $data;
}
/**
 * os_wxapp_one_JSON_UserToJson
 * 转换用户对象
 */
function os_wxapp_one_JSON_UserToJson($item, $hasPrivacy = false) {
    global $zbp;
    $data = json_decode($item->__toString());
    $data->Metas = (Object) array();
    $metas = $item->Meta;
    $metas = unserialize($metas);
    if ($metas) {
        $metas = (array) $metas;
        foreach ($metas as $k => $v) {
            $data->Metas->$k = $v;
            $data->Metas->$k = str_replace('{#ZC_BLOG_HOST#}', $zbp->host, $data->Metas->$k);
        }
    }
    unset($data->Meta);
    unset($data->Guid);
    unset($data->Password);
    unset($data->Template);
    if (!$hasPrivacy) {
        unset($data->IP);
        unset($data->Agent);
        unset($data->Email);
        unset($data->Name);
        unset($data->PostTime);
        unset($data->Uploads);
        // unset($data->Level);
        unset($data->Status);
    }
    // 处理昵称
    $data->Nickname = $item->StaticName;
    $data->StaticName = $item->StaticName;
    // 获取头像
    if ($item->ID == 0 && $item->Metas->os_wxapp_avatar) {
        $data->Avatar = $item->Metas->os_wxapp_avatar;
    } else {
        $data->Avatar = $item->Avatar;
    }

    return $data;
}

/**
 * os_wxapp_one_JSON_CommentToJson
 * 转换评论对象
 */
function os_wxapp_one_JSON_CommentToJson($item, $hasPrivacy = false) {
    global $zbp;
    $data = json_decode($item->__toString());
    $data->Metas = (Object) array();
    $metas = $item->Meta;
    $metas = unserialize($metas);
    if ($metas) {
        $metas = (array) $metas;
        foreach ($metas as $k => $v) {
            $data->Metas->$k = $v;
            $data->Metas->$k = str_replace('{#ZC_BLOG_HOST#}', $zbp->host, $data->Metas->$k);
        }
    }
    unset($data->Meta);
    if (!$hasPrivacy) {
        unset($data->IP);
        unset($data->Agent);
        unset($data->Email);
    }

    $data->Author = os_wxapp_one_JSON_UserToJson($item->Author);
    $data->Post = os_wxapp_one_JSON_PostToJson($item->Post);

    // 获取父级评论
    if ($data->ParentID > 0) {
        $parentItem = $zbp->GetCommentByID($data->ParentID);
        $data->Parents = os_wxapp_one_JSON_CommentToJson($parentItem, $hasPrivacy);
    }

    return $data;
}

/**
 * 转换swiper的JSON
 */
function os_wxapp_one_JSON_SwiperToJson($item) {
    $data = json_decode($item->__toString());
    $data->Metas = (Object) array();
    $metas = $item->Meta;
    $metas = unserialize($metas);
    if ($metas) {
        $metas = (array) $metas;
        foreach ($metas as $k => $v) {
            $data->Metas->$k = $v;
            $data->Metas->$k = str_replace('{#ZC_BLOG_HOST#}', $zbp->host, $data->Metas->$k);
        }
    }
    unset($data->Meta);

    switch ($item->Type) {
        case 'normal':
            $data->route = null;
        break;
        case 'article':
            $data->route = "/pages/article/index?id=".$item->Related;
        break;
        case 'page':
            $data->route = "/pages/page/index?id=".$item->Related;
        break;
        case 'search':
            $data->route = "/pages/search/index?word=".$item->Related;
        break;
    }

    return $data;
}
