<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

class forum extends Module{

    public function doAction($action){
        $this->objPage->setMenu('forum');
        $this->objPage->addJSFile('/'.root().'modules/forum/scripts/forum.js');
        $this->objPage->addCSSFile('/'.root().'modules/forum/styles/forum.css');
        $vars = $this->objPage->getVar('tplVars');

        $this->objTPL->assign_vars(array(
            'I_NO_POSTS'        => $vars['IMG_posts_old'],          'L_NO_POSTS'        => langVar('I_NO_POSTS'),
            'I_NEW_POSTS'       => $vars['IMG_posts_new'],          'L_NEW_POSTS'       => langVar('I_NEW_POSTS'),
            'I_LOCKED'          => $vars['IMG_locked'],             'L_LOCKED'          => langVar('I_LOCKED'),
            'I_ANNOUNCEMENT'    => $vars['IMG_announcement_old'],   'L_ANNOUNCEMENT'    => langVar('I_ANNOUNCEMENT'),
            'I_STICKY'          => $vars['IMG_sticky_old'],         'L_STICKY'          => langVar('I_STICKY'),
        ));


        //reset the forum tracker
        if(User::$IS_ONLINE){
            $this->forumTrackerInit();
        }
 
        //view thread
        if(preg_match('_^thread/([a-zA-Z0-9-]*)\-([0-9]*)_i', $action, $threadId)){
            $action = 'thread';
            $this->rowstart = doArgs('page', 0, $_GET);
            if(User::$IS_ONLINE){
                switch($_GET['mode']){
                    case 'lock':
                    case 'unlock':
                        $this->lock = $this->lockThread($threadId[2], ($_GET['mode']=='lock' ? true : false));
                    break;

                    case 'watch':
                    case 'unwatch':
                        $this->watchThread($threadId[2], ($_GET['mode']=='watch' ? true : false));
                    break;

                    case 'edit':        $action = 'edit';        break;
                    case 'reply':       $action = 'reply';       break;
                    case 'qreply':      $action = 'qreply';      break;
                    case 'rm':          $action = 'remove';      break;
                    case 'move':        $action = 'move';        break;
                }
            }
        }

        //post thread
        if(preg_match('_([a-zA-Z0-9-]*)-([0-9]*)/post($|/)_i', $action, $postId)){
            $action = 'post';
        }

            //view category
            if(preg_match('_^([a-z0-9-]*)-([0-9]*)/page([0-9]*)_i', $action, $boardId) ||
                preg_match('_^([a-z0-9-]*)-([0-9]*)_i', $action, $boardId)){
                    $action = 'cat';
                    $this->rowstart = isset($boardId[3]) ? $boardId[3] : 0;
            }

        if($action=='index' || is_empty($action)){
            $action = 'index';
        }

            //preview stuff
            if(preg_match('_preview_i', $action)){
                $action = 'previewPost';
            }

        //ajaxy stuff
        if(preg_match('_ajax[$|/]([a-z0-9-]*)_i', $action, $ajaxInfo)){
            $action = 'ajax';
        }

        /**
         * we will 'add' a breadcrum for this module, the modules
         * already sets the start of the pagecrumbs so its just a
         * case of adding to it
         */
        $this->objPage->addPagecrumb(array(
            array('url' => '/'.root().'modules/forum/', 'name' => langVar('B_FORUM')),
        ));

        switch(strtolower($action)){
            case 'index':
                $this->showIndex();
            break;

            case 'cat':
                $this->viewCat($boardId[2]);
            break;

            case 'thread':
                $this->viewThread($threadId[2]);
            break;

            case 'post':
                $this->postThread($postId[2]);
            break;

            case 'reply':
                $this->postReply($threadId[2]);
            break;

            case 'qreply':
                $this->postQuickReply($threadId[2]);
            break;

            case 'edit':
                $this->editPost(isset($_GET['postid']) && is_number($_GET['postid']) ? $_GET['postid'] : 0);
            break;

            case 'remove':
                $post = doArgs('postid', false, $_GET, 'is_number');
                if($post!==false){
                    $this->delReply($post);
                }else{
                    $this->delThread($threadId[2]);
                }
            break;

            case 'previewpost':
                $this->preview();
            break;

            case 'ajax':
                $this->doAjax($ajaxInfo[1]);
            break;

            default:
            case 404:
                $this->throwHTTP(404);
            break;
        }
    }

    /**
     * Function to output content directly to the index.php page
     *
     * @version 2.0
     * @since   1.0.0
     * @autor   xLink
     */
    public function showMain(){
        $this->objTPL->set_filenames(array(
            'body' => 'modules/forum/template/forum_newsPost.tpl'
        ));

            $newsPosts = $this->objSQL->getTable(
                'SELECT t.*, p.timestamp as last_timestamp, p.post as post, count(DISTINCT p.id) as replies
                    FROM `$Pforum_threads` t
                    LEFT JOIN `$Pforum_posts` p
                        ON p.id = t.first_post_id
                    WHERE t.cat_id ="%d"
                    GROUP BY t.id
                    ORDER BY t.timestamp DESC
                    LIMIT 4',
                array($this->config('forum', 'news_category'))
            );

            if(!$newsPosts){
                $this->objTPL->assign_block_vars('error', array(
                    'ERROR' => msg('INFO', langVar('L_NO_NEWS'), 'return')
                ));
            }else{
                $count = 0;
                foreach($newsPosts as $thread){
                    $title = secureMe(doArgs('subject', null, $thread));
                    $author = $this->objUser->profile($thread['author']);
                    $threadURL = $this->generateThreadURL($thread);

                    $this->objTPL->assign_block_vars('thread', array(
                        'ID'        => $thread['id'],
                        'ROW'       => $count++%2==1 ? 'row_color2' : 'row_color1',
                        'POSTED'    => langVar('L_NEWS_POSTED_ON', $author, $this->objTime->mk_time($thread['posted'])),

                        'AVATAR'    => $this->objUser->parseAvatar($thread['author'], 64),
                        'AUTHOR'    => $author,
                        'TITLE'     => '<a href="'.$threadURL.'">'.$title.'</a>',
                        'POST'      => contentParse($thread['post']),
                        'COMMENTS'  => '<a href="'.$threadURL.'.html">'.langVar('L_COMMENTS', ($thread['replies']-1)).'</a>',
                    ));
                }
            }
        $this->objTPL->parse('body', false);
    }

    /**
     * Returns sql about a specific forum
     *
     * @version 1.0
     * @since   1.0.0
     * @autor   xLink
     *
     * @param   int     $id
     *
     * @return  array
     */
    public function getForumInfo($id=0, $subs=false){

        //see if we have the cache in place
        if(is_empty($this->forum)){
            $this->forum = array();

            //grab the categories and some extra details
            $cats = $this->objSQL->getTable(
                'SELECT f.*, u.id as uid,
                    t.id as tid, t.subject as thread_name, t.timestamp as thread_posted, p.author as last_author, p.timestamp as last_posted
                FROM `$Pforum_cats` as f
                LEFT JOIN `$Pforum_posts` as p
                    ON p.id = f.last_post_id
                LEFT JOIN `$Pforum_threads` as t
                    ON p.thread_id = t.id
                LEFT JOIN `$Pusers` as u
                    ON u.id = p.author
                ORDER BY f.id, f.order ASC'
            );
                if(!$cats){ hmsgDie('FAIL', 'Error: No forum information avalible at this time.'); }
                $this->objSQL->freeResult($cats);

            //shove each forum into a cache var so we dont have to keep querying for em
            foreach($cats as $cat){
                $this->forum[$cat['id']] = $cat;
            }

            //get a list of permissions for the user
            $this->auth = $this->auth(AUTH_ALL, AUTH_LIST_ALL, $cats);

            $counts = $this->objSQL->getTable(
                'SELECT c.id, c.postcounts, COUNT(DISTINCT t.id) AS thread_count, COUNT(DISTINCT p.id) AS post_count
                FROM `$Pforum_cats` c
                LEFT JOIN `$Pforum_threads` t
                    ON t.cat_id = c.id
                LEFT JOIN `$Pforum_posts` p
                    ON t.id = p.thread_id

                GROUP BY c.id'
            );
                if(!$counts){ hmsgDie('FAIL', 'Error: No forum information avalible at this time.'); }
                $this->objSQL->freeResult($counts);

            //add the counts to them
            foreach($counts as $count){
                if(!$count['postcounts']){ continue; }
                $this->forum[$count['id']]['thread_count'] = $count['thread_count'];
                $this->forum[$count['id']]['post_count'] = $count['post_count'];
            }
        }


        //are we wanting the sub categories only?
        if($subs){
            //check if we have sub cats..
            $cats = array(); $forums = $this->forum;
            foreach($forums as $cat){
                if($this->auth[$cat['id']]['auth_view'] && $cat['parent_id'] == $id){
                    $cats[$cat['id']] = $cat;
                }
            }
            return $cats;

        //return specific category or all of em
        }else{
            //if it was a asterix, give it all to em
            if($id == '*'){ return $this->forum; }

            //if they gave us a number for a ID give them the specific forum ID back
            if(is_number($id) && isset($this->forum[$id])){
                return array($this->forum[$id]);
            }
        }

        //otherwise we cant accommodate them so return false
        return false;
    }

    /**
     * Ouputs categories into a template and returns the contents
     *
     * @version 1.0
     * @since   1.0.0
     * @autor   xLink
     *
     * @param   array   $categories     Array of categories to output -parents only-
     * @param   bool    $index          Determine if we are on the index, for sortable cats
     * @param   string  $title          The title to give to the block
     *
     * @return  string
     */
    public function outputCats($categories, $index=false, $title=null){
        $vars = $this->objPage->getVar('tplVars');
        $_row_color1 = $vars['row_color1']; $_row_color2 = $vars['row_color2'];  $_row_highlight = $vars['row_highlight'];

        $this->objTPL->set_filenames(array(
            'categories' => 'modules/forum/template/forum_categoryOutput.tpl',
        ));

    //
    //--Moderator Setup
    //
        $forum_moderators = array();

        // Obtain list of moderators(users only) of each forum
        $query = $this->objSQL->getTable(
            'SELECT aa.cat_id, u.id, u.username
            FROM `$Pforum_auth` aa, `$Pgroup_subs` ug, `$Pgroups` g, `$Pusers` u
            WHERE aa.auth_mod = 1
                    AND g.single_user_group = 1
                    AND ug.gid = aa.group_id
                    AND g.id = aa.group_id
                    AND u.id = ug.uid
            GROUP BY u.id, u.username, aa.cat_id
            ORDER BY aa.cat_id, u.id'
        );
        if($query===false){ hmsgDie('FAIL', 'Could not query forum moderator information'); }
            $this->objSQL->freeResult($query);

        if(count($query)){
            foreach($query as $row){
                $forum_moderators[$row['cat_id']][] = $this->objUser->profile($row['id']);
            }
        }
        //clean up a bit
        unset($query);
    //
    //--Moderator Setup
    //

        $currId = 0; //set a var for the current id
        $row_count = 0; //setup a row counter..
        //loop through the categories
        foreach($categories as $cat){
            $children = $this->getForumInfo($cat['id'], true);

            //do we need to show the header again? (no subs for a main cat will render no header either)
            if($cat['id'] != $currId && $children){
                //if we have no data for it, then just set it to show
                if($cat['_display']===null){ $cat['_display'] = 1; }

                //just the cat headers
                $this->objTPL->assign_block_vars('forum', array(
                    'ROW'           => $row_color,

                    'ID'            => $cat['id'],
                    'CAT'           => (!is_empty($title) ? $title : $cat['title']),
                    'THREADS'       => langVar('L_THREADS'),
                    'POSTS'         => langVar('L_POSTS'),
                    'LASTPOST'      => langVar('L_LASTPOST'),

                    /* Sortable Cats */
                    'EXPAND'        => (User::$IS_ONLINE && $index
                                            ? ($cat['_display']==1 ? $vars['IMG_retract'] : $vars['IMG_expand'])
                                            : '/'.root().'images/spacer.gif'),
                    'DISPLAY'       => (User::$IS_ONLINE && $index ? ($cat['_display']==1 ? null : 'display:none;') : null),
                    'MODE'          => (User::$IS_ONLINE && $index ? ($cat['_display']==1 ? '1' : '0') : '1'),
                    'CLASS'         => (User::$IS_ONLINE && $index ? ' cat_handle' : ''),
                    /* Sortable Cats */
                ));

                if(User::$IS_ONLINE && $index){ $this->objTPL->assign_block_vars('forum.expand', array()); }

                //reassign the current id so we know where we are
                $currId = $cat['id'];
            }

            //make sure we have some subcategories before we continue in here..
            if(!is_empty($children)){
                foreach($children as $child){
                    //see if we can view the cat first
                    if(!$this->auth[$child['id']]['auth_view']){ continue; }
                    $grandChildren = $this->getForumInfo($child['id'], true);

                    $row_color = $row_count%2==1 ? 'row_color1' : 'row_color2';
                    $icon_status = '_old';
                    if(User::$IS_ONLINE){
                        $tracking_topics = array(); $tracker = doArgs('forum_tracker', false, $_SESSION['user']);
                        if(!is_empty($tracker)){ $tracking_threads = unserialize($tracker); }

                        if(!is_empty($tracking_threads)){
                            foreach($tracking_threads as $t){
                                if($t['cat_id'] == $child['id'] && $t['read']===false){
                                   $icon_status = '_new';
                                }
                            }
                        }
                    }
                    if($thread['locked']==1){ $ico = 'IMG_locked'; }

                    switch($post['mode']){
                        case 1:     $ico = 'IMG_announcement'.$icon_status; break;
                        case 2:     $ico = 'IMG_sticky'.$icon_status;       break;
                        default:    $ico = 'IMG_posts'.$icon_status;        break;
                    }

                    //sort though the last post stuff :D
                    $lastThread = $this->modCat($child, 'last_post');

                    $last_title  = doArgs('thread_name', null, $lastThread, function($text){
                        return truncate(secureMe($text), 25);
                    });

                    $last_author = !is_empty($lastThread['last_author']) ? 'by '.$this->objUser->profile($lastThread['last_author']) : langVar('L_NO_POST');
                    $last_post   = '/'.root().Page::$THEME_ROOT.'buttons/goto_reply.gif';

                    $this->objTPL->assign_block_vars('forum.row', array(
                        'ROWSPAN'       => !is_empty($grandChildren) ? 2 : 1,

                        'ID'            => $child['id'],
                        'CAT_ICO'       => $vars[$ico],
                        'URL'           => '/'.root().'modules/forum/'.seo($child['title']).'-'.$child['id'].'/',
                        'ROW'           => $row_color,
                        'CAT'           => secureMe($child['title']),
                        'DESC'          => (isset($child['desc']) && !is_empty($child['desc'])) ? contentParse($child['desc']) : '',

                        'L_TCOUNT'      => langVar('L_THREADS'),
                        'T_PCOUNT'      => langVar('L_POSTS'),
                        'T_COUNT'       => $this->modCat($child, 'thread'),
                        'P_COUNT'       => $this->modCat($child, 'post'),

                        'LP_AUTHOR'     => $last_author,
                        'LP_URL'        => !is_empty($last_title)
                                                ? '/'.root().'modules/forum/thread/'.seo($lastThread['thread_name']).'-'.$lastThread['last_post'].'.html'
                                                : null,
                        'LP_TITLE'      => !is_empty($last_title) ? secureMe($last_title) : null,
                        'LP_TIME'       => !is_empty($last_title) ? $this->objTime->mk_time($lastThread['post_time']) : null,
                        'LP_REPLY_URL'  => !is_empty($last_title)
                                                ? '/'.root().'modules/forum/thread/'.seo($lastThread['thread_name']).'-'.$lastThread['last_post'].'.html?mode=last_page'
                                                : null,
                        'LP_REPLY_IMG'  => !is_empty($last_title) ? '<img src="'.$last_post.'" alt="" />' : null,

                        'L_MODS'        => is_array($forum_moderators[$child['id']]) ? langVar('MODS') : null,
                        'C_MODS'        => is_array($forum_moderators[$child['id']]) ? implode(', ', $forum_moderators[$child['id']]) : null,
                    ));

                    if($child['postcounts']){
                        //show the postcounts
                        $this->objTPL->assign_block_vars('forum.row.counts', array());
                    }

                    if(!is_empty($grandChildren)){
                        //assign this so we can see the subs
                        $this->objTPL->assign_block_vars('forum.row.subs', array(
                            'ID'    => $child['id'],
                            'ROW'   => $row_color,
                        ));

                        foreach($grandChildren as $child){
                            if(!$this->auth[$child['id']]['auth_view']){ continue; }
                            $ico = 'IMG_subForum_old';
                            if(User::$IS_ONLINE && $tracking_threads){
                                if(!is_empty($tracking_threads)){
                                    foreach($tracking_threads as $thread){
                                        if($thread['cat_id'] == $child['id'] && $thread['read']===false){
                                            $ico = 'IMG_subForum_new';
                                        }
                                    }
                                }
                            }

                            $this->objTPL->assign_block_vars('forum.row.subs.cats', array(
                                'URL'   => '/'.root().'modules/forum/'.seo($child['title']).'-'.$child['id'].'/',
                                'ID'    => $child['id'],
                                'NAME'  => secureMe($child['title']),
                                'IMG'   => $vars[$ico],
                            ));
                        }
                    }
                    $row_count++;
                }
           }
        }

        $return = $this->objTPL->get_html('categories');
        $this->objTPL->reset_block_vars('forum');
        return $return;
    }


    /**
     * Shows outputs the first level of forums with sub forums and threads
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     */
    public function showIndex(){
        $vars = $this->objPage->getVar('tplVars');
        $_row_color1 = $vars['row_color1']; $_row_color2 = $vars['row_color2'];  $_row_highlight = $vars['row_highlight'];
        $this->objPage->setTitle('Forum');

        $this->objTPL->set_filenames(array(
            'body' => 'modules/forum/template/forum_index.tpl',
        ));

        //grab the categories and some extra details
        $mainCats = $this->getForumInfo('*');

        //and then find out which main cats the user can see
        $categories = array();
        foreach($mainCats as $cat){
            if($this->auth[$cat['id']]['auth_view'] && $cat['parent_id']==0){ $categories[] = $cat; }
        }


        /* Sortable Cats */
        $reOrder = false;
        if(User::$IS_ONLINE && $this->config('forum', 'sortable_categories')){
            $reOrder = (!is_empty($this->objUser->grab('forum_cat_order')) ? unserialize($this->objUser->grab('forum_cat_order')) : false);

            //we have an active order that we can use to reorder the forum cats
            if($reOrder){
                $newOrder = array();
                //$reOrder as $id => $display
                foreach($reOrder as $k => $v){
                    foreach($categories as $f){
                        if($f['id']==$k){
                            $f['_display'] = $v;
                            $newOrder[$k] = $f; //reorder the array with the new cat order
                            break;
                        }
                    }
                }
                //and then make sure we havent missed any
                foreach($categories as $f){
                    if(array_searchRecursive($f['id'], $newOrder)===false){
                        $newOrder[] = $f;
                    }
                }
                $categories = $newOrder; //assign the new order to $cats
            }
        }
        /* Sortable Cats */

        $this->objTPL->assign_var('CATEGORIES', $this->outputCats($categories, true));

    //
    //-- Stats
    //
        //figure out which users have been active in the last 24 hours
        $user24 = $this->objSQL->getTable(
            'SELECT id, last_active FROM `$Pusers`
                WHERE last_active >= %d
                ORDER BY last_active DESC',
            array($this->objTime->mod_time(time(), 0, 0, 24, 'MINUS'))
        );

        $users24 = array();
        if(!is_empty($user24)){
            foreach($user24 as $u){ $users24[] = $this->objUser->profile($u['id']); }
        }else{
            $users24 = langVar('L_NOONE24');
        }

        //grab the groups and output em into a key
        global $config;

        $key = '';
        if($config['groups']){

            $groups = array();
            foreach($config['groups'] as $group){
                if($group['type'] == GROUP_HIDDEN){ continue; }
                if($group['single_user_group']){ continue; }

                $groups[] = $group;
            }

            $add = ' | '; $group_count = count($groups); $counter = 1;
              foreach($groups as $group){
                if($group_count == ($counter)){ $add = ''; }
                $key .= '<span style="color: '.$group['color'].'" class="username '.strtolower($group['name']).'" title="'.$group['description'].'">'.$group['name'].'</span>'.$add;

                $counter++;
            }
        }

        //grab the currently online users
        $userO = $this->objSQL->getTable(
            'SELECT * FROM `$Ponline` WHERE timestamp >= %s',
            array($this->objTime->mod_time(time(), 0, 20, 0, 'MINUS'))
        );

        $usersO = 0; $guestsO = 0; $users = array();
        if(count($userO)){
            foreach($userO as $u){
                if($u['uid']==0){
                    $guestsO++;
                }else{
                    $usersO++; $users[] = $this->objUser->profile($u['uid']);
                }
            }
        }

        $boarddays = (time() - $this->config('statistics', 'site_opened')) / 86400;
        $total_topics   = $this->objSQL->getInfo('forum_threads', false);
        $total_posts    = $this->objSQL->getInfo('forum_posts', false) + $total_topics;
        $total_users    = $this->objSQL->getInfo('users', false);
        $last_user      = $this->objSQL->getLine('SELECT id FROM `$Pusers` WHERE active = 1 ORDER BY id DESC');

        $this->objTPL->assign_block_vars('stats', array(
            'L_STATS'           => langVar('L_STATS'),

            'L_THREADS'         => langVar('L_TOT_THREADS'),
            'C_THREADS'         => $total_topics,

            'L_POSTS'           => langVar('L_TOT_POSTS'),
            'C_POSTS'           => $total_posts,

            'L_USERS'           => langVar('L_TOT_MEMBERS'),
            'C_USERS'           => $total_users,

            'L_NEWUSER'         => langVar('L_NEW_MEMBER'),
            'C_NEWUSER'         => $this->objUser->profile($last_user['id']),

            'L_TOTAL_USERS'     => langVar('L_USERSONOFF', $usersO, $guestsO, implode(', ', $users)),
            'USER24'            => is_array($users24) && !is_empty($users24)
                                        ? langVar('L_USERSONLINE24', count($users24), implode(', ', $users24))
                                        : langVar('L_USERSONLINE24', 0, $users24),

            'LEGEND'            => langVar('L_LEGEND', $key),
        ));

        $this->objTPL->parse('body', false);
    }


    /**
     * Outputs threads for a category, and sub categories if necessary
     *
     * @version 1.3
     * @since   0.8.0
     * @author  xLink
     *
     * @param   int     $id ID of the category to start from
     */
    public function viewCat($id){
        //init some generally used vars
        $vars = $this->objPage->getVar('tplVars');
        $_row_color1 = $vars['row_color1']; $_row_color2 = $vars['row_color2'];  $_row_highlight = $vars['row_highlight'];

        $this->objTPL->set_filenames(array(
            'body' => 'modules/forum/template/forum_category.tpl'
        ));

            //grab this forums info and auth
            $cat = $this->getForumInfo($id);
            $catAuth = $this->auth[$id];

            //if forum dosent exist or user dosent have perms...BOOM!
            if(is_empty($cat) || !$catAuth['auth_read'] || !$catAuth['auth_view']){
                $this->objPage->setTitle(langVar('B_FORUM').' > '.langVar('L_CAT_NF'));

                //this msg depends on if they wer owned due to permissions or the forum actually dosent exist :O
                $msg = (!$catAuth['auth_view'] ? langVar('L_NO_ID', $id) : langVar('F_PERMS', $catAuth['auth_read_type']));
                hmsgDie('INFO', $msg);
                return;
            }else{
                //else check to see if we have sub cats, and output
                $this->objTPL->assign_var('CATEGORY', $this->outputCats($cat, false, langVar('L_SUBCATS')));
            }

            //grab all the parents and granparents for this forum
            $this->getSubCrumbs($id);
            $this->objPage->setTitle(langVar('B_FORUM').' > '.secureMe($cat['title']));

            //reset $cat so we dont get confuzzled and check to see if we arnt a root forum
            $cat = $cat[0];
            if($cat['parent_id'] == 0){
                //no posts are allowed in root forums
                $this->objTPL->parse('body', false);
                return;
            }
        //
        //--Begin Thread Output
        //
            $limit = 20;
            //init pagination
            $objPagination = new pagination('page', $limit, $this->objSQL->getInfo('forum_threads', array('`cat_id`=%s AND `mode`=0', $id)));

            //grab the threads with the current pagination limit
            $threads = $this->objSQL->getTable(
                'SELECT t.*, p.timestamp as last_timestamp, count(DISTINCT p.id) as replies
                    FROM `$Pforum_threads` t
                    LEFT JOIN `$Pforum_posts` p
                        ON t.id = p.thread_id
                    WHERE t.cat_id = %d
                        AND t.mode = 0

                    GROUP BY t.id
                    ORDER BY p.timestamp DESC
                    LIMIT %s',
                array($id, $objPagination->getSqlLimit())
            );

            //grab the 'special' threads, announcements, stickies etc
            $special = $this->objSQL->getTable(
                'SELECT t.*, p.timestamp as last_timestamp, count(DISTINCT p.id) as replies
                    FROM `$Pforum_threads` t
                    LEFT JOIN `$Pforum_posts` p
                        ON t.id = p.thread_id
                    WHERE t.cat_id = %d
                        AND t.mode <> 0

                    GROUP BY t.id
                    ORDER BY p.timestamp DESC',
                array($id)
            );

            //output the header
            $this->objTPL->assign_block_vars('threads', array(
                'CAT'               => secureMe($cat['title']),
                'L_THREAD_TITLE'    => langVar('L_THREAD_TITLE'),
                'L_AUTHOR'          => langVar('L_AUTHOR'),
                'L_VIEWS'           => langVar('L_VIEWS'),
                'L_REPLIES'         => langVar('L_POSTS'),
                'L_LASTPOST'        => langVar('L_LASTPOST'),
            ));

            //if user has the permission to post in here..
            if($catAuth['auth_post']){
                $this->objTPL->assign_block_vars('threads.post', array(
                    'URL'     => '/'.root().'modules/forum/'.seo($cat['title']).'-'.$id.'/post/',
                    'TEXT'    => 'New Topic',
                    'IMG'     => $vars['FIMG_new_post'],
                ));
            }
            $this->objTPL->assign_var('PAGINATION', $objPagination->getPagination());
            if(is_array($special)){ $threads = array_merge($special, $threads); }

            if(is_empty($threads)){
                $this->objTPL->assign_block_vars('threads.error', array(
                    'ERROR' => langVar('L_NO_THREADS'),
                ));
            }else{
                //loop through the threads
                $count = 0;
                foreach($threads as $thread){

                    //figure out which icon we should be showing
                    $icon_status = '_old';
                    if(User::$IS_ONLINE){
                        $tracking_topics = array(); $tracker = doArgs('forum_tracker', false, $_SESSION['user']);
                        if(!is_empty($tracker)){ $tracking_threads = unserialize($tracker); }

                        if(!is_empty($tracking_threads)){
                            foreach($tracking_threads as $t){
                                if($t['id'] == $thread['id'] && !$t['read']){
                                   $icon_status = '_new';
                                }
                            }
                        }
                    }

                    //title and post status
                    $title = secureMe($thread['subject']);
                    if(is_empty($title)){ $title = 'No Thread Title'; }
                    switch($thread['mode']){
                        case 1:
                            $title = langVar('L_ANNOUNCEMENT', $title);
                            $ico = 'IMG_announcement'.$icon_status;
                        break;
                        case 2:
                            $title = langVar('L_STICKY', $title);
                            $ico = 'IMG_sticky'.$icon_status;
                        break;
                        default:
                            $title = $title;
                            $ico = 'IMG_posts'.$icon_status;
                        break;
                    }
                    if($thread['locked']==1){ $ico = 'IMG_locked'; }

                    //some vars
                    $threadUrl = $this->generateThreadURL($thread);
                    $lp_icon = '/'.root().Page::$THEME_ROOT.'buttons/goto_reply.gif';

                    //output the thread info to the template
                    $this->objTPL->assign_block_vars('threads.row', array(
                        'ID'            => 'thread_'.$thread['id'],
                        'ICON'          => $vars[$ico],
                        'URL'           => $threadUrl,
                        'CLASS'         => $count%2 ? 'row_color1' : 'row_color2',

                        'TITLE'         => $title,
                        'AUTHOR'        => $this->objUser->profile($thread['author']),
                        'VIEWS'         => $thread['views'],
                        'REPLIES'       => $thread['replies'],

                        'LP_AUTHOR'     => $thread['replies'] ? $this->objUser->profile($thread['last_uid']) : null,
                        'LP_URL'        => $thread['replies'] ? $this->generateThreadURL($thread).'?mode=last_page' : null,
                        'LP_TIME'       => $thread['replies'] ? $this->objTime->mk_time($thread['last_timestamp']) : langVar('L_NO_REPLYS'),
                        'LP_REPLY'      => $thread['replies'] ? '<a href="'.$threadUrl.'?mode=last_page"><img src="'.$lp_icon.'" /></a>' : '',
                    ));

                    if($thread['replies'] > 10){
                        //set a simple pagination up for this thread
                        $threadPag = new pagination('page', 10, $thread['replies']);
                        $this->objTPL->assign_block_vars('threads.row.pagination', array(
                            'SHOW' => $threadPag->getPagination(false, 'mini', $threadUrl)
                        ));
                    }

                    $count++;
                }
            }

        $this->objTPL->parse('body', false);
    }

    /**
     * Allows a user to view a thread
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int         $id
     */
    public function viewThread($id){
        $vars = $this->objPage->getVar('tplVars');
        $this->objTPL->set_filenames(array(
            'body' => 'modules/forum/template/forum_thread.tpl',
        ));

        //grab the thread
        $thread = $this->objSQL->getLine(
            'SELECT t.*, COUNT(DISTINCT p.id) as posts
                FROM `$Pforum_threads` t
                LEFT JOIN `$Pforum_posts` p
                    ON p.thread_id = t.id
                WHERE t.id = %d',
            array($id)
        );
            //make sure it exists
            if(is_empty($thread['id'])){ $this->throwHTTP(404); return; }

        //grab the cat
        $cat = $this->getForumInfo($thread['cat_id']);
        $cat = $cat[0];

        //grab the auth and make sure they /can/ see it
        $threadAuth = $this->auth[$thread['cat_id']];
        if(!$threadAuth['auth_view'] || !$threadAuth['auth_read']){
            $this->objPage->setTitle(langVar('B_FORUM'). ' > '.langVar('P_PERMISSION_DENIED'));
            hmsgDie('INFO', langVar('L_AUTH_MSG', $threadAuth['auth_read_type']));
            return;
        }

        //sort out the breadcrumbs & page title
        $threadTitle = secureMe($thread['subject']);
        $threadUrl = $this->generateThreadURL($thread);

        $page_name = array(langVar('B_FORUM'), $cat['title'], (!is_empty($threadTitle) ? $threadTitle : langVar('F_VIEWF')));
        $this->objPage->setTitle(implode(' > ', $page_name));

        $this->getSubCrumbs($thread['cat_id']);
        $this->objPage->addPagecrumb(array(
            array('url' => $threadUrl, 'name' => $threadTitle),
        ));

        //update views
        if(!isset($_SESSION['site']['forum']['view'][$thread['tid']])){
            $this->objSQL->query('UPDATE `$Pforum_threads` SET views = (views+1) WHERE id = %d LIMIT 1', array($id));
            $_SESSION['site']['forum']['view'][$thread['tid']] = 1;
        }

        //if the user is online
        if(User::$IS_ONLINE){
            //do thread tracker part of the tour
                $tracker = doArgs('forum_tracker', false, $_SESSION['user']);
                $tracking_threads = array();
                if(!is_empty($tracker)){
                    $tracking_threads = unserialize($tracker);
                }

                //find the thread row in the array or create a new one
                if(!is_empty($tracking_threads)){
                    foreach($tracking_threads as $k => $v){
                        if($tracking_threads[$k]['id'] == $id){
                            $tracking_threads[$k][$id]['read'] = true;
                            $tracking_threads[$k][$id]['last_poster'] = time();
                        }
                    }
                }else{
                    $tracking_threads[$id]['read'] = true;
                    $tracking_threads[$id]['last_poster'] = time();
                }

                //now update the user row
                unset($update);
                $_SESSION['user']['forum_tracker'] = $update['forum_tracker'] = serialize($tracking_threads);
                $this->objUser->updateUserSettings($this->objUser->grab('id'), $update);
                unset($update);

            //update the users watch status
            $this->objSQL->updateRow('forum_watch', array('seen'=>1), array('user_id ="%d" AND thread_id ="%d"', $this->objUser->grab('id'), $id));

            // && read notification if needed
            $this->objNotify->clearNotifications($id, true);
        }

        //setup a new pagination obj
        $objPagination = new pagination('page', 10, $thread['posts']);

        //see if the user wants us to jump to the last page
        if(doArgs('mode', false, $_GET) == 'last_page'){ $objPagination->goLastPage(); }

        //check for guest restrictions
        $limit = $objPagination->getSqlLimit();
        if(!User::$IS_ONLINE && $this->config('forum', 'guest_restriction')){
            $this->objTPL->assign_block_vars('error', array(
                'ERROR' => langVar('L_VIEW_GUEST'),
            ));

            $limit = '1;';
        }

        //grab the thread posts
        $posts = $this->objSQL->getTable(
            'SELECT * FROM `$Pforum_posts` WHERE thread_id = %d ORDER by timestamp, id ASC LIMIT %s',
            array($id, $limit)
        );

        //assign some vars to the tpl
        $this->objTPL->assign_vars(array(
            'THREAD_TITLE'  => $threadTitle,
            'PAGINATION'    => $objPagination->getPagination(true),
            'JUMPBOX'       => $this->objForm->start('jump'.randcode(2)).$this->buildJumpBox('jumpbox', $this->buildJumpBoxArray(), $thread['cat_id'], false).$this->objForm->finish(),
            'JUMPBOX2'      => $this->objForm->start('jump'.randcode(2)).$this->buildJumpBox('jumpbox2', $this->buildJumpBoxArray(), $thread['cat_id'], false).$this->objForm->finish(),
        ));

        //setup the watch thread trigger
        $watchThread = $this->objSQL->getInfo('forum_watch', array('user_id ="%s" AND thread_id ="%s"', $this->objUser->grab('id'), $id));
        $this->objTPL->assign_var('WATCH',
            (USER::$IS_ONLINE
                ? '<a href="'.$threadUrl.'?mode='.($watchThread ? 'unwatch' : 'watch').'">'.
                        langVar(($watchThread ? 'L_UNWATCH_THREAD' : 'L_WATCH_THREAD')).'</a>'
                : null)
        );

        //check if the thread is currently locked
        if($thread['locked']==0){
            $quick_reply = doArgs('forum_quickreply', false, $_SESSION['user']);

            //test if we get to output quick reply
            if($quick_reply && ($threadAuth['auth_reply'] || $threadAuth['auth_mod'] || User::$IS_MOD)){
                $_SESSION['site']['forum'][$id]['id']     = $id;
                $_SESSION['site']['forum'][$id]['sessid'] = $sessid = md5($this->objUser->grab('username').$id);

                //assign the form to the tpl
                $this->objTPL->assign_vars(array(
                    'F_START'           => $this->objForm->start('qreply', array('method' => 'POST', 'action' => $threadUrl.'?mode=qreply')),
                    'F_END'             => $this->objForm->finish(),
                    'HIDDEN'            => $this->objForm->inputbox('sessid', 'hidden', $sessid).
                                           $this->objForm->inputbox('id', 'hidden', $id).
                                           $this->objForm->inputbox('quick_reply', 'hidden', 'true'),

                    'L_QUICK_REPLY'     => langVar('L_QUICK_REPLY'),
                    'F_QUICK_REPLY'     => $this->objForm->textarea('post', '', array(
                                                'extra'=> ' tabindex="2"',
                                                'style'=> 'width:100%;height:50px;border:0;padding:0;',
                                                'placeholder'=> langVar('L_QR_PLACEHOLDER')
                                            )),

                    'POST_OPTIONS'      => langVar('L_OPTIONS'),
                    'OPTIONS'           => $this->objForm->checkbox('autoLock', null, false).' '.langVar('L_QR_LOCK_THREAD').
                                           (!$watchThread
                                               ? $this->objForm->checkbox($this->objUser->grab('autowatch'), 'watch_topic').' Watch Topic.'
                                               : NULL),

                    'SUBMIT'            => $this->objForm->button('submit', 'Post', array('extra'=> ' tabindex="3"')),
                ));

                $this->objTPL->assign_block_vars('qreply', array(
                    'TEXT'  => langVar('L_QUICK_REPLY'),
                ));

                if($threadAuth['auth_mod'] || User::$IS_MOD){
                    $this->objTPL->assign_block_vars('qreply.options', array());
                }
            }
            if($threadAuth['auth_reply'] || $threadAuth['auth_mod'] || User::$IS_MOD){
                $this->objTPL->assign_block_vars('reply', array(
                    'URL'   => $threadUrl.'?mode=reply',
                    'TEXT'  => langVar('L_POST_REPLY'),
                    'IMG'   => $thread['locked']==1 ? '<img src="'.$vars['FIMG_locked'].'" />'  : '<img src="'.$vars['FIMG_reply'].'" />' ,
                ));
            }

        }else{
            $this->objTPL->assign_block_vars('reply', array(
                'URL'   => $threadUrl.'?mode=unlock',
                'TEXT'  => langVar('L_THREAD_LOCKED'),
                'IMG'   => $thread['locked']==1 ? '<img src="'.$vars['FIMG_locked'].'" />' : NULL,
            ));
        }

        $this->objTPL->assign_var('POSTS', $this->outputPosts($posts, $thread));

        $this->objTPL->parse('body', false);
    }

    /**
     * Outputs threads
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   array     $posts
     * @param   array    $thread
     */
    public function outputPosts($posts, $thread){
        $vars = $this->objPage->getVar('tplVars');
        $_row_color1 = $vars['row_color1']; $_row_color2 = $vars['row_color2'];  $_row_highlight = $vars['row_highlight'];

        $this->objTPL->set_filenames(array(
            'posts' => 'modules/forum/template/forum_postsOutput.tpl',
        ));

        //grab the cat
        $cat = $this->getForumInfo($thread['cat_id']);
        $cat = $cat[0];

        //grab the auth and make sure they /can/ see it
        $threadAuth = $this->auth[$thread['cat_id']];

        $threadUrl = $this->generateThreadURL($thread);

        //grab posts counts
        $authors = array();
        foreach($posts as $post){
            $authors[] = $post['author'];
        }
        $postCounts = $this->getPostCounts($authors);

        //posts get output here
        foreach($posts as $post){
            $first_post = ($post['id']==$thread['first_post_id'] ? true : false);

            //set default so we have something to work with
                $author['profile'] = 'Guest';
                $author['avatar'] = '<img src="/'.root().'images/no_avatar.png" />';

                $author = $this->objUser->getUserInfo($post['author'], '*');

                //sort the contact info out
                $location = null;
                if(!is_empty($author['contact_info'])){
                    $author['contact_info'] = unserialize($author['contact_info']);

                    foreach($author['contact_info'] as $info){
                        if($info['type'] == 'url'){
                            $location = $info['val']; break;
                        }
                    }
                }

            $tplvars = array(
                'ID'            => $post['id'],

                'USERNAME'      => strtolower($author['profile']),
                'AUTHOR'        => $this->objUser->profile($author['id']),
                'AUTHOR_IO'     => $this->objUser->onlineIndicator($author['id']),

                'L_USERLEVEL'   => langVar('LEVEL'),
                'USERLEVEL'     => $author['level'],
                'TIME'          => langVar('L_POSTED_ON', $this->objTime->mk_time($post['timestamp'], 'dS M y @ h:s a')),
                'AVATAR'        => $this->objUser->parseAvatar($author['id'], 100),
                'POST'          => contentParse($post['post']),
                'EDITED'        => $post['edited']>0
                                     ? langVar('L_EDITED', $post['edited'], $this->objUser->profile($post['edited_uid']))
                                     : null,

                'ROW'           => $count%2==1 ? 'row_color2' : 'row_color1',
                'ALTROW'        => $count%2==1 ? 'row_color1' : 'row_color2',
            );

            if(doArgs('mode', false, $_GET) != 'reply'){
                $tplvars += array(
                    'USERTITLE'     => secureMe(doArgs('title', null, $author)),
                    'SIGNATURE'     => contentParse(doArgs('signature', '&nbsp;', $author)),
                    'POSTCOUNT'     => langVar('L_POST_COUNT', $postCounts[$author['id']]),
                    'LOCATION'      => !is_empty($location) ? langVar('L_LOCATION', $location) : null,
                    'IP'            => (User::$IS_MOD || $threadAuth['auth_mod'])
                                         ? langVar('L_USERS_IP', $post['poster_ip'])
                                         : null,

                );
            }


            //assign the info to the template
            $this->objTPL->assign_block_vars('thread', $tplvars);

            //allow the user to hide the sigs if so desired
            if(doArgs('mode', false, $_GET) != 'reply' && !is_empty($author['signature']) && !$this->objUser->grab('forum_show_sigs')){
                $this->objTPL->assign_block_vars('thread.sig', array());
            }

        //
        //-- Post Control Buttons
        //
            if(doArgs('mode', false, $_GET) != 'reply'){
                //MOD permissions
                if(User::$IS_MOD || $threadAuth['auth_mod']
                        //make sure the user is the author
                        || ($this->objUser->grab('id') == $post['author']
                            //make sure there is only 1 reply, or they are within the time limit
                            && ($thread['replies'] == 1 || (time()-$post['posted'] < $this->config('forum', 'post_edit_time'))))
                ){

                        if($threadAuth['auth_del'] && !$first_post){
                            $this->objTPL->assign_block_vars('thread.del', array(
                                'URL'   => $threadUrl.'?mode=rm&postid='.$post['id'],
                                'IMG'   => $vars['FIMG_post_del'],
                                'TEXT'  => langVar('L_DELETE'),
                            ));
                        }
                        if($threadAuth['auth_edit']){
                            $this->objTPL->assign_block_vars('thread.edit', array(
                                'EXTRA' => $this->objUser->ajaxSettings('forum_eip')
                                                ? ' id="post_'.$post['id'].'" class="editBtn"'
                                                : null,
                                'URL'   => $threadUrl.'?mode=edit&postid='.$post['id'],
                                'IMG'   => $vars['FIMG_post_edit'],
                                'TEXT'  => langVar('L_EDIT'),
                            ));
                        }
                }
                if(User::$IS_ONLINE){
                    $this->objTPL->assign_block_vars('thread.quote', array(
                        'URL'   => $threadUrl.'?mode=reply&q='.$post['id'],
                        'IMG'   => $vars['FIMG_post_quote'],
                        'TEXT'  => langVar('L_QUOTE'),
                    ));
                }
            }else{
                //this version will give us a clickable link that will insert the post in a quote straight into our post we are writing
                if(User::$IS_ONLINE){
                    $this->objTPL->assign_block_vars('thread.quote', array(
                        'EXTRA' => 'onclick="quotePost(\''.$post['id'].'\'); return false;"',
                        'URL'   => '#',
                        'IMG'   => $vars['FIMG_post_quote'],
                        'TEXT'  => langVar('L_QUOTE'),
                    ));
                }
            }

        $count++;
        }

        //thread controls
        if(User::$IS_MOD || $threadAuth['auth_mod']){
            if($threadAuth['auth_move']){
                $this->objTPL->assign_block_vars('move', array(
                    'URL'   => $threadUrl.'?mode=move',
                    'IMG'   => $vars['FIMG_post_move'],
                    'AJAX'  => ' onclick="return moveThread('.$id.');"',
                    'TEXT'  => langVar('F_MOVE'),
                ));
            }
            $this->objTPL->assign_block_vars('locked', array(
                'URL'   => $threadUrl.'?mode='.($thread['locked']==1 ? 'unlock' : 'lock'),
                'IMG'   => isset($thread['locked'])&&$thread['locked']==1
                                ? $vars['FIMG_locked']
                                : $vars['FIMG_unlocked'],
                'TEXT'  => isset($thread['locked'])&&$thread['locked']==1
                                ? langVar('F_UNLOCK')
                                : langVar('F_LOCK'),
            ));
            if($threadAuth['auth_del']){
                $this->objTPL->assign_block_vars('del', array(
                    'URL'   => $threadUrl.'?mode=rm',
                    'IMG'   => $vars['FIMG_post_del'],
                    'TEXT'  => langVar('F_DELETE'),
                ));
            }
        }

        $return = $this->objTPL->get_html('posts');
        $this->objTPL->reset_block_vars('thread');
        return $return;
    }

    /**
     * Allows posting of threads to the Forum
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int         $id
     */
    public function postThread($id){
        $category = $this->getForumInfo($id);
        $category = $category[0];
        $catAuth = $this->auth[$category['id']];

        //give em write by default
            $writeTest = true;

        //see if the user has write permissions
        if(!$catAuth['auth_post'] && !$catAuth['auth_mod'] && !User::$IS_MOD){
            $writeTest = false;
        }

        //apparently they havent..
        if(!$writeTest){
            $this->objTPL->set_filenames(array(
                'body' => 'modules/forum/template/forum_category.tpl'
            ));
            $this->objTPL->assign_block_vars('threads', array());
            $this->objTPL->assign_block_vars('threads.error', array(
                'ERROR' => langVar('L_NO_ID', $id),
            ));
            $this->objTPL->parse('body', false);
            return;
        }

        //if we get this far then they have permissions, so start the page output
        $this->objPage->addPagecrumb(array(
            array('url' => $this->config('global', 'url'), 'name' => langVar('B_POST_THREAD', $category['title'])),
        ));

        //okay so test to see which part of the page we should see..
        if(!HTTP_POST && (!isset($_GET['mode']) || $_GET['mode']!='post')){
            $this->objPage->addJSFile('/'.root().'scripts/editor.js');
            $this->objTPL->set_filenames(array(
                'body' => 'modules/forum/template/forum_post.tpl'
            ));
            $_SESSION['site']['forum'][$id]['id']     = $id;
            $_SESSION['site']['forum'][$id]['sessid'] = $sessid = $this->objUser->mkPassword($this->objUser->grab('username').$id);

        //
        //-- BBCode Buttons
        //
            $button[] = array('text_heading_1.png', 'Heading 1', 'h1', '[h1]|[/h1]');
            $button[] = array('text_heading_2.png', 'Heading 2', 'h2', '[h2]|[/h2]');
            $button[] = array('text_heading_3.png', 'Heading 3', 'h3', '[h3]|[/h3]');
            $button[] = '---';
            $button[] = array('text_bold.png', 'Bold', 'bold', '[b]|[/b]');
            $button[] = array('text_italic.png', 'Italics', 'italics', '[i]|[/i]');
            $button[] = array('text_underline.png', 'Underlined', 'underlined', '[u]|[/u]');
            $button[] = array('text_strikethrough.png', 'Strikethrough', 'strikethrough', '[s]|[/s]');
            $button[] = $this->genSelects('color');
            $button[] = '---';
            $button[] = array('link.png', 'Link', 'links', "[url]|[/url]");
            $button[] = array('email.png', 'Email Link', 'email', "[email]|[/email]");
            $button[] = array('photo_delete.png', 'Image', 'image', "[img]|[/img]");
            $button[] = array('comment.png', 'Add Quote', 'quote', "[quote]\n|\n[/quote]");
            $button[] = '---';
            $button[] = array('script_code.png', 'Code Block', 'code', "[code]\n|\n[/code]");
            $button[] = array('php.png', 'PHP Code Block', 'phpcode', "[code=php]\n|\n[/code]");
            $button[] = $this->genSelects('code');
            $button[] = '---';
            $button[] = array('text_columns.png', 'Add Table Columns', 'columns', "[columns]|[/columns]");
            $button[] = array('text_list_bullets.png', 'Add Bullet Points', 'ul', "[list]\n[*]|[/list]");
            $button[] = array('text_list_numbers.png', 'Add Numbered Points', 'ol', "[list=ol]\n[*]|\n[/list]");
            $button[] = array('text_superscript.png', 'Add Superscript Text', 'sup', "[sup]|[/sup]");
            $button[] = array('text_subscript.png', 'Add Subscript Text', 'sub', "[sub]|[/sub]");

            $this->objPlugins->hook('MODForum_post_buttons', $buttons);

            $buttons = NULL;
            foreach($button as $b){
                if(!is_array($b) && strlen($b)>3){ $buttons .= $b; continue; }
                if(!is_array($b) && $b == '---'){ $buttons .= ' &nbsp; '; continue; }

                $buttons .= sprintf(
                '<input type="image" src="%s" class="bbButton" title="%s" data-code="%s" />',
                    '/'.root().'images/icons/'.$b[0],
                    $b[1],
                    $b[3]
                );
            }

            $postMode = null;
            if(User::$IS_MOD || $catAuth['auth_mod']){
                $postVals = array(
                    1 => str_replace(array(':', ' '), '', strip_tags(langVar('L_ANNOUNCEMENT', ''))),
                    2 => str_replace(array(':', ' '), '', strip_tags(langVar('L_STICKY', ''))),
                    0 => str_replace(array(':', ' '), '', strip_tags(langVar('L_POST', ''))),
                );
                $postMode = $this->objForm->radio('type', $postVals, 0, array('br'=>true)).'<br />';
            }

            $autoLock = null;
            if(User::$IS_MOD || $catAuth['auth_mod']){
                $autoLock = $this->objForm->checkbox('autolock', '', false) . langVar('L_AUTO_LOCK');
            }


            //yada yada, the general tpl crap..
            $this->objTPL->assign_vars(array(
                'F_START'       => $this->objForm->start('reply', array('method' => 'POST', 'action' => '?mode=post')),
                'F_END'         => $this->objForm->finish(),

                'SMILIES'       => $this->generateSmilies(),
                'BUTTONS'       => $buttons,
                'ID'            => $this->objForm->inputbox('id', 'hidden', $id).
                                    $this->objForm->inputbox('sessid', 'hidden', $sessid),

                'L_TITLE'       => langVar('L_TITLE').':',
                'F_TITLE'       => $this->objForm->inputbox('title', 'input', '', array('extra'=> 'tabindex="1"', 'style'=> 'width:99%')),

                'L_POST_BODY'   => langVar('L_POST_BODY').':',
                'F_POST'        => $this->objForm->textarea('post', '', array('extra'=> 'tabindex="2" rows="3"', 'style'=> 'height:350px;width:99%;')),

                'POST_MODE'     => $postMode,
                'AUTO_LOCK'        => $autoLock,
                'WATCH_TOPIC'   => $this->objForm->checkbox('watch_thread', '', true). langVar('L_WATCH_THREAD'),

                'SUBMIT'        => $this->objForm->button('submit', 'Submit', array('extra'=> ' tabindex="3"')),
                'RESET'         => $this->objForm->button('preview', 'Preview', array('extra'=> ' tabindex="4" onclick="doPreview();"')),
            ));

            $this->objTPL->assign_block_vars('new_post', array());
            $this->objTPL->assign_block_vars('title', array());
            $this->objTPL->parse('body', false);
            return;
        }else{
            //check to make sure we have a cat id
            if(!doArgs('id', false, $_POST)){
                hmsgDie('FAIL', 'Error: I cannot remember where your posting to.');
            }

                //content checks
                if(!doArgs('title', false, $_POST) || !doArgs('post', false, $_POST)){
                    unset($_SESSION['site']['forum']);
                    hmsgDie('FAIL', 'Post Failed - Title or Post either missing or not long enough.');
                }

            if(!doArgs('id', false, $_SESSION['site']['forum'][$id]) || $_SESSION['site']['forum'][$id]['id']!=$_POST['id']){
                hmsgdie('FAIL', 'Post Failed - I cannot remember where your posting to.');
            }

            if(!doArgs('sessid', false, $_SESSION['site']['forum'][$id]) || $_SESSION['site']['forum'][$id]['sessid']!=$_POST['sessid']){
                hmsgdie('FAIL', 'Post Failed - Security Check failed. Please make sure your posting directly from the page.');
            }
        //
        //--insert the post info into the db
        //
            $uid = $this->objUser->grab('id');

            //generate the sql for the topics table....Part 1
            unset($thread);
            $thread['mode']         = doArgs('type', false, $_POST, 'is_number') && ($catAuth['auth_mod'] || User::$IS_MOD) ? $_POST['type'] : 0;
            $thread['cat_id']       = $id;
            $thread['author']       = $uid;
            $thread['locked']       = isset($_POST['autolock']) && ($catAuth['auth_mod'] || User::$IS_MOD) ? 1 : 0;
            $thread['subject']      = secureMe($_POST['title']);
            $thread['last_uid']     = $uid;
            $thread['timestamp']    = time();

                $thread['id'] = $this->objSQL->getAI('forum_threads');
                $log = 'Forum: New thread posted - <a href="'.$this->generateThreadURL($thread).'">'.
                            secureMe($_POST['title']).'</a> by '.$this->objUser->profile($uid, RAW);
                unset($thread['id']);
                $topic_insert = $this->objSQL->insertRow('forum_threads', $thread, $log);
                    if(!$topic_insert){
                        unset($_SESSION['site']['forum']);
                        hmsgDie('FAIL', 'Post Failed - Inserting the data into the db failed.(1)');
                    }

            //and now to generate the sql for the actual post table ;D...part 2
            unset($post);
            $post['post']       = secureMe($_POST['post']);
            $post['author']     = $uid;
            $post['timestamp']  = time();
            $post['thread_id']  = $topic_insert;
            $post['poster_ip']  = User::getIP();

                $post_insert = $this->objSQL->insertRow('forum_posts', $post);
                    if(!$post_insert){
                        unset($_SESSION['site']['forum']);
                        hmsgDie('FAIL', 'Post Failed - Inserting the data into the db failed.(2)');
                    }

            //update the parent category
            unset($array);
            $array['last_post_id'] = $topic_insert;

                $this->objSQL->updateRow('forum_cats', $array, array('id ="%s"', $category['id']));

            //this one is so we know which post is the original
            unset($array);
            $array['first_post_id'] = $post_insert;

                $this->objSQL->updateRow('forum_threads', $array, array('id ="%s"', $topic_insert));

            //update the forum watch table
            if(isset($_POST['watch_topic'])){
                unset($array);
                $array['user_id'] = $_uid;
                $array['thread_id'] = $topic_insert;

                    $this->objSQL->insertRow('forum_watch', $array);
            }

            unset($_SESSION['site']);
            $this->objPage->redirect('/'.root().'modules/forum/thread/'.seo($thread['subject']).'-'.$topic_insert.'.html#top', 0, 3);
            hmsgDie('INFO', 'Thread successfully posted. Redirecting you to it.');
        }
    }

    /**
     * Allows static reply to a thread
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int         $id
     */
    public function postReply($id){
        //grab the required thread so we got something to work with..
        $thread = $this->objSQL->getLine('SELECT * FROM `$Pforum_threads` WHERE id ="%s" LIMIT 1;', array($id));
            if(!$thread) hmsgDie('FAIL', 'Failed to retreive thread information');

        $category = $this->getForumInfo($thread['cat_id']);
        $category = $category[0];
        $catAuth = $this->auth[$category['id']];

        //give em write by default
            $writeTest = true;

        //see if the user has write permissions
        if(!$catAuth['auth_reply'] && !$catAuth['auth_mod'] && !User::$IS_MOD){
            $writeTest = false;
        }

        //apparently they havent..
        if(!$writeTest || $thread['locked']){
            $this->objTPL->set_filenames(array(
                'body' => 'modules/forum/template/forum_category.tpl'
            ));
            $this->objTPL->assign_block_vars('threads', array());
            $this->objTPL->assign_block_vars('threads.error', array(
                'ERROR' => $thread['locked'] ? langVar('L_LOCKED') : langVar('L_AUTH_POST', $catAuth['auth_reply_type']),
            ));
            $this->objTPL->parse('body', false);
            return;
        }

        //if we get this far then they have permissions, so start the page output
        $this->objPage->addPagecrumb(array(
            array('url' => $this->config('global', 'url'), 'name' => langVar('B_POST_REPLY', $thread['subject'])),
        ));

        //okay so test to see which part of the page we should see..
        if(!HTTP_POST && (!isset($_GET['mode']) || $_GET['mode']!='post')){
            $this->objPage->addJSFile('/'.root().'scripts/editor.js');
            $this->objTPL->set_filenames(array(
                'body' => 'modules/forum/template/forum_post.tpl'
            ));
            $_SESSION['site']['forum'][$id]['id']     = $id;
            $_SESSION['site']['forum'][$id]['sessid'] = $sessid = $this->objUser->mkPassword($this->objUser->grab('username').$id);

        //
        //-- BBCode Buttons
        //
            $button[] = array('text_heading_1.png', 'Heading 1', 'h1', '[h1]|[/h1]');
            $button[] = array('text_heading_2.png', 'Heading 2', 'h2', '[h2]|[/h2]');
            $button[] = array('text_heading_3.png', 'Heading 3', 'h3', '[h3]|[/h3]');
            $button[] = '---';
            $button[] = array('text_bold.png', 'Bold', 'bold', '[b]|[/b]');
            $button[] = array('text_italic.png', 'Italics', 'italics', '[i]|[/i]');
            $button[] = array('text_underline.png', 'Underlined', 'underlined', '[u]|[/u]');
            $button[] = array('text_strikethrough.png', 'Strikethrough', 'strikethrough', '[s]|[/s]');
            $button[] = $this->genSelects('color');
            $button[] = '---';
            $button[] = array('link.png', 'Link', 'links', "[url]|[/url]");
            $button[] = array('email.png', 'Email Link', 'email', "[email]|[/email]");
            $button[] = array('photo_delete.png', 'Image', 'image', "[img]|[/img]");
            $button[] = array('comment.png', 'Add Quote', 'quote', "[quote]\n|\n[/quote]");
            $button[] = '---';
            $button[] = array('script_code.png', 'Code Block', 'code', "[code]\n|\n[/code]");
            $button[] = array('php.png', 'PHP Code Block', 'phpcode', "[code=php]\n|\n[/code]");
            $button[] = $this->genSelects('code');
            $button[] = '---';
            $button[] = array('text_columns.png', 'Add Table Columns', 'columns', "[columns]|[/columns]");
            $button[] = array('text_list_bullets.png', 'Add Bullet Points', 'ul', "[list]\n[*]|[/list]");
            $button[] = array('text_list_numbers.png', 'Add Numbered Points', 'ol', "[list=ol]\n[*]|\n[/list]");
            $button[] = array('text_superscript.png', 'Add Superscript Text', 'sup', "[sup]|[/sup]");
            $button[] = array('text_subscript.png', 'Add Subscript Text', 'sub', "[sub]|[/sub]");

            $this->objPlugins->hook('MODForum_post_buttons', $buttons);

            $buttons = NULL;
            foreach($button as $b){
                if(!is_array($b) && strlen($b)>3){ $buttons .= $b; continue; }
                if(!is_array($b) && $b == '---'){ $buttons .= ' &nbsp; '; continue; }

                $buttons .= sprintf(
                '<input type="image" src="%s" class="bbButton" title="%s" data-code="%s" />',
                    '/'.root().'images/icons/'.$b[0],
                    $b[1],
                    $b[3]
                );
            }

            //enable direct quoting of posts
            $msg = null;
            if(doArgs('q', false, $_GET, 'is_number')){
                $query = $this->objSQL->getLine('SELECT author, post FROM `$Pforum_posts` WHERE id = "%d" LIMIT 1;', array($_GET['q']));

                if(is_array($query)){
                    $msg = '[quote='.$this->objUser->getUserInfo($query['author'], 'username').']'."\n".(htmlspecialchars_decode($query['post']))."\n".'[/quote]'."\n";
                }
            }

            $forumWatch = $this->objSQL->getInfo('forum_watch', array('user_id= "%s" AND thread_id= "%s"', $this->objUser->grab('id'), $id));

            //yada yada, the general tpl crap..
            $this->objTPL->assign_vars(array(
                'F_START'       => $this->objForm->start('reply', array('method' => 'POST', 'action' => '')),
                'F_END'         => $this->objForm->finish(),

                'SMILIES'       => $this->generateSmilies(),
                'BUTTONS'       => $buttons,
                'ID'            => $this->objForm->inputbox('id', 'hidden', $id).
                                    $this->objForm->inputbox('sessid', 'hidden', $sessid),

                'L_TITLE'       => langVar('L_TITLE').':',
                'F_TITLE'       => $this->objForm->inputbox('title', 'input', '', array('extra'=> 'tabindex="1"', 'style'=> 'width:99%')),

                'L_POST_BODY'   => langVar('L_POST_BODY').':',
                'F_POST'        => $this->objForm->textarea('post', $msg, array('extra'=> 'tabindex="2" rows="3"', 'style'=> 'height:350px;width:99%;')),

                'WATCH_TOPIC'   => $this->objForm->checkbox('watch_thread', '', true). langVar('L_WATCH_THREAD'),

                'SUBMIT'        => $this->objForm->button('submit', 'Submit', array('extra'=> ' tabindex="3"')),
                'RESET'         => $this->objForm->button('preview', 'Preview', array('extra'=> ' tabindex="4" onclick="doPreview();"')),
            ));

            if(!$forumWatch){
                $this->objTPL->assign_block_vars('new_post', array());
            }

            $posts = $this->objSQL->getTable('SELECT * FROM `$Pforum_posts` WHERE thread_id ="%s" ORDER BY id DESC LIMIT 10;', array($id));

            if(count($posts)){
                $this->objTPL->assign_block_vars('reply_posts', array(
                    'L_THREAD_RECAP' => langVar('L_THREAD_RECAP'),
                    'CONTENT' => $this->outputPosts($posts, $thread),
                ));
            }

            $this->objTPL->parse('body', false);
            return;
        }else{
            //check to make sure we have a cat id
            if(!doArgs('id', false, $_POST)){
                hmsgDie('FAIL', 'Error: I cannot remember where your posting to.');
            }

                //content checks
                if(!doArgs('post', false, $_POST)){
                    unset($_SESSION['site']['forum']);
                    hmsgDie('FAIL', 'Post Failed - Post either missing or not long enough.');
                }

            if(!doArgs('id', false, $_SESSION['site']['forum'][$id]) || $_SESSION['site']['forum'][$id]['id']!=$_POST['id']){
                hmsgdie('FAIL', 'Post Failed - I cannot remember where your posting to.');
            }

            if(!doArgs('sessid', false, $_SESSION['site']['forum'][$id]) || $_SESSION['site']['forum'][$id]['sessid']!=$_POST['sessid']){
                hmsgdie('FAIL', 'Post Failed - Security Check failed. Please make sure your posting directly from the page.');
            }
        //
        //--insert the post info into the db
        //
            $uid = $this->objUser->grab('id');

            //generate the post
            unset($post);
            $post['post']           = secureMe($_POST['post']);
            $post['author']         = $uid;
            $post['timestamp']      = time();
            $post['thread_id']      = $thread['id'];
            $post['poster_ip']      = User::getIP();

                $post_insert = $this->objSQL->insertRow('forum_posts', $post);
                    if(!$post_insert){
                        unset($_SESSION['site']['forum']);
                        hmsgDie('FAIL', 'Post Failed - Inserting the data into the db failed.(1)');
                    }

            //update the thread
            unset($update);
            $update['last_uid'] = $uid;
                $thread_update = $this->objSQL->updateRow('forum_threads', $update, array('id ="%s"', $id));

            //update the forum watch table
            if(isset($_POST['watch_topic'])){
                unset($array);
                $array['user_id'] = $uid;
                $array['thread_id']    = $thread['id'];

                    $this->objSQL->insertRow('forum_watch', $array);
            }

            //update the parent category
            unset($array);
            $array['last_post_id'] = $post_insert;

                $this->objSQL->updateRow('forum_cats', $array, array('id ="%s"', $category['id']));

            //do the notifications
            $info = array(
                'timestamp'     => time(),
                'content_id'    => $thread_id,
                'thread_id'     => $thread['id'],
            );
            $this->notify($id, $thread, $info);

            unset($_SESSION['site']);
            $this->objPage->redirect('/'.root().'modules/forum/thread/'.seo($thread['subject']).'-'.$thread['id'].'.html#top', 0, 3);
        }
    }

    /**
     * Allows quick reply
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int         $id
     */
    public function postQuickReply($id){
        //grab the required thread so we got something to work with..
        $thread = $this->objSQL->getLine('SELECT * FROM `$Pforum_threads` WHERE id ="%s" LIMIT 1;', array($id));
            if(!$thread) hmsgDie('FAIL', 'Failed to retreive thread information');

        $category = $this->getForumInfo($thread['cat_id']);
        $category = $category[0];
        $catAuth = $this->auth[$category['id']];

        //give em write by default
            $writeTest = true;

        //see if the user has write permissions
        if(!$catAuth['auth_reply'] && !$catAuth['auth_mod'] && !User::$IS_MOD){
            $writeTest = false;
        }

        //apparently they havent..
        if(!$writeTest || $thread['locked']){
            $this->objTPL->set_filenames(array(
                'body' => 'modules/forum/template/forum_category.tpl'
            ));
            $this->objTPL->assign_block_vars('threads', array());
            $this->objTPL->assign_block_vars('threads.error', array(
                'ERROR' => $thread['locked'] ? langVar('L_LOCKED') : langVar('L_AUTH_POST', $catAuth['auth_reply_type']),
            ));
            $this->objTPL->parse('body', false);
            return;
        }

        //if we get this far then they have permissions, so start the page output
        $this->objPage->addPagecrumb(array(
            array('url' => $this->config('global', 'url'), 'name' => langVar('B_POST_REPLY', $thread['subject'])),
        ));


        //okay so test to see which part of the page we should see..
        if(HTTP_POST && isset($_GET['mode']) && $_GET['mode']=='qreply'){
            //check to make sure wer coming from a quick reply form
            if(!doArgs('quick_reply', false, $_POST)){
                hmsgDie('FAIL', 'Error: Post Failed.');
            }

            //check to make sure we have a cat id
            if(!doArgs('id', false, $_POST)){
                hmsgDie('FAIL', 'Error: I cannot remember where your posting to.');
            }

                //content checks
                if(!doArgs('post', false, $_POST)){
                    unset($_SESSION['site']['forum']);
                    hmsgDie('FAIL', 'Post Failed - Post either missing or not long enough.');
                }

            if(!doArgs('id', false, $_SESSION['site']['forum'][$id]) || $_SESSION['site']['forum'][$id]['id']!=$_POST['id']){
                hmsgdie('FAIL', 'Post Failed - I cannot remember where your posting to.');
            }

            if(!doArgs('sessid', false, $_SESSION['site']['forum'][$id]) || $_SESSION['site']['forum'][$id]['sessid']!=$_POST['sessid']){
                hmsgdie('FAIL', 'Post Failed - Security Check failed. Please make sure your posting directly from the page.');
            }
        //
        //--insert the post info into the db
        //
            $uid = $this->objUser->grab('id');

            //generate the post
            unset($post);
            $post['post']        = secureMe($_POST['post']);
            $post['author']        = $uid;
            $post['timestamp']    = time();
            $post['thread_id']    = $thread['id'];
            $post['poster_ip']    = User::getIP();

                $post_insert = $this->objSQL->insertRow('forum_posts', $post);
                    if(!$post_insert){
                        unset($_SESSION['site']['forum']);
                        hmsgDie('FAIL', 'Post Failed - Inserting the data into the db failed.(1)');
                    }

            //update the thread
            unset($update);
            $update['last_uid'] = $uid;
                $thread_update = $this->objSQL->updateRow('forum_threads', $update, array('id ="%s"', $id));

            //update the forum watch table
            if(isset($_POST['watch_topic'])){
                unset($array);
                $array['user_id'] = $uid;
                $array['thread_id']    = $thread['id'];

                    $this->objSQL->insertRow('forum_watch', $array);
            }

            //update the parent category
            unset($array);
            $array['last_post_id'] = $post_insert;

                $this->objSQL->updateRow('forum_cats', $array, array('id ="%s"', $category['id']));

            //do the notifications
            $info = array(
                'timestamp' => time(),
                'content_id' => $thread_id,
                'thread_id' => $thread['id'],
            );
            $this->notify($id, $thread, $info);

            unset($_SESSION['site']);
            if(!HTTP_AJAX){
                $this->objPage->redirect('/'.root().'modules/forum/thread/'.seo($thread['subject']).'-'.$thread['id'].'.html#top', 0, 3);
            }else{
                //grab the thread
                $thread = $this->objSQL->getLine(
                    'SELECT t.*, COUNT(DISTINCT p.id) as posts
                        FROM `$Pforum_threads` t
                        LEFT JOIN `$Pforum_posts` p
                            ON p.thread_id = t.id
                        WHERE t.id = %d',
                    array($thread['id'])
                );

                $pages = ceil($thread['posts']/10);
                $page = doArgs('mode', false, $_GET)=='last_page' ? $pages : doArgs('page', 1, $_GET);
                if($page < $pages){
                    echo '<script>document.location= "'.$this->generateThreadURL($thread).'?mode=last_page";</script>';
                    exit;
                }

                $post['id'] = $post_insert;
                echo $this->outputPosts(array($post), $thread);
                exit;
            }
        }
        hmsgDie('FAIL', 'Error: Quick Reply Precedure Fail.');
    }

    /**
     * Allows a user to edit post if conditions are right
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int         $id
     */
    public function editPost($id){
        //grab the post were reffering to
        $post = $this->objSQL->getLine('SELECT * FROM `$Pforum_posts` WHERE id ="%s" LIMIT 1;', array($id));
            if(!is_array($post)){ hmsgDie('FAIL', 'Failed to retreive post information'); }

        $thread = $this->objSQL->getLine(
                'SELECT t.*, COUNT(DISTINCT p.id) as replies
                    FROM `$Pforum_threads` t
                    LEFT JOIN `$Pforum_posts` p ON p.thread_id = t.id
                    WHERE t.id ="%s"
                    GROUP BY t.id',
                array($post['thread_id'])
        );
            if(!is_array($thread)){ hmsgDie('FAIL', 'Failed to retreive thread information'); }

        $category = $this->getForumInfo($thread['cat_id']);
        $category = $category[0];
        $catAuth = $this->auth[$category['id']];

        $writeTest = false;
        //see if the user has write permissions
        if(User::$IS_MOD || $catAuth['auth_mod']
            //make sure the user is the author
            || ($catAuth['auth_edit'] && $this->objUser->grab('id') == $post['author']
                //make sure there is only 1 reply, or they are within the time limit
                && ($thread['replies'] == 1 || (time()-$post['posted'] < $this->config('forum', 'post_edit_time'))))
        ){
            $writeTest = true;
        }

        if($writeTest != true){
            $this->objTPL->set_filenames(array(
                'body' => 'modules/forum/template/forum_category.tpl'
            ));
            $this->objTPL->assign_block_vars('threads', array());
            $this->objTPL->assign_block_vars('threads.error', array(
                'ERROR' => $msg,
            ));
            $this->objTPL->parse('body', false);
            return;
        }

        //if we get this far then they have permissions, so start the page output
        $this->objPage->addPagecrumb(array(
            array('url' => $this->config('global', 'url'), 'name' => langVar('B_EDIT_POST', $thread['subject'])),
        ));

        //okay so test to see which part of the page we should see..
        if(!HTTP_POST){
            $this->objPage->addJSFile('/'.root().'scripts/editor.js');
            $this->objTPL->set_filenames(array(
                'body' => 'modules/forum/template/forum_post.tpl'
            ));
            $_SESSION['site']['forum'][$id]['id']     = $id;
            $_SESSION['site']['forum'][$id]['sessid'] = $sessid = $this->objUser->mkPassword($this->objUser->grab('username').$id);

            $first_post = false;
            if($id == $thread['first_post_id']){
                $first_post = true;
            }
        //
        //-- BBCode Buttons
        //
            $button[] = array('text_heading_1.png', 'Heading 1', 'h1', '[h1]|[/h1]');
            $button[] = array('text_heading_2.png', 'Heading 2', 'h2', '[h2]|[/h2]');
            $button[] = array('text_heading_3.png', 'Heading 3', 'h3', '[h3]|[/h3]');
            $button[] = '---';
            $button[] = array('text_bold.png', 'Bold', 'bold', '[b]|[/b]');
            $button[] = array('text_italic.png', 'Italics', 'italics', '[i]|[/i]');
            $button[] = array('text_underline.png', 'Underlined', 'underlined', '[u]|[/u]');
            $button[] = array('text_strikethrough.png', 'Strikethrough', 'strikethrough', '[s]|[/s]');
            $button[] = $this->genSelects('color');
            $button[] = '---';
            $button[] = array('link.png', 'Link', 'links', "[url]|[/url]");
            $button[] = array('email.png', 'Email Link', 'email', "[email]|[/email]");
            $button[] = array('photo_delete.png', 'Image', 'image', "[img]|[/img]");
            $button[] = array('comment.png', 'Add Quote', 'quote', "[quote]\n|\n[/quote]");
            $button[] = '---';
            $button[] = array('script_code.png', 'Code Block', 'code', "[code]\n|\n[/code]");
            $button[] = array('php.png', 'PHP Code Block', 'phpcode', "[code=php]\n|\n[/code]");
            $button[] = $this->genSelects('code');
            $button[] = '---';
            $button[] = array('text_columns.png', 'Add Table Columns', 'columns', "[columns]|[/columns]");
            $button[] = array('text_list_bullets.png', 'Add Bullet Points', 'ul', "[list]\n[*]|[/list]");
            $button[] = array('text_list_numbers.png', 'Add Numbered Points', 'ol', "[list=ol]\n[*]|\n[/list]");
            $button[] = array('text_superscript.png', 'Add Superscript Text', 'sup', "[sup]|[/sup]");
            $button[] = array('text_subscript.png', 'Add Subscript Text', 'sub', "[sub]|[/sub]");

            $this->objPlugins->hook('MODForum_post_buttons', $buttons);

            $buttons = NULL;
            foreach($button as $b){
                if(!is_array($b) && strlen($b)>3){ $buttons .= $b; continue; }
                if(!is_array($b) && $b == '---'){ $buttons .= ' &nbsp; '; continue; }

                $buttons .= sprintf(
                '<input type="image" src="%s" class="bbButton" title="%s" data-code="%s" />',
                    '/'.root().'images/icons/'.$b[0],
                    $b[1],
                    $b[3]
                );
            }

            $postMode = null; $title = null;
            if($first_post && (User::$IS_MOD || $catAuth['auth_mod'])){
                $postVals = array(
                    1 => str_replace(array(':', ' '), '', strip_tags(langVar('L_ANNOUNCEMENT', ''))),
                    2 => str_replace(array(':', ' '), '', strip_tags(langVar('L_STICKY', ''))),
                    0 => str_replace(array(':', ' '), '', strip_tags(langVar('L_POST', ''))),
                );
                $postMode = $this->objForm->radio('type', $postVals, 0, array('br'=>true)).'<br />';

                $this->objTPL->assign_block_vars('title', array());
                $this->objTPL->assign_block_vars('new_post', array());
            }

            //yada yada, the general tpl crap..
            $this->objTPL->assign_vars(array(
                'F_START'       => $this->objForm->start('edit', array('method' => 'POST', 'action' => '?mode=edit&postid='.$id)),
                'F_END'         => $this->objForm->finish(),

                'SMILIES'       => $this->generateSmilies(),
                'BUTTONS'       => $buttons,
                'ID'            => $this->objForm->inputbox('id', 'hidden', $id).
                                    $this->objForm->inputbox('sessid', 'hidden', $sessid),

                'L_TITLE'       => langVar('L_TITLE').':',
                'F_TITLE'       => $this->objForm->inputbox('title', 'input', $thread['subject'], array('extra'=> 'tabindex="1"', 'style'=> 'width:99%')),

                'L_POST_BODY'   => langVar('L_POST_BODY').':',
                'F_POST'        => $this->objForm->textarea('post', $post['post'], array('extra'=> 'tabindex="2" rows="3"', 'style'=> 'height:350px;width:99%;')),

                'POST_MODE'     => $postMode,

                'SUBMIT'        => $this->objForm->button('submit', 'Submit', array('extra'=> ' tabindex="3"')),
                'RESET'         => $this->objForm->button('preview', 'Preview', array('extra'=> ' tabindex="4" onclick="doPreview();"')),
            ));

            $this->objTPL->parse('body', false);
            return;
        }else{
            //check to make sure we have a cat id
            if(!doArgs('id', false, $_POST)){
                hmsgDie('FAIL', 'Error: I cannot remember where your posting to.');
            }

                //content checks
                if(!doArgs('post', false, $_POST)){
                    unset($_SESSION['site']['forum']);
                    hmsgDie('FAIL', 'Post Failed - Post either missing or not long enough.');
                }

            if(!doArgs('id', false, $_SESSION['site']['forum'][$id]) || $_SESSION['site']['forum'][$id]['id']!=$_POST['id']){
                hmsgdie('FAIL', 'Post Failed - I cannot remember where your posting to.');
            }

            if(!doArgs('sessid', false, $_SESSION['site']['forum'][$id]) || $_SESSION['site']['forum'][$id]['sessid']!=$_POST['sessid']){
                hmsgdie('FAIL', 'Post Failed - Security Check failed. Please make sure your posting directly from the page.');
            }
        //
        //--insert the post info into the db
        //
            $uid = $this->objUser->grab('id');

            unset($update);
            $update['post'] = secureMe($_POST['post']);
            $update['edited'] = $thread['edited']+1;
            $update['edited_uid'] = $uid;

                $post_update = $this->objSQL->updateRow('forum_posts', $update, array('id ="%s"', $id));
                if(!$post_update){
                    hmsgDie('FAIL', 'Error: This is not your post, or there was a problem with saving the post. Error 0x02;');
                }

            $this->objPage->redirect('/'.root().'modules/forum/thread/'.seo($thread['subject']).'-'.$thread['id'].'.html');
            hmsgDie('INFO', 'Thread successfully posted. Redirecting you to it.');
        }
    }

    /**
     * Deletes a thread from the forum
     *
     * @version 3.0
     * @since   0.8.0
     * @author  xLink
     *
     * @param   int     $id
     *
     * @return  bool
     */
    function delThread($id){
        if(!isset($id) || !is_number($id)){
            hmsgDie('Error: Thread ID is invalid.');
        }

        $thread = $this->objSQL->getLine(
            'SELECT t.*, COUNT(DISTINCT p.id) as replies
                FROM `cscms_forum_threads` t
                LEFT JOIN `cscms_forum_posts` p ON p.thread_id = t.id
                WHERE t.id ="%s"
                GROUP BY t.id',
            array($id)
        );

        $category = $this->getForumInfo($thread['cat_id']);
        $category = $category[0];
        $catAuth = $this->auth[$category['id']];

        //give em write by default
            $writeTest = true;

        //see if the user has write permissions
            if(!$catAuth['auth_del'] && !$catAuth['auth_mod'] && !User::$IS_MOD){
                $writeTest = false;
            }

            //no, no they havent so just error
            if(!$writeTest){
                //or show the thread, i think this is better
                $this->viewThread($id);
                return; //we dont want to go any further in the function now do we? :)
            }

            unset($update);

        $continue = confirmMsg('INFO', 'You are about to delete this thread. Continue?', 'Thread Deletion', 'body');
        if($continue){

            //delete teh posts
            $this->objSQL->deleteRow('forum_posts', array('thread_id ="%d"', $id));

            $this->objSQL->deleteRow('forum_threads', array('id ="%d"', $id),
                'Forum: '.$this->objUser->profile($this->objUser->grab('id'), RAW).' Deleted Thread ID - '.$id.' / '.secureMe($thread['subject']));

            //update the source cat with the propper latest posts
            $lastPost = $this->objSQL->getLine(
                'SELECT p.*, t.id
                    FROM `$Pforum_posts` p, `$Pforum_threads` t

                    WHERE t.cat_id ="%d"
                    GROUP BY p.id
                    ORDER BY p.timestamp DESC
                    LIMIT 1',
                array($thread['cat_id'])
            );

                unset($update);
                $update['last_post_id'] = $lastPost['id'];
                    $this->objSQL->updateRow('forum_cats', $update, array('id="%d"', $thread['cat_id']));

            $this->objPage->redirect('/'.root().'modules/forum/', 0, 2);
            hmsgDie('INFO', 'Thread Deleted.');
        }
    }

    /**
     * Deletes a reply from a thread
     *
     * @version 3.0
     * @since   0.8.0
     * @author  xLink
     *
     * @param   int     $id
     *
     * @return  bool
     */
    function delReply($id){
            //grab the post
            $post = $this->objSQL->getLine('SELECT * FROM `$Pforum_posts` WHERE id = "%d" LIMIT 1;', $thread['cat_id']);
                if(!$post) hmsgDie('FAIL', 'Failed to reteive post information');

            //grab the thread also
            $thread = $this->objSQL->getLine(
                'SELECT t.*, COUNT(DISTINCT p.id) as replies
                    FROM `cscms_forum_threads` t
                    LEFT JOIN `cscms_forum_posts` p ON p.thread_id = t.id
                    WHERE t.id ="%s"
                    GROUP BY t.id',
                array($thread['cat_id'])
            );
                if(!$thread){ hmsgDie('FAIL', 'Failed to retreive thread information'); }

            //and the category for permissions
            $category = $this->getForumInfo($thread['cat_id']);
            $category = $category[0];
            $catAuth = $this->auth[$category['id']];

            //give em write by default
                $writeTest = true;

            //see if the user has write permissions
                if(!$catAuth['auth_del'] && !$catAuth['auth_mod'] && !User::$IS_MOD){
                    $writeTest = false;
                }

            //no, no they havent so just error
                if(!$writeTest){
                    //or show the thread, i think this is better
                    $this->viewThread($id);
                    return; //we dont want to go any further in the function now do we? :)
                }

            unset($update);

        $continue = confirmMsg('INFO', 'You are about to delete a reply. Continue?', 'Reply Deletion', 'body');
        if($continue){

            //delete teh actuall post
            $this->objSQL->deleteRow('forum_posts', array('id ="%d"', $id),
                'Forum: '.$this->objUser->profile($this->objUser->grab('id'), RAW).' Deleted Post ID - '.$id.' from '.secureMe($thread['subject']));

            $this->objPage->redirect($this->generateThreadURL($thread), 0, 2);
            hmsgDie('INFO', 'Post Deleted.');
        }
    }

    /**
     * Keeps tracking topics upto date
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     */
    private function forumTrackerInit(){
        //if they arn't logged in return, no need to continue
        if(!User::$IS_ONLINE){ return; }

        if($this->objUser->grab('last_visit') < ($this->objTime->mod_time(time(), 0, 0, (24*60), 'TAKE'))){
            $_SESSION['user']['last_visit'] = $this->objTime->mod_time(time(), 0, 0, (24*60), 'TAKE');
        }

        //setup the tracking array
        $tracking_threads = array();
        if(doArgs('forum_tracker', false, $_SESSION['user'])){
            $tracking_threads = unserialize($_SESSION['user']['forum_tracker']);
        }

        //grab the latest posts since users last visit
        $query = $this->objSQL->getTable(
            'SELECT t.id, t.cat_id, p.timestamp as last_poster
                FROM `$Pforum_threads` t
                LEFT JOIN `$Pforum_posts` p
                    ON t.id = p.thread_id

                  WHERE p.timestamp >= "%s"
                GROUP BY t.id',
            array($this->objUser->grab('last_visit'))
        );
            if(!count($query)){ return; }

        //loop through em and set them in the tracking array
        foreach($query as $t){
            if($tracking_threads[$t['id']]['last_poster'] < $t['last_poster']){
               $t['read'] = false;
               $tracking_threads[$t['id']] = $t;
            }
        }

        //make sure we have a limited number of topics in the array
        if(count($tracking_threads)){
            foreach($tracking_threads as $tracking_thread){
                if(count($tracking_threads) >= 150 || $tracking_thread['last_poster'] < $this->objUser->grab('last_visit'))
                    unset($tracking_threads[$tracking_thread['id']]);
            }
        }

        /**
         * update the user row with the tracking topics, this could be done via cookies
         * but doing it this way lets users sign in from other places and still have
         * access to their info...and stops problems with cookies not being set too xD
         */
        unset($update);
        $update['forum_tracker'] = serialize($tracking_threads);
        $_SESSION['user']['forum_tracker'] = $update['forum_tracker'];
        $this->objUser->updateUserSettings($this->objUser->grab('id'), $update);
        unset($tracking_topic, $tracking_threads);
     }

    /**
     * Retreives breadcrumb info for sub categories.
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int     $threadId
     * @param   array   $thread
     * @param   array   $postInfo
     */
    private function notify($threadId, $thread, $postInfo){
        $users2Notify = array(); $uid = $this->objUser->grab('id');

        //grab the watching users
        $watchingUsers = $this->objSQL->getTable(
            'SELECT DISTINCT user_id, seen FROM $Pforum_watch WHERE thread_id ="%s" AND seen = 1',
            array($threadId)
        );
            foreach($watchingUsers as $user){ $users2Notify[] = $user['user_id']; }

        //remove the current user, no point in email him about the post he just made O.o
        $users2Notify = array_diff($users2Notify, array($uid));
        if(is_empty($users2Notify)){ return false; }

        //grab some info about the users
        $users = array();
        foreach($users2Notify as $user){
            $users[] = $this->objUser->getUserInfo($user['id']);
        }

        //if we have no users then return here
        if(is_empty($users)){ return false; }

        //update forum watch
        unset($update);
        $update['seen'] = 0;
            $this->objSQL->updateRow('forum_watch', $update, array(
                'thread_id ="%d" AND uid IN (%s)',
                $threadId,
                implode(', ', $users2Notify)
            ));

        $vars = array(
            'AUTHOR'        => $uid,
            'THREAD_NAME'   => $thread['subject'],
            'TIME'          => $postInfo['posted'],
            'THREAD_URL'    => $postInfo['thread_url'],
        );

        $nl = "\n";
        //loop thru the users and exec the desired action :D
        foreach($users as $user){
            $message['title'] = langVar('L_THREAD_NOTIFY');
            $message['email'] = parseEmail('E_USER_POSTED', $postInfo);
            $message['notify'] =
                langVar('L_USER_POSTED',
                    $this->objUser->profile($uid, RAW),
                    '[b]'.secureMe($thread['subject']).'[/b]',
                    $this->objTime->mk_time($postInfo['posted'], 'db', $user['timezone'])
                ).$nl.
                'You can view the topic by visiting the following URL: '.
                    '[url=http://'.$_SERVER['HTTP_HOST'].'/'.root().'modules/forum/thread/'.seo($thread['subject']).'-'.$threadId.'.html?mode=last_page]Here[/url]';

            doNotification($user['id'], 'forum', 'forumReplies', $message);
        }
    }


    /**
     * Outputs a parsed post
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     */
    public function preview(){
        if(!doArgs('post_val', false, $_POST)){ die(); }

        $this->objTPL->set_filenames(array(
            'preview' => 'modules/forum/template/forum_preview.tpl'
        ));

        $this->objTPL->assign_vars(array(
            'L_PREVIEW' => langVar('L_PREVIEW'),
            'F_PREVIEW' => contentParse($_POST['post_val']),
        ));

        $this->objTPL->parse('preview');
        exit;
    }

    /**
     * Sets up some select boxes for the forums post page.
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string    $type
     *
     * @return  string
     */
    public function genSelects($type){
        $bcode='';

        switch($type){
            case 'code':
                $_langs = array('php', 'c', 'cpp', 'csharp', 'perl', 'vb', 'html', 'text', 'python', 'ruby', 'asm', 'pascal');
                sort($_langs);
                $return = "<select onchange=\"addText('post', '[code=\\'' + this.options[this.selectedIndex].value + '\\']', '[/code]');this.selectedIndex=0;\">\n
                <option value=\"text\" selected>Select a Lang</option>";
                foreach($_langs as $clang){$return .= "<option value=\"$clang\">".ucwords($clang)."</option>";}
                $return .= "</select>";

            break;
            case 'color':
                $colors = array('maroon', 'red', 'orange', 'brown', 'yellow', 'green',
                'lime', 'olive', 'cyan', 'blue', 'navy', 'purple', 'violet', 'black', 'gray', 'silver', 'white');
                sort($colors);
                $return = "<select onchange=\"addText('post', '[color=' + this.options[this.selectedIndex].value + ']', '[/color]');this.selectedIndex=0;\">\n
                <option value=\"text\" selected>Select a Color</option>";
                foreach($colors as $clang){$return .= "<option value=\"$clang\" style=\"color:$clang;\">".ucwords($clang)."</option>";}
                $return .= "</select>";
            break;
        }

        return $return;
    }

    /**
     * Generates a block for the smilies
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     */
    public function generateSmilies(){
        //set some vars up
        $smilies = array(); $added = array();
        $pack = is_empty($this->config('site', 'smilie_pack')) ? $this->config('site', 'smilie_pack') : 'default';
        $smilieDir = cmsROOT.'images/smilies/'.$pack.'/';

        //check to see if the directory exists and has a smilies.txt in
        if(!is_dir($smilieDir) || !is_readable($smilieDir.'smilies.txt')){
            return;
        }

        //read the file and make sure its not empty
        $lines = file($smilieDir.'smilies.txt');
        if(!count($lines)){ return; }

        $_smilie = '<input type="image" src="%s" height="16" width="16" data-code="%s" class="smilie" />';
        $i=0; $columns=4;
        $new = array();
        foreach($lines as $line){
            if($i!=0 && ($i%$columns==0)){
                $smilies[] = $new;
                $new = array();
                $i=0;
            }

            $s = explode(' ', $line);
            if(is_empty($s[0]) || is_empty($s[1])){ continue; }
            if(in_array($s[1], $added)){ continue; }
            $new[$i] = sprintf($_smilie, '/'.root().$smilieDir.$s[1], $s[0]);
            $added[] = $s[1];

            $i++;
        }


        //gah this was a pain in the ass ;p
        foreach($smilies as $code){
            $this->objTPL->assign_block_vars('smilies', array(
                1=> $code[0], 2=> $code[1], 3=> $code[2], 4=> $code[3],
            ));
        }
    }

    /**
     * Returns a formated URL for the threads
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   array     $thread
     *
     * @return  string
     */
    public function generateThreadURL($thread){
        if(!is_array($thread) || is_empty($thread)){
            return null;
        }
        return '/'.root().'modules/forum/thread/'.seo($thread['subject']).'-'.$thread['id'].'.html';
    }

    /**
     * Returns the list of authors along with their post counts
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   array     $uids
     *
     * @return  int
     */
    public function getPostCounts($uids){
        if(!is_array($uids)){
            $uids = array($uids);
        }
        $authors = array_unique($uids);

        foreach($authors as $k => $v){
            if(!is_number($v)){
                unset($authors[$k]);
            }
        }

        $users = $this->objSQL->getTable(
            'SELECT u.id, COUNT(DISTINCT p.id) AS post_count
                FROM `$Pusers` u, `$Pforum_posts` p, `$Pforum_threads` t, `$Pforum_cats` c
                    WHERE u.id IN ( %s )
                        AND p.author = u.id
                        AND p.thread_id = t.id
                        AND t.cat_id = c.id
                        AND c.postcounts = 1
                GROUP BY u.id',
            array(implode(', ', $authors))
        );

        $return = array();
        foreach($users as $user){
            $return[$user['id']] = $user['post_count'];
        }

        return $return;
    }

    /**
     * Figures out Combined Posts counts for $cat and subs /
     *         Figured out which 'last post' to show from the sub cats
     *
     * @version 1.0
     * @since   0.8.0
     * @author  xLink
     *
     * @param   int         $cat    ID of the parent category
     * @param   string      $mode
     *
     * @return  int/array   int if $mode == (post || thread), array if $mode == last_post
     */
    private function modCat($cat, $mode){
        //make sure we have the right mode
        $mode_ary = array('post', 'thread', 'last_post');
        if(!in_array(strtolower($mode), $mode_ary)){
            $mode = 'post';
        }

        //check if there is children categories for this cat
        $subs = $this->getForumInfo($cat['id'], true);

        //k so we need to use this func for a few things..
        switch($mode){
            case 'post':
            case 'thread':
                $count = 0;
                //can this user see this? if yes, increase the postcount
                if($this->auth[$cat['id']]['auth_view']){
                    $count += $cat[$mode.'_count'];
                    //is there any subcat?
                    if(!is_empty($subs)){
                        foreach($subs as $subCat){
                            if($this->auth[$subCat['id']]['auth_view']){
                                $count += $this->modCat($subCat, $mode);
                            }
                        }
                        return $count;
                    }
                }
                return $count;
            break;

            //thanks to children categories etc we need to figure out if any of those have a post newer than we have in this cat
            case 'last_post':
                $return = array( 'last_post'     => 0,
                                 'last_author'   => 0,
                                 'thread_name'   => NULL,
                                 'post_time'     => 0 );

                //can this user see this?
                if($this->auth[$cat['id']]['auth_view']){
                    $return = array( 'last_post'     => $cat['tid'],
                                     'last_author'   => $cat['last_author'],
                                     'thread_name'   => $cat['thread_name'],
                                     'post_time'     => $cat['last_posted'] );

                    if (!is_empty($subs)){
                        foreach($subs as $subCat){
                            if($this->auth[$subCat['id']]['auth_view']){
                                if($subCat['last_poster']!==NULL && $subCat['last_poster'] >= $return['post_time']){
                                    $return = array( 'last_post'     => $subCat['tid'],
                                                     'last_author'   => $subCat['last_author'],
                                                     'thread_name'   => $subCat['thread_name'],
                                                     'post_time'     => $subCat['last_posted'] );
                                }
                            }
                        }
                        return $return;
                    }
                }
                return $return;
            break;
        }
    }

    /**
     * Sets Breadcrumbs from this category to its highest grandparent.
     *
     * @version 1.3
     * @since   0.8.0
     * @author  xLink
     *
     * @param   int     $id        ID of the category to start from
     */
    private function getSubCrumbs($id){
        //set some vars
        $count = 0; $countArray = array();

        //get the current category
        $query = $this->getForumInfo($id);

        //and add it to the breadcrumb array
        $b[$count++]    = $query[0];
        $countArray[]   = $query[0]['id'];

        //and then loop back through the cats till we have no parent id
        while($query[0]['parent_id'] != 0){
            //grab the parent cat
            $query = $this->getForumInfo($query[0]['parent_id']);

            //and add it to the array
            $b[$count++]     = $query[0];
            $countArray[]     = $query[0]['id'];

            if(in_array($query['id'], $countArray)){
                $query['parent_id'] = 0; break;
            }
        }

        //reverse $b and add the info gained to the
        $b = array_reverse($b); $crumbs = array();
        foreach($b as $cat){
            $crumbs[] = array('url' => '/'.root().'modules/forum/'.seo($cat['title']).'-'.$cat['id'].'/', 'name' => $cat['title']);
        }
        $this->objPage->addPagecrumb($crumbs);
    }

    /**
     * Generates a ACL list for categories
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   constant    $type
     * @param   int         $forum_id
     *
     * @return  array
     */
    public function auth($type, $forum_id, $f_access=NULL){

        switch($type){
            case AUTH_ALL:
                $a_sql = 'a.auth_view, a.auth_read, a.auth_post, a.auth_reply, a.auth_edit, a.auth_del, a.auth_move, a.auth_special';
                $auth_fields = array('auth_view', 'auth_read', 'auth_post', 'auth_reply', 'auth_edit', 'auth_del','auth_move', 'auth_special');
            break;

            case AUTH_VIEW:       $a_sql = 'a.auth_view';       $auth_fields = array('auth_view');              break;
            case AUTH_READ:       $a_sql = 'a.auth_read';       $auth_fields = array('auth_read');              break;
            case AUTH_POST:       $a_sql = 'a.auth_post';       $auth_fields = array('auth_post');              break;
            case AUTH_REPLY:      $a_sql = 'a.auth_reply';      $auth_fields = array('auth_reply');             break;
            case AUTH_EDIT:       $a_sql = 'a.auth_edit';       $auth_fields = array('auth_edit');              break;
            case AUTH_DELETE:     $a_sql = 'a.auth_del';        $auth_fields = array('auth_del');               break;
            case AUTH_MOVE:       $a_sql = 'a.auth_move';       $auth_fields = array('auth_move');              break;
            case AUTH_SPECIAL:    $a_sql = 'a.auth_special';    $auth_fields = array('auth_special');           break;
            default:                                                                                            break;
        }

        //check if we need to return perms for a specific forum or the entire lot
        if(empty($f_access)){
            if(!isset($this->authQuery[$type][$forum_id])){
                $forum_match_sql = ($forum_id != AUTH_LIST_ALL ? 'WHERE a.id = '.$forum_id : '');
                $sql = 'SELECT a.id, %s FROM `$Pforum_cats` a %s';
                    $function = ($forum_id != AUTH_LIST_ALL ? 'getLine' : 'getTable');
                    if(!($this->authQuery[$type][$forum_id] = $f_access = $this->objSQL->$function($sql, array($a_sql, $forum_match_sql)))){
                        $this->objSQL->freeResult($f_access);
                        return array();
                    }
                $this->objSQL->freeResult($f_access);
            }else{
                $f_access = $this->authQuery[$type][$forum_id];
            }
        }

        // If the user isn't logged on then all we need do is check if the forum
        // has the type set to ALL, if yes they are good to go, if not then they
        // are denied access
        $u_access = array();
        if(user::$IS_ONLINE){
            if(!isset($this->authQuery2[$type][$forum_id])){
                if(!isset($this->authQuery3)){
                    $this->authQuery3 = $query = $this->objSQL->getTable(
                        'SELECT a.cat_id, %s, a.auth_mod
                            FROM `$Pforum_auth` a, `$Pgroup_subs` ug
                            WHERE ug.uid = "%s"
                                AND ug.pending = 0
                                AND a.group_id = ug.gid',
                           array($a_sql, $this->objUser->grab('id'))
                    );

                    if($query===false){
                        hmsgDie('FAIL', 'Error: Cannot retreive the forum authorization');
                    }
                }else{
                    $query = $this->authQuery3;
                }

                if(count($query)){
                    foreach($query as $row){
                        if($forum_id != AUTH_LIST_ALL){
                            $u_access[] = $row;
                        }else{
                            $u_access[$row['cat_id']][] = $row;
                        }
                    }
                }
                $this->authQuery2[$type][$forum_id] = $u_access;
                $this->objSQL->freeResult($query);
            }else{
                $u_access = $this->authQuery2[$type][$forum_id];
            }
        }

        $is_admin = (User::$IS_ONLINE && User::$IS_ADMIN) ? true : 0;

        $auth_user = array();
        $icount = count($auth_fields);
        for($i = 0; $i < $icount; $i++){
            $key = $auth_fields[$i];

            if($forum_id != AUTH_LIST_ALL){
                $value = $f_access[$key];

                switch($value){
                    case AUTH_ALL:
                        $auth_user[$key] = true;
                        $auth_user[$key.'_type'] = langVar('L_Auth_Anonymous_Users');
                    break;
                    case AUTH_REG:
                        $auth_user[$key] = User::$IS_ONLINE ? true : 0;
                        $auth_user[$key.'_type'] = langVar('L_Auth_Registered_Users');
                    break;
                    case AUTH_ACL:
                        $auth_user[$key] = User::$IS_ONLINE ? $this->objUser->checkUserAuth(AUTH_ACL, $key, $u_access, $is_admin) : 0;
                        $auth_user[$key.'_type'] = langVar('L_Auth_Users_granted_access');
                    break;
                    case AUTH_MOD:
                        $auth_user[$key] = User::$IS_ONLINE ? $this->objUser->checkUserAuth(AUTH_MOD, 'auth_mod', $u_access, $is_admin) : 0;
                        $auth_user[$key.'_type'] = langVar('L_Auth_Moderators');
                    break;
                    case AUTH_ADMIN:
                        $auth_user[$key] = $is_admin;
                        $auth_user[$key.'_type'] = langVar('L_Auth_Administrators');
                    break;
                    default:
                        $auth_user[$key] = 0;
                    break;
                }
            }else{
                $kcount = count($f_access);
                for($k = 0; $k < $kcount; $k++){
                    $value = $f_access[$k][$key];
                    $f_fid = $f_access[$k]['id'];
                    $u_access[$f_fid] = isset($u_access[$f_fid]) ? $u_access[$f_fid] : array();

                    switch($value){
                        case AUTH_ALL:
                            $auth_user[$f_fid][$key] = true;
                            $auth_user[$f_fid][$key.'_type'] = langVar('L_Auth_Anonymous_Users');
                        break;
                        case AUTH_REG:
                            $auth_user[$f_fid][$key] = User::$IS_ONLINE ? true : 0;
                            $auth_user[$f_fid][$key.'_type'] = langVar('L_Auth_Registered_Users');
                        break;
                        case AUTH_ACL:
                            $auth_user[$f_fid][$key] = User::$IS_ONLINE ? $this->objUser->checkUserAuth(AUTH_ACL, $key, $u_access[$f_fid], $is_admin) : 0;
                            $auth_user[$f_fid][$key.'_type'] = langVar('L_Auth_Users_granted_access');
                        break;
                        case AUTH_MOD:
                            $auth_user[$f_fid][$key] = User::$IS_ONLINE ? $this->objUser->checkUserAuth(AUTH_MOD, 'auth_mod', $u_access[$f_fid], $is_admin) : 0;
                            $auth_user[$f_fid][$key.'_type'] = langVar('L_Auth_Moderators');
                        break;
                        case AUTH_ADMIN:
                            $auth_user[$f_fid][$key] = $is_admin;
                            $auth_user[$f_fid][$key.'_type'] = langVar('L_Auth_Administrators');
                        break;
                         default:
                            $auth_user[$f_fid][$key] = 0;
                        break;
                    }

                }
            }
        }

        // Is user a moderator?
        if($forum_id != AUTH_LIST_ALL){
            $auth_user['auth_mod'] = User::$IS_ONLINE ? $this->objUser->checkUserAuth(AUTH_MOD, 'auth_mod', $u_access, $is_admin) : 0;
        }else{
            for($k = 0; $k < count($f_access); $k++){
                $f_fid = $f_access[$k]['id'];
                $u_access[$f_fid] = isset($u_access[$f_fid]) ? $u_access[$f_fid] : array();
                $auth_user[$f_fid]['auth_mod'] = User::$IS_ONLINE ? $this->objUser->checkUserAuth(AUTH_MOD, 'auth_mod', $u_access[$f_fid], $is_admin) : 0;
            }
        }

        return $auth_user;
    }

    /**
     * Builds the multidimensional array
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   array    $paths
     * @param   int     $id
     *
     * @return  string
     */
    public function buildJumpBoxArray($blank=array()){
        //grab a copy of the entire cat table, grabbing the data we need
        $cats = $this->getForumInfo('*');

        //rearrange the query
        $newQuery = array();
        if(is_array($blank) && count($blank)){
            $newQuery[0] = $blank;
        }
            //first pass for master cats
            foreach($cats as $cat){
                if($cat['parent_id'] != 0){ continue; }
                $newQuery[$cat['id']] = $cat;
            }
                $this->catTitles = $newQuery;

            //second pass for the rest
            foreach($cats as $cat){
                if($cat['parent_id'] ==0 ){ continue; }
                $newQuery[$cat['id']] = $cat;
            }
                $this->catQuery = $newQuery;

        $auth = $this->auth;
        if(is_array($blank) && count($blank)){
            $auth[0]['auth_view'] = true;
        }

        $a = array(); $cats = $this->catQuery;
        //for each parent cat
        foreach($cats as $cat){
            if(!$auth[$cat['id']]['auth_view']){ continue; }
            if($cat['parent_id']>0 && !$auth[$cat['parent_id']]['auth_view']){ continue; }
            //this is a parent cat so just add it to the array
            if($cat['parent_id'] == 0){
                $a[$cat['id']] = array();
                continue;
            }

              //this isnt a parent cat so do some upgrades...
            if($cat['parent_id'] != 0){
                $id = array_searchRecursive((int)$cat['parent_id'], $a);
                $id = $this->buildArrayPath($id, $cat['id']);

                if(!$id){ $id = '$a['.$cat['id'].']'; }

                eval("$id = array(\$cat['title']);");
              }
        }

        return $a;
    }

    /**
     * A custom function to build a select box, specifically for the jumpbox
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string  $name
     * @param   array   $options
     * @param   bool    $selected
     * @param   bool    $allowMasters        Allow the Root Forums to be selected?
     *
     * @return  string
     */
    public function buildJumpBox($name, $options, $selected=null, $allowMasters=true){

        $val = '<select name="'.$name.'" id="'.$name.'" class="chzn-select">'."\n";
        foreach ($options as $k => $v){
            $j=0; $title = str_replace(array('\''), array(''), $this->catTitles[$k]['title']);
            if($allowMasters){
                $val .= '<optgroup label=\'----------\'>'."\n";
                $val .= '<option value=\''.$k.'\''.($k==$selected ? "selected='true'" : '').'>'.
                            '&nbsp;'.$title.
                        '</option>'."\n";
                $val .= '<optgroup label=\'----------\'>'."\n";
            }else{
                $val .= '<optgroup label="'.$title.'">'."\n";
            }
            if(is_array($v)){ $val .= self::processSelect($v, $selected, $noKeys, $j++); }
        }
        $val .= '</select>'."\n";
        return $val;
    }

    /**
     * A recursive function for generating the select box options
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   array       $options
     * @param   string      $selected
     * @param   bool        $noKeys
     * @param   int         $repeat
     * @param   int         $i
     * @param   int         $ki
     *
     * @return  string
     */
    private function processSelect($options, $selected, $noKeys=false, $repeat=0, $i=0, $ki=0){
        if(!is_array($options)){ return false; }

        foreach ($options as $k => $v){
            if(is_array($v)){
                foreach($v as $a => $b){
                    if(is_array($b)){
                        $val .= self::processSelect($b, $selected, $noKeys, $repeat+1, $i+1, $a);
                    }else{
                        $val .= '<option value="'.$k.'"'.($k==$selected ? 'selected="true"' : NULL).'>'.
                                    str_repeat('&nbsp;', $repeat+$i+1).'&#9500; '.$b.
                                '</option>'."\n";

                    }
                }
            }else{
                $val .= '<option value="'.($k==0 ? $ki : $k).'"'.(($k==0 ? $ki : $k)==$selected ? 'selected="true"' : '').'>'.
                            str_repeat('&nbsp;', $repeat+$i).'&#9492;'.str_repeat('-', $repeat).' '.$v.
                        '</option>'."\n";
            }
        }

        return $val;
    }

    /**
     * Builds a path for the multi dimensional array the jumpbox uses
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   array   $paths
     * @param   int     $id
     *
     * @return  string
     */
    private function buildArrayPath($paths=array(), $id=0){
        if(!is_array($paths)){ return false; }

        $return = '$a'; $x=0;
        foreach($paths as $path){
            $return .= '['.$path.']';

            if($x++ == count($paths)-1 && $id!=0){
                $return .= '['.$id.']';
            }
        }
        return $return;
    }


    /**
     * Performs action based on $action
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string    $action
     */
    public function doAjax($action){
        if(is_empty($action)){ $this->throwHTTP(500); return false; }

        switch($action){
            case 'sortForum':
                parse_str($_POST['order'], $order);
                    if(!is_array($order) || !count($order)){ $this->throwHTTP(401); }
                parse_str($_POST['state'], $state);
                    if(!is_array($state) || !count($state)){ $this->throwHTTP(401); }

                if(!is_array($order['sortable_forums']) || !count($order['sortable_forums'])){ $this->throwHTTP(401); }

                foreach($order['sortable_forums'] as $k){
                    $go[$k] = $state[$k]==1 ? 1 : 0;
                }

                echo print_r($go, true);

                $db = serialize($go);
                $_SESSION['user']['forum_cat_order'] = $db;
                $update['forum_cat_order'] = $db;
                $this->objUser->updateUserSettings($this->objUser->grab('id'), $update);
            break;

            //edit in place stuff
            case 'eip':
                $id = doArgs('id', 0, $_GET, 'is_number');
                $uid = $this->objUser->grab('id');

                if($id==0 || !User::$IS_ONLINE){
                    die('Error: There was a problem with the form you submitted. Please try again.');
                }

                //grab the post were reffering to
                $post = $this->objSQL->getLine('SELECT * FROM `$Pforum_posts` WHERE id ="%s" LIMIT 1;', array($id));
                    if(!$post){ die('Error: There was a problem obtaining the post data. Error 0x01;'); }

                //grab the required thread so we got something to work with..
                $thread = $this->objSQL->getLine('SELECT id, cat_id FROM `$Pforum_threads` WHERE id ="%s" LIMIT 1;', array($post['thread_id']));
                    if(!$thread){ die('Error: There was a problem obtaining the post data. Error 0x02;'); }

                //now grab the cat id..
                $cat = $this->getForumInfo($thread['cat_id']);
                    if(!$cat){ die('Error: There was a problem obtaining the post data. Error 0x03;'); }

                $catAuth = $this->auth[$cat['id']];

                if($post['author']!=$uid && !$catAuth['auth_edit'] && !$catAuth['auth_mod'] && !IS_MOD){
                    die('Error: This is not your post;');
                }

                //load or save?
                $action = doArgs('action', false, $_GET);
                if($action == 'load'){
                    echo html_entity_decode($post['post']);
                }else if($action == 'save'){
                    //what we have dosent match whats its supposed to be
                    if(doArgs('editorId', false, $_POST) != 'post_id_'.$id){
                        die('Error: There was a problem with the form you submitted.');
                    }

                    unset($update);
                    $update['post'] = secureMe($_POST['value']);
                    $update['edited'] = $post['edited']+1;
                    $update['edited_uid'] = $uid;

                        $post_update = $this->objSQL->updateRow('forum_posts', $update, array('id ="%d"', $id));

                    if($post_update){
                        contentParse($_POST['value'], true);
                        exit;
                    }else{
                        die('Error: This is not your post, or there was a problem with saving the post. Error 0x02;');
                    }
                }
            break;

            case 'quote':
                $id = doArgs('id', 0, $_GET, 'is_number');
                $uid = $this->objUser->grab('id');

                if($id==0 || !User::$IS_ONLINE){
                    die('Error: There was a problem with the form you submitted. Please try again.');
                }

                //grab the post were reffering to
                $post = $this->objSQL->getLine('SELECT * FROM `$Pforum_posts` WHERE id ="%s" LIMIT 1;', array($id));
                    if(!$post){ die('Error: There was a problem obtaining the post data. Error 0x01;'); }

                //grab the required thread so we got something to work with..
                $thread = $this->objSQL->getLine('SELECT id, cat_id FROM `$Pforum_threads` WHERE id ="%s" LIMIT 1;', array($post['thread_id']));
                    if(!$thread){ die('Error: There was a problem obtaining the post data. Error 0x02;'); }

                //now grab the cat id..
                $cat = $this->getForumInfo($thread['cat_id']);
                    if(!$cat){ die('Error: There was a problem obtaining the post data. Error 0x03;'); }

                $catAuth = $this->auth[$cat['id']];

                if(!$catAuth['auth_read'] && !$catAuth['auth_mod'] && !IS_MOD){
                    die('Error: This is not your post;');
                }

                $quote = "\n[quote=%s]\n%s\n[/quote]\n";
                echo sprintf($quote, $this->objUser->getUserInfo($post['author'], 'username'), $post['post']);
            break;

        }
        //everything that happens here dosent need to be output back to the parent template
        exit;
    }
}
?>