<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

function menuChecker($link=''){
    $module = (isset($_GET['__module']) ? $_GET['__module'] : '');
    if(strtolower($module)==strtolower($link)){
        return ' class="selected"';
    }
    return NULL;
}

$_menu = get_menu('main_nav', 'array');
$menu = '';
if(!is_empty($_menu)){
    foreach($_menu as $m){
        $start = ($m['options']['color']==NULL ? '' : '<font style="color: '.$m['options']['color'].';">');
        $stop = ($m['options']['color']==NULL ? '' : '</font>');
        $menu .= '<li'.menuChecker($m['options']['name']).'>'.
                    '<a href="'.$m['options']['link'].'"'.$m['options']['blank'].'>'.
                        $start.$m['options']['name'].$stop.
                    '</a></li>'."\n";
    }
}

$_more_vars = array(
                'L_WELCOME'     => langVar('L_WELCOME', $this->config('site', 'title'), $this->objUser->profile($this->objUser->grab('id'))),
                'TPL_MENU'         => $menu,
            );

?>