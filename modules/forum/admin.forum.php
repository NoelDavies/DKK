<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

class forum extends Module{

    public function doAction($action){
        $this->objPage->addPagecrumb(array(
            array('url' => '/'.root().'admin/forum/', 'name' => langVar('B_FORUM_ADMIN')),
        ));
        $this->autoLoadModule('forum', $this->objForum);

        if(preg_match('_setup/edit/_i', $action)){ $action = 'modifyCat'; }
        if(preg_match('_setup_i', $action)){ $action = 'setup'; }
        if(preg_match('_config_i', $action)){ $action = 'config'; }
        if(is_empty($action)){ $action = 'index'; }

        switch(strtolower($action)){
            case 'index':
                $this->showIndex();
            break;

            case 'config':
                $this->configForum();
            break;

            case 'setup':
                $this->categoryManagement();
            break;

            case 'modifycat':
                $this->categoryModify();
            break;

            default:
            case 404:
                $this->throwHTTP(404);
            break;
        }
    }

    public function showIndex(){
        $counter = 0; $columns = 2;
        $links = array(
            array('url' => '/'.root().'admin/forum/config/',           'name' => langVar('L_CONFIG')),
            array('url' => '/'.root().'admin/forum/setup/',            'name' => langVar('L_CAT_MANAGE')),
            array('url' => '/'.root().'admin/forum/group/?mode=group', 'name' => langVar('L_GROUP_PERMS')),
            array('url' => '/'.root().'admin/forum/group/?mode=user',  'name' => langVar('L_USER_PERMS')),
        );

        $this->objTPL->set_filenames(array(
            'body' => 'modules/core/template/admin/defaultIndex.tpl'
        ));
        include 'cfg.php';
        $this->objTPL->assign_var('MODULE', $mod_name);
        foreach($links as $l){
            $this->objTPL->assign_block_vars('view', array(
                'URL'       => $l['url'],
                'COLOR'     => ($counter%2==0 ? 'row_color1' : 'row_color2'),
                'COUNTER'   => $counter,

                'CATNAME'   => $l['name'],
            ));

            if($counter != 0 && ($counter % $columns == 0)){
                $this->objTPL->assign_block_vars('view.rowSplitter', array());
            }
            $counter++;
        }
        $this->objTPL->parse('body', false);
    }

    public function configForum(){
        $this->objPage->addPagecrumb(array(
            array('url' => '/'.root().'admin/forum/config/', 'name' => langVar('L_CONFIG')),
        ));

        $this->objTPL->set_filenames(array(
            'body' => 'modules/core/template/panels/panel.settings.tpl'
        ));

        $yn = array(1 => langVar('L_ENABLED'), 0 => langVar('L_DISABLED'));

        if(!HTTP_POST){
            $this->objForm->outputForm(array(
                'FORM_START'    => $this->objForm->start('panel', array('method' => 'POST', 'action' => '?save')),
                'FORM_END'      => $this->objForm->finish(),

                'FORM_TITLE'    => langVar('L_CONFIG'),
                'FORM_SUBMIT'   => $this->objForm->button('submit', 'Submit'),
                'FORM_RESET'    => $this->objForm->button('reset', 'Reset'),
            ),
            array(
                'field' => array(
                    langVar('L_NEWS_CAT') => $this->objForum->buildJumpBox('news_category', $this->objForum->buildJumpBoxArray(), $this->config('forum', 'news_category'), false),
                    langVar('L_SORTABLES') => $this->objForm->radio('sortables', $yn, $this->config('forum', 'sortable_categories')),
                ),
                'desc' => array(
                    langVar('L_NEWS_CAT') => langVar('L_NEWS_CAT_DESC'),
                    langVar('L_SORTABLES') => langVar('L_SORTABLES_DESC'),
                ),
                'errors' => $_SESSION['site']['panel']['error'],
            ),
            array(
                'header' => '<h4>%s</h4>',
                'dedicatedHeader' => true,
                'parseDesc' => true,
            ));
           }else{
            $update = array();

            $forum = $this->objForum->getForumInfo($_POST['news_category']);
            if(doArgs('news_category', false, $_POST)!=$this->config('forum', 'news_category') && $forum!==false){
                $update['news_category'] = $_POST['news_category'];
            }

            if(doArgs('sortables', false, $_POST)!=$this->config('forum', 'sortable_categories')){
                $update['sortable_categories'] = $_POST['sortables'];
            }

            //make sure we have somethign to update
            if(is_empty($update)){
                $this->objPage->redirect(str_replace('?save', '', $this->config('global', 'url')), 3);
                hmsgDie('FAIL', langVar('L_NO_CHANGES'));
                break;
            }

            //run through and run the update routine
            $failed = array();
            foreach($update as $setting => $value){
                $update = $this->objSQL->updateRow('config', array('value' => $value), array('var = "%s"', $setting));
                if(is_empty($update)){
                    $failed[$setting] = $this->objSQL->error();
                }
            }

            //make sure the update went as planned
            if(!is_empty($failed) || count($failed)){
                $msg = null;
                foreach($failed as $setting => $error){
                    $msg .= $setting.': '.$error.'<br />';
                }
                $this->objPage->redirect(str_replace('?save', '', $this->config('global', 'url')), 3);
                hmsgDie('FAIL', 'Error: Some settings were not saved.<br />'.$msg.'<br />Redirecting you back in 3 seconds.');
                $this->objTPL->parse('body', false); break;
            }
            $this->objCache->regenerateCache('config');
            $this->objPage->redirect(str_replace('?save', '', $this->config('global', 'url')), 3);
            hmsgDie('INFO', 'Successfully updated settings. Returning you to the panel.');
        }
        $this->objTPL->parse('body', false);
       }

    public function categoryManagement(){
        $this->objPage->addPagecrumb(array(
            array('url' => '/'.root().'admin/forum/setup/', 'name' => langVar('L_CAT_MANAGE')),
        ));

        $this->objTPL->set_filenames(array(
            'body' => 'modules/forum/template/admin/panel.category_management.tpl'
        ));

        $this->objTPL->assign_vars(array(
            'JB_TITLE'          => langVar('L_CAT_MANAGE'),
            'JUMPBOX'           => $this->objForum->buildJumpBox('id', $this->objForum->buildJumpBoxArray()),
            'JB_FORM_START'     => $this->objForm->start('admin', array('method' => 'GET', 'action' => '/'.root().'admin/forum/setup/edit/')),
            'JB_FORM_END'       => $this->objForm->finish(),
            'JB_EDIT'           => $this->objForm->button('submit', langVar('L_EDIT'), array('name'=>'mode')),
            'JB_DELETE'         => $this->objForm->button('submit', langVar('L_DELETE'), array('name'=>'mode')),

            'HID_INPUT'         => $this->objForm->inputbox('id', 'hidden', '0'),
            'ADD_FORM_START'    => $this->objForm->start('admin', array('method' => 'GET', 'action' => '/'.root().'admin/forum/setup/edit/')),
            'ADD_FORM_END'      => $this->objForm->finish(),
            'ADD_SUBMIT'        => $this->objForm->button('submit', langVar('L_NEW_CAT')),
        ));

        $this->objTPL->parse('body', false);
    }

    public function categoryModify(){
        //grab the ID, if its set to 0 then we want to add a category
        $id = doArgs('id', -1, $_GET, 'is_number');
            if($id == -1){ hmsgDie('FAIL', 'Error: Invalid ID passed.'); }

        //grab the forum category
        if($id != 0){
            $cat = $this->objForum->getForumInfo($id);
                if(!$cat){ hmsgDie('FAIL', 'Error: Could not find category by ID'); }
            $cat = $cat[0];
        }else{
            $cat = array(
                'title'         => '',
                'parent_id'     => 0,
                'desc'          => '',
                'auth_view'     => 0,
                'auth_read'     => 0,
                'auth_post'     => 0,
                'auth_reply'    => 0,
                'auth_edit'     => 0,
                'auth_del'      => 0,
                'auth_move'     => 0,
                'auth_special'  => 0,
                'auth_mod'      => 0,
            );
        }

        $this->objPage->setTitle(langVar($id!=0 ? 'L_EDIT_CAT' : 'L_ADD_CAT'));
        $this->objPage->addPagecrumb(array(
            array('url' => '/'.root().'admin/forum/setup/', 'name' => 'Category Management'),
            array('url' => $_url, 'name' => langVar($id!=0 ? 'L_EDIT_CAT' : 'L_ADD_CAT')),
        ));

        if(!HTTP_POST){
            $this->objPage->addJSFile('/'.root().'modules/forum/scripts/admin_catEdit.js');
            $this->objTPL->set_filenames(array(
            	'body' => 'modules/forum/template/admin/panel.edit_category.tpl'
            ));

                $permList = array();
                $permList['0'] = 'Everyone';
                $permList['1'] = 'Registered Only';
                $permList['2'] = 'With Permission';
                $permList['3'] = 'Moderators Only';
                $permList['5'] = 'Admin Only';

                $field_names = array(
                    'auth_view'     => array('View',    'Determine whether it is visible on listings.'),
                    'auth_read'     => array('Read',    'Determine whether this categories contents are readable.'),
                    'auth_post'     => array('Post',    'Determine if this category can be posted to.'),
                    'auth_reply'    => array('Reply',   'Determine if the threads in this category can be replied to.'),
                    'auth_edit'     => array('Edit',    'Deternine if the threads in this category can be editable.'),
                    'auth_del'      => array('Delete',  'Deternine if the threads in this category can be deleted.'),
                    'auth_move'     => array('Move',    'Deternine if the threads in this category can be moved.'),
                    'auth_special'  => array('Special', 'Determine who has the ability to add special items(attachments, polls, etc) to a thread/post.'),
                    'auth_mod'      => array('Moderate','Determine who gets to moderate this category.')
                );

                $perms = NULL; $j = 0;
                $img = '/'.root().'images/icons/help.png';
                foreach($cat as $k=>$v){
                    $match = preg_match('/auth_([a-zA-Z]*)/is', $k, $m);
                        if(!$match){ continue; }

                    $perms .= '<td><div class="float-left"><img src="'.$img.'" alt="'.$field_names[$m[0]][1].'" title="'.$field_names[$m[0]][1].'" />'.
                                $field_names[$m[0]][0].':</div>'.
                                '<div class="float-right">'.$this->objForm->select($m[0], $permList, array('fancy'=>false, 'extra'=>'data-js="changeme"')).'</div></td>';
                    if($j++ == 4){ $j=0; $perms.='</tr><tr>'; }
                }

                //this var handles the quick permission select box, this determines
                    //  View      Read          Post        Reply       Edit        Delete          Move      Special       Moderate
                $simple_auth_array = array(
                '01'=>'Change Me',

                    AUTH_ALL.','.AUTH_ALL.','.AUTH_ALL.','.AUTH_ALL.','.AUTH_REG.','.AUTH_REG.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD
                        => 'Everyone',

                    AUTH_ALL.','.AUTH_ALL.','.AUTH_REG.','.AUTH_REG.','.AUTH_REG.','.AUTH_REG.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD
                        => 'Registered',

                    AUTH_REG.','.AUTH_REG.','.AUTH_REG.','.AUTH_REG.','.AUTH_REG.','.AUTH_REG.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD
                        => 'Registered [ Hidden ]',

                    AUTH_ALL.','.AUTH_ACL.','.AUTH_ACL.','.AUTH_ACL.','.AUTH_ACL.','.AUTH_ACL.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD
                        => 'With Permission',

                    AUTH_ACL.','.AUTH_ACL.','.AUTH_ACL.','.AUTH_ACL.','.AUTH_ACL.','.AUTH_ACL.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD
                        => 'With Permission [ Hidden ]',

                    AUTH_ALL.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD
                        => 'Moderators',

                    AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD
                        => 'Moderators [ Hidden ]',

                '02'=>'---',

                    AUTH_ALL.','.AUTH_ALL.','.AUTH_MOD.','.AUTH_REG.','.AUTH_REG.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD.','.AUTH_MOD
                        => 'News Category',

                );

                $this->objTPL->assign_vars(array(
        			'L_EDITING_CAT'     => langVar(($id != 0 ? 'L_EDIT_CAT' : 'L_ADD_CAT')),
        			'FORM_START' 	    => $this->objForm->start('admin', array('method' => 'POST', 'action' => '/'.root().'admin/forum/setup/edit/?action=save&id='.$id)),
        			'FORM_END'		    => $this->objForm->finish(),

                    'L_CAT_NAME'        => 'Category Name',
                    'CAT_NAME'          => $this->objForm->inputbox('title', 'input', $cat['title'], array('extra'=>'style="width:99%"')),

                    'L_CAT_DESC'        => 'Category Desc',
                    'CAT_DESC'          => $this->objForm->textarea('desc', $cat['desc'], array('extra'=>'style="width:99%"','rows'=>'3')),

                    'L_CAT_ATTACH'      => 'Attach Forum To',
                    'CAT_ATTACH'        => $this->objForum->buildJumpBox('parent_id', $this->objForum->buildJumpBoxArray(array('id'=>0,'title'=>'Forum Root')), $cat['parentid']),

                    'L_CAT_PERMS'       => 'Category Default Permissions',
                    'CAT_PERMS'         => $perms,

                    'L_QUICK_PERMS'     => 'Quick Swap Perms',
                    'QUICK_PERMS'       => $this->objForm->select('quick_perms', $simple_auth_array, array('fancy'=>false)),

        			'SUBMIT'			=> $this->objForm->button('submit', 'Save'),
        			'RESET'			    => $this->objForm->button('reset', 'Reset'),
        		));

            $this->objTPL->parse('body', false);
        }else{


            $cats = $this->objSQL->getTable('SELECT id FROM `$Pforum_cats`');
                #if(!$cats){ hmsgDie('FAIL', 'Error: Could not request forum categories.'); }
            $catRange = array(0); //set a default of 0, for the new "Master Cat"
                if($cats){ foreach($cats as $cat){ $catRange[] = $cat['id']; } }

            $authRange = range(0,5);
            $needed = array(
                'title'         => 'string',
                'parentid'      => $catRange,
                'desc'          => 'string',
                'auth_view'     => $authRange,
                'auth_read'     => $authRange,
                'auth_post'     => $authRange,
                'auth_reply'    => $authRange,
                'auth_edit'     => $authRange,
                'auth_del'      => $authRange,
                'auth_move'     => $authRange,
                'auth_special'  => $authRange,
                'auth_mod'      => $authRange,
            );

            unset($update);
            foreach($needed as $field => $vals){
                //if what we need aint there, just continue
                    if(!isset($_POST[$field])){ continue; }
                //now check if its not an array, then we want to check if its empty
                    if(!is_array($vals) && empty($_POST[$field])){ continue; }
                //its an array, so check if the value from the post, is in the acceptable array
                    if(is_array($vals) && !in_array($_POST[$field], $vals)){ continue; }

                $update[$field] = $_POST[$field];
            }

            if($id!=0){
                $update = $this->objSQL->updateRow('forum_cats', $update, 'id = '.$id, 'Forum: Updated category - '.$update['title']);
                $this->objPage->redirect('/'.root().'admin/forum/setup/edit/?id='.$id, 2);
                    if(!$update){ hmsgDie('FAIL', 'Error: Update Failed.'); }

                hmsgDie('INFO', 'Update Successful.');
            }else{
                $AI = $this->objSQL->getAI('forum_cats');
                $update = $this->objSQL->insertRow('forum_cats', $update, 'Forum: Added new category - '.$update['title']);
                $this->objPage->redirect('/'.root().'admin/forum/setup/edit/?id='.$AI, 2);
                    if(!$update){ hmsgDie('FAIL', 'Error: Adding new category Failed.'); }

                hmsgDie('INFO', 'New Category Added.');
            }
        }
    }

}

?>