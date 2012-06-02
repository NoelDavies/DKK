<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if (!defined('INDEX_CHECK')) die("INDEX_CHECK not defined.");

class profile extends Module{
    
                
    /**
     * This function acts as the messenger between the CMS and this module.
     * 
     * @version    1.0
     * @since   0.8.0 
     */    
    function doAction($action){
        
        if(preg_match('_view/(.*?)($|/)_i', $action, $uid)){
            $action = 'view';
        }
        if(preg_match('_avatar_i', $action)){
            $action = 'avatar';
        }
        if(preg_match('_contactInfo_i', $action)){
            $action = 'css';
        }

        $this->objPage->addPagecrumb(array(
            array('url' => '/'.root().'modules/profile/', 'name' => 'Profile'),
        ));

    //
    //--Begin Switch
    //
        switch(strtolower($action)){
            
            default:
            case 'view':
                $this->showProfile($uid[1]);
            break;
            
            case 'avatar':
                $this->objPage->showHeader(true);
                    include('avatar.php');
                $this->objPage->showFooter(true);
                exit;
            break;
            

            //this one is special, cause its to allow access to a file from within the module folder
            //this usually isnt allowed due to the rewrite module ;)
            case 'title':
                header('Content-Type: application/x-shockwave-flash');
                include('./title.swf');
                exit;
            break;
            
            case 'css':
                $this->contactInfoCSS();
            break;
        }
    }
    
    
    function showProfile($uid){        
        if(!User::$IS_ONLINE){
            hmsgDie('FAIL', 'Error: You must be logged in to view this users profile.');    
        }
        
        if(is_empty($uid) && User::$IS_ONLINE){
            $this->objPage->redirect('/'.root().'modules/profile/view/'.$this->objUser->grab('username'), 0);
            return;
        }
        
        $vars = $this->objPage->getVar('tplVars');
        $user = $this->objUser->getUserInfo($uid);
            if(!count($user)){ 
                $this->objPage->setTitle('Profile > User doesnt exist.');
                hmsgDie('FAIL', 'Error: User doesnt exist.');
            }
        
        $this->objTPL->set_filenames(array(
            'body' => 'modules/profile/template/viewProfile.tpl',
        ));

        $this->objPage->setTitle('Profile > '.$user['username']);
        $this->objPage->addCSSFile('/'.root().'modules/profile/contactInfo.css');
        $this->objPage->addJSFile('/'.root().'modules/profile/scripts/profile.js');
        $this->objPage->addPagecrumb(array(
            array('url' => '/'.root().'modules/profile/view/'.$user['username'], 'name' => 'Viewing '.secureMe($user['username']).'\'s profile'),
        ));
        
        $icons = $this->contactInfoLinks($user);

        $uProfile = $this->objUser->profile($user['id']);
        if(preg_match('_"color: ([^;]*);" title="([^"]*)">([^<]*)</font>_i', $uProfile, $m)){
            $text = $m[2]; $color = $m[1];
        }

        $this->objComments->start('PROFILE_COMMENTS', 'cpage', 'profile', $user['id'], 20, $user['id']);
        $this->objTPL->assign_block_vars('profile', array(
            'USERNAME'      => $uProfile,
            'USERNAME_RAW'  => $user['username'],

            'AVATAR'        => $this->objUser->parseAvatar($user['id']),
            
            'TITLE'         => !is_empty($title) ? secureMe($user['title']) : '<font color="'.$color.'">'.$text.'</font>',
            'PM'            => User::$IS_ONLINE ? '<a href="/'.root().'modules/pm/compose/'.$user['username'].'"><img src="'.$vars['PM_compose'].'" /></a>' : '',
                
            'SIGNATURE'     => contentParse($user['signature']),
            'INTERESTS'     => contentParse($user['interests']),
            'ABOUT_ME'      => contentParse($user['about']),
                
            'L_LOCALTIME'   => 'Local Time',
            'LOCALTIME'     => $this->objTime->mk_time(time(), 'D jS M h:ia', $user['timezone']),

            'CONTACT_ICONS' => $icons,
            
            
            #tabs
            'L_COMMENTS'    => 'Comments',
            'L_RECENTA'     => 'Recent Activity',
            'L_BIO'         => 'User Bio',
        ));
        
        if(!is_empty($user['about'])){ $this->objTPL->assign_block_vars('profile.ABOUT_ME', array()); }
        if(!is_empty($user['interests'])){ $this->objTPL->assign_block_vars('profile.INTRESTS', array()); }
        
        $bio_info = array();
        $bio_info[] = !is_empty($title) 
                        ? array('var'=> 'User Title', 'val'=> secureMe($user['title'])) 
                        : array('var'=> 'User Privs', 'val'=> '<font color="'.$color.'">'.$text.'</font>') ;

        $bio_info[] = array('var'=> 'Registered Since', 'val'=> $this->objTime->mk_time($user['registerdate'], 'l jS F Y @ h:ia'));
        $bio_info[] = array('var'=> langVar('L_LAST_LOGGED_IN'), 'val'=> $this->objTime->mk_time($user['timestamp']));
        
        if($user['birthday'] != '00/00/0000'){
            $ex = explode('/', $user['birthday']);
            $tiem = gmmktime(0, 0, 0, $ex[1], $ex[0], $ex[2]);
            
            $bio_info[] = array('var'=> 'Birthday', 'val'=> $this->objTime->mk_time($tiem, 'D jS M'));
        }
        
        if(!is_empty($location)){
            $bio_info[] = array('var'=> 'Location', 'val'=> $location); 
        }
        
        $i = 0;
        foreach($bio_info as $row){
            $this->objTPL->assign_block_vars('profile.BINFO', array(
                'VAR' => $row['var'], 'VAL' => $row['val'],
                'ROW' => ($i++%2==0 ? 'row_color1' : 'row_color2'),
            ));
        }
        
        $this->objTPL->assign_vars(array(
            'RECENT_ACTIVITY_MSG' => msg('INFO', 'This part of the panel is still in development. Watch this space.', 'return'),
        ));


        $this->objTPL->parse('body', false);
    }
    
    
//
//-- Contact Info Settings
//

    public function contactInfoSettings(){
        $settings = array(
            '---Mail Services',
            'gma' =>     array('ico'=>'gmail.png',       'unique' => false,     'code'=> 'gma',     'name'=> 'Google Mail'),
            'hom' =>     array('ico'=>'msn.png',         'unique' => false,     'code'=> 'hom',     'name'=> 'Hotmail'),
            'yam' =>     array('ico'=>'yahoo.png',       'unique' => false,     'code'=> 'yam',     'name'=> 'Yahoo! Mail'),

            '---Social Services',
            'fb'  =>     array('ico'=>'facebook.png',    'unique' => true,     'code'=> 'fb',      'name'=> 'FaceBook'),
            'gpl' =>     array('ico'=>'g+.png',            'unique' => true,     'code'=> 'gpl',       'name'=> 'Google Plus!'),
            'twi' =>     array('ico'=>'twitter.png',     'unique' => false,     'code'=> 'twi',     'name'=> 'Twitter'),
            'red' =>     array('ico'=>'reddit.png',      'unique' => true,     'code'=> 'red',     'name'=> 'Reddit'),
            'stu' =>     array('ico'=>'stumbleupon.png', 'unique' => true,     'code'=> 'stu',     'name'=> 'StumbleUpon'),
            
            '---Messengers',
            'wlm' =>     array('ico'=>'msn.png',         'unique' => false,     'code'=> 'wlm',     'name'=> 'Windows Live Messenger'),
            'aol' =>     array('ico'=>'aim.png',         'unique' => false,     'code'=> 'aol',     'name'=> 'AOL Instant Messenger'),
            'sky' =>     array('ico'=>'skype.png',       'unique' => false,     'code'=> 'sky',     'name'=> 'Skype'),
            'yah' =>     array('ico'=>'yahoo.png',       'unique' => false,     'code'=> 'yah',     'name'=> 'Yahoo! Messenger'),
            'gt'  =>     array('ico'=>'gtalk.png',       'unique' => false,     'code'=> 'gt',      'name'=> 'Google Talk'),
            'irc' =>     array('ico'=>'mirc.gif',        'unique' => false,     'code'=> 'irc',     'name'=> 'IRC'),

            '---Others',
            'git' =>     array('ico'=>'github.png',      'unique' => false,     'code'=> 'git',       'name'=> 'GitHub'),
            'bbu' =>     array('ico'=>'bitbucket.png',   'unique' => false,     'code'=> 'bbu',       'name'=> 'BitBucket'),
            'ste' =>     array('ico'=>'steam.png',       'unique' => true,     'code'=> 'ste',     'name'=> 'Steam'),
            'spo' =>     array('ico'=>'spotify.png',     'unique' => true,     'code'=> 'spo',     'name'=> 'Spotify'),
            'utb' =>     array('ico'=>'youtube.png',     'unique' => true,     'code'=> 'utb',     'name'=> 'YouTube'),
            'dA'  =>     array('ico'=>'deviantart.png',  'unique' => true,     'code'=> 'dA',      'name'=> 'DeviantArt'),
            'grv' =>     array('ico'=>'grooveshark.png', 'unique' => true,     'code'=> 'grv',     'name'=> 'Grooveshark'),
            
            '---Your Info',
            'urb' =>     array('ico'=>'wordpress.png',   'unique' => true,     'code'=> 'urb',     'name'=> 'Your Blog'),
            'urw' =>     array('ico'=>'link.png',        'unique' => false,     'code'=> 'urw',     'name'=> 'Your Website'),
            'url' =>     array('ico'=>'photo_delete.png','unique' => true,     'code'=> 'url',     'name'=> 'Your Location'),
        );

        $this->objPlugins->hook('CMSProfile_contactInfoSettings', $settings);

        return $settings;
    }
    
    public function contactInfoCSS(){
        header('Content-type: text/css');
        $css = <<<CSS
.ico{ height: 21px; margin: 0 12px 0 14px; background-position: 0 center !important; padding: 0 0 0 20px; }
.label{ padding-left: 25px; width: 30%; margin: 12px; line-height: 22px; cursor: n-resize; }
.formTbl{ width: 100%; border: 0; }
#close{ width: 23px; }
CSS;
        $settings = $this->contactInfoSettings();
        $cssTPL = '.%s { background: url("/'.root().'images/social/%s") no-repeat scroll 5px center transparent; }'."\n";
        foreach($settings as $s){
            if(!is_array($s)){ continue; }
            $css .= sprintf($cssTPL, $s['code'], $s['ico']);
        }

        die($css);
    }
    
    public function contactInfoLinks($user, $filter=null){
        if(is_empty($user['contact_info'])){ return null; }

        $user['contact_info'] = json_decode($user['contact_info'], true);
            if(!is_array($user['contact_info'])){ return null; }
        
        $filter = (!is_empty($filter) ? explode('|', $filter) : array());
        
        //set the tpl and css up
        $this->objPage->addCSSCode('.contactInfo{ padding: 0 20px 1px 0; } .ico{ margin: 0; padding: 0 0 1px 20px; }');
        
        $icons = null;
        foreach($user['contact_info'] as $row){ //continue(2)
            $ico = array();
            $ico['type']        = $row['type'];
            $ico['val']         = secureMe($row['val']);
            $ico['contact']     = 'Click to Visit <strong>'.$ico['val'].'</strong>\'s Profile';
        
            switch($row['type']){ //continue(1)
                //we dont want it processing for anything past what we have so break out of the switch AND the foreach
                default: continue(2); break;
                
                case 'wlm': $blank = false;
                    $ico['url'] = 'msnim:chat?contact='.$ico['val']; $ico['contact'] = 'Windows Live Messenger: '.$ico['contact'];
                break;
                
                case 'aol': $blank = false;
                    $ico['url'] = 'aim:goim?screenname='.$ico['val']; $ico['contact'] = 'AOL Instant Messenger: '.$ico['contact'];
                break;
                
                case 'sky': $blank = false;
                    $ico['url'] = 'skype:'.$ico['val'].'?chat'; $ico['contact'] = 'Skype: '.$ico['contact'];
                break;
                
                case 'yah': $blank = false;
                    $ico['url'] = 'ymsgr:sendIM?'.$ico['val']; $ico['contact'] = 'Yahoo Messenger: '.$ico['contact'];
                break;
                
                case 'gt': $blank = false;
                    $ico['url'] = 'gtalk:chat?'.$ico['val']; $ico['contact'] = 'Google Talk: '.$ico['contact'];
                break;
                
                case 'irc': $blank = false;
                    $ico['url'] = $ico['val']; $ico['contact'] = 'IRC: Click to connect to : '.$ico['val'];
                break;
                
                case 'twi': $blank = false;
                    $ico['url'] = $ico['val']; $ico['contact'] = 'Twitter: '.$ico['contact'];
                break;
                
                
                case 'fb':
                    $ico['url'] = 'http://facebook.com/'.$ico['val']; $ico['contact'] = 'Facebook: '.$ico['contact'];
                break;
                
                case 'gt':
                    $ico['url'] = 'http://twitter.com/'.$ico['val']; $ico['contact'] = 'Twitter: '.$ico['contact'];
                break;
                
                case 'gpl':
                    $allowed = array('plus.google.com', 'gplus.to');
                    if(preg_match('/('.implode('|', $allowed).')/i', $$ico['val'])){
                        $ico['url'] = $ico['val']; $ico['contact'] = 'Facebook: '.$ico['contact'];
                    }
                break;
                
                case 'git':
                    $ico['url'] = 'https://github.com/'.$ico['val']; $ico['contact'] = 'GitHub: '.$ico['contact'];
                break;
                
                case 'bbu':
                    $ico['url'] = 'https://bitbucket.org/'.$ico['val']; $ico['contact'] = 'BitBucket: '.$ico['contact'];
                break;
                
                case 'grv':
                    $ico['url'] = 'http://grooveshark.com/#/'.$ico['val']; $ico['contact'] = 'Grooveshark: '.$ico['contact'];
                break;
                

                case 'red':
                    $ico['url'] = 'http://www.reddit.com/user/'.$ico['val']; $ico['contact'] = 'Reddit: '.$ico['contact'];
                break;
                
                case 'stu':
                    $ico['url'] = 'http://www.stumbleupon.com/stumbler/'.$ico['val']; $ico['contact'] = 'StumbleUpon: '.$ico['contact'];
                break;                        

                case 'yam':
                case 'gma':
                case 'hom': $blank = false;
                    $ico['url'] = 'mailto:'.$ico['val']; $ico['contact'] = 'Email: '.$ico['contact'];
                break;


                case 'ste':
                    $ico['url'] = 'http://steamcommunity.com/id/'.$ico['val']; $ico['contact'] = 'Steam: '.$ico['contact'];
                break;
                
                case 'spo':
                    $ico['url'] = 'http://open.spotify.com/user/'.$ico['val']; $ico['contact'] = 'Spotify: '.$ico['contact'];
                break;
                
                case 'utb':
                    $ico['url'] = 'http://www.youtube.com/user/'.$ico['val']; $ico['contact'] = 'YouTube: '.$ico['contact'];
                break;
                
                case 'dA':
                    $ico['url'] = 'http://'.$ico['val'].'.deviantart.com/'; $ico['contact'] = 'DeviantArt: '.$ico['contact'];
                break;
               
                case 'urb':
                    $ico['url'] = $ico['val']; $ico['contact'] = 'Your Blog: '.$ico['contact'];
                break;
                
                case 'urw':
                    $ico['url'] = $ico['val']; $ico['contact'] = 'Your Website: '.$ico['contact'];
                break;
                
                case 'url':
                    $location = $ico['val']; continue(2);
                break;
                
            }
            $ico['blank'] = $blank || true;

            //if we have a filter, continue if this element isnt in the filter
            if(count($filter) && !in_array($row['type'], $filter)){ continue; }
            
            //add to the vars and return
            $icon = '<a href="%s" class="%s contactInfo hoverWatch" title="%s" rel="nofollow" ico="%s"%s>&nbsp;</a>';
            $icons .= sprintf($icon, $ico['url'], $ico['type'], $ico['contact'], $ico['type'], ($ico['blank']===true ? ' target="_blank"' : ''));
        }

        return $icons;
    }
}

?>