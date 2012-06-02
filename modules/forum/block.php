<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/


function menu_forum_posts($args){
    global $config, $objCore, $objModule;

    $limit = doArgs('limit', $objCore->config('rss', 'global_limit'), $args);

    $objCore->objTPL->set_filenames(array(
        $args['uniqueId'] => 'modules/forum/template/block_forum.tpl'
    ));

    //grab the last 50 threads, it makes sure we have something to show the user (hopefully)
    $query = $objCore->objSQL->getTable(
        'SELECT t.*
            FROM `$Pforum_threads` t
            LEFT JOIN `$Pforum_posts` p
                ON t.id = p.thread_id
            GROUP BY t.id
            ORDER BY t.timestamp DESC
            LIMIT 50'
    );

    //if empty show an error and quit
    if(is_empty($query)){
        $objCore->objTPL->assign_block_vars('error', array(
            'MESSAGE'    => langVar('L_NO_POSTS'),
        ));
        return $objCore->objTPL->get_html($args['uniqueId']);
    }

    $catAuth = $objModule->getVar('auth');

    //if we are using the forum when this block is show, WIN! if not init the forum
    if($objModule->name() != 'forum'){
        $objCore->autoLoadModule('forum', $objModule);
        $catAuth = $objModule->auth(AUTH_VIEW, AUTH_VIEW_ALL);
    }

    $count = 0; $return = '';

    $icons = $objCore->objPage->getVar('tplVars');
    $j = 0;
    foreach($query as $thread){
        if($j >= $limit || !$catAuth[$thread['cat_id']]['auth_view']){
            continue;
        }

        $icon_status = '_old';
        if(User::$IS_ONLINE){
            $tracking_topics = array(); $tracker = doArgs('forum_tracker', false, $_SESSION['user']);
            if(!is_empty($tracker)){ $tracking_threads = unserialize($tracker); }

            if(!is_empty($tracking_threads)){
                foreach($tracking_threads as $t){
                    if(!doArgs('read', false, $t)){
                       $icon_status = '_new';
                    }
                }
            }
        }
        switch($thread['mode']){
            case 1:     $ico = 'IMG_announcement'.$icon_status; break;
            case 2:     $ico = 'IMG_sticky'.$icon_status;         break;
            default:     $ico = 'IMG_posts'.$icon_status;         break;
        }
        if($thread['locked']==1){ $ico = 'IMG_locked'; }

         $objCore->objTPL->assign_block_vars('threadRow', array(
             'ID'        => 'fblock_'.$j,
             'CLASS'        => ($j%2==0 ? 'row_color2' : 'row_color1'),
             'ICON'        => $icons[$ico],
            'HREF'      => $objModule->generateThreadURL($thread).'?mode=last_page#top',

            'L_TITLE'    => langVar('L_TITLE'),
            'TR_TITLE'  => strip_tags(contentParse($thread['subject'], false, false)),
            'TITLE'      => contentParse(truncate($thread['subject'], 25), false, false),

            'L_AUTHOR'     => langVar('L_AUTHOR'),
            'AUTHOR'    => $objCore->objUser->profile($thread['last_uid']),

            'POSTED'    => $objCore->objTime->timer($thread['posted'],time(),  'wd'),

        ));
        $j++;
    }

    $return = $objCore->objTPL->get_html($args['uniqueId']);
    $objCore->objTPL->reset_block_vars('threadRow');
    return $return;
}

function menu_forum_users($args){
    global $objCore;

    $limit = doArgs('limit', 5, $args);

    $objCore->objTPL->set_filenames(array(
        $args['uniqueId'] => 'modules/forum/template/block_forum.tpl'
    ));


    $users = $objCore->objSQL->getTable(
        'SELECT u.id, COUNT(DISTINCT p.id) AS count
        FROM `$Pusers` u, `$Pforum_posts` p, `$Pforum_threads` t, `$Pforum_cats` c
            WHERE p.author = u.id AND p.thread_id = t.id AND t.cat_id = c.id AND c.postcounts = 1
        GROUP BY u.id
        ORDER BY count DESC
        LIMIT %d',
        array($limit)
    );

    if(!$users){
        $objCore->objTPL->assign_block_vars('error', array(
            'MESSAGE'    => langVar('L_ERROR'),
        ));
        return $objCore->objTPL->get_html($args['uniqueId']);
    }

    $opened = round((time()-$objCore->config('statistics', 'site_opened')) / 86400);
    $j = 0;
    foreach($users as $user){

         $objCore->objTPL->assign_block_vars('userRow', array(
             'ID'         => $objCore->objUser->getUserInfo($user['id'], 'id'),
             'USERNAME'     => $objCore->objUser->profile($user['id']),
             'COUNT'     => $user['count'],
             'PER_DAY'    => langVar('L_PER_DAY', round(sprintf('%.2f', $user['count'] / $opened), 0)),

             'CLASS'        => ($j%2==0 ? 'row_color2' : 'row_color1'),
         ));

         $j++;
    }

    //reset the block var so the data dosent creep into the other templates
    $return = $objCore->objTPL->get_html($args['uniqueId']);
    $objCore->objTPL->reset_block_vars('userRow');
    return $return;
}
?>