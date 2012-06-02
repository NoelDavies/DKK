<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/

function menu_wio($args){
    global $config, $objCore, $objSQL, $objUser, $objTime, $objTPL;
    if(defined('NO_DB')){ return; }
    $objTPL->set_filenames(array(
        $args['uniqueId'] => 'modules/core/template/blocks/block_wio.tpl'
    ));

        $expiry = $objTime->mod_time(time(), 0, 20, 0, 'TAKE');
        $query = $objSQL->getTable('SELECT DISTINCT uid, ip_address FROM `$Ponline` WHERE %d < timestamp', array($expiry));
            if(!count($query)){
                $objTPL->assign_vars(array(
                   'USERS_ONLINE'  => langVar('L_USERS_ONLINE'),
                   'USERS'         => 'Error: Could not query table',
                ));
                return $objTPL->get_html($args['uniqueId']);
            }

        $group_count = 0;
        $bots_online = array();
        $users = array();
        foreach($query as $user){
            if($user['uid']!=GUEST){
                $users[] = $objUser->profile($user['uid']);
            }
        }
           if(count($users)){
               $users = array_unique($users);
           }else{
               $users = array(langVar('L_NO_ONLINE_USERS'));
           }

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
                $key .= '<font style="color: '.$group['color'].'" class="username '.strtolower($group['name']).'" title="'.$group['description'].'">'.$group['name'].'</font>'.$add;

                $counter++;
            }
        }

    $objTPL->assign_vars(array(
       'USERS_ONLINE'  => langVar('L_USERS_ONLINE'),
       'USERS'         => implode(', ', $users),

       'L_KEY'         => langVar('L_KEY'),
       'GROUPS'        => $key,
    ));

    return $objTPL->get_html($args['uniqueId']);
}

function menu_affiliates($args){
    if(defined('NO_DB')){ return; }
    global $objTPL, $objSQL;

    $settings = array(
        'limit' => doArgs('limit', 6, $args),
        'perRow' => doArgs('limit', 2, $args),
    );

    //grab the table
    $table = $objSQL->getTable('SELECT * FROM `$Paffiliates` WHERE active = 1 AND showOnMenu = 1 ORDER BY rand() LIMIT %d;', array($settings['limit']));
        if($table===NULL){ return 'Error: Could not query Affiliates.'; }
        if(is_empty($table)){ return 'Error: No Affiliates in the database active.'; }

    $return = NULL;$counter = 1;
    foreach($table as $a){
        $title = secureMe($a['title']).'
            In: '.$a['in'].' | Out: '.$a['out'];
        $return .= '<a href="/'.root().'affiliates.php?out&id='.$a['id'].'" title="'.$title.'" target="_blank" rel="nofollow"><img src="'.$a['img'].'" alt="'.$title.'" /></a>';

        if($counter % $settings['perRow']==0){ $return .= '<br />'; }

        $counter++;
    }

    return '<center>'.$return.'</center>';
}

function menu_login($args){
    global $objCore, $objUser, $objTPL, $objPage, $objForm, $objTime, $config, $objSQL;

    if(!User::$IS_ONLINE){
        $objTPL->set_filenames(array(
            $args['uniqueId'] => 'modules/core/template/blocks/block_login.tpl'
        ));

            //check see if we are allowing auto_login precedures
            if($objCore->config('login', 'remember_me')){ $objTPL->assign_block_vars('remember_me', array()); }
            $hash = md5(time().'userkey');
            $_SESSION['login']['cs_hash'] = $hash;

            $objTPL->assign_vars(array(
                'FORM_START'         => $objForm->start('login', array('method' => 'POST', 'action' => '/'.root().'login.php?action=check')),
                'FORM_END'            => $objForm->inputbox('hash', 'hidden', $hash) . $objForm->finish(),

                'F_USERNAME'        => $objForm->inputbox('username', 'text', $userValue, array('class'=>'icon username', 'br'=>true, 'disabled'=>$acpCheck, 'required'=>true)),
                'F_PASSWORD'        => $objForm->inputbox('password', 'password', '', array('class'=>'icon password', 'br'=>true, 'required'=>true)),
                'F_REMME'            => $objForm->select('remember', array('0'=>'No Thanks', '1'=>'Forever'), array('selected'=>0)),

                'L_USERNAME'         => langVar('L_USERNAME'),
                'L_PASSWORD'         => langVar('L_PASSWORD'),
                'L_REMME'            => langVar('L_REMME'),

                  'SUBMIT'            => $objForm->button('submit', 'Login'),
                'RESET'                => $objForm->button('reset', 'Reset Form'),
                'REGISTER'          => $objForm->button('register', 'Register',
                                            array('extra'=> 'onclick="document.location = \'/'.root().'register.php\'; return false;"')),
                'FORGOT_PWD'        => $objForm->button('forgot_pwd', 'Forgot Password',
                                            array('extra'=> 'onclick="inWindow(\'/'.root().'forgotpass.php?ajax\', \'Forgot Password\', 600, 500); return false;"')),
            ));

    }else{
        $objTPL->set_filenames(array(
            $args['uniqueId'] => 'modules/core/template/blocks/block_logout.tpl'
        ));

        $user = $objUser->profile($objUser->grab('id'));

        $objTPL->assign_vars(array(
            'L_LAST_VISIT'      => langVar('LAST_VISIT', $objTime->mk_time($objUser->grab('timestamp'))),
            'L_LOGOUT_BTN'        => '<a href="/'.root().'login.php?action=logout&check='.$objUser->grab('usercode').'" class="button">'.langVar('L_LOGOUT').'</a>',
        ));

    }

    return $objTPL->get_html($args['uniqueId']);
}


?>