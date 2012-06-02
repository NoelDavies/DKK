<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){die('Error: Cannot access directly.');}
if(!defined('PANEL_CHECK')){die('Error: Cannot include panel from current location.');}

//setup some vars we are gonna need
$corePanels = array(); $menuTabs = array();
$vars = $objPage->getVar('tplVars');

//check to see which panel group we need
if(User::$IS_ADMIN){    $corePanels['admin'] = cmsROOT.'modules/core/panels/admin/';   }
if(User::$IS_MOD){      $corePanels['mod']   = cmsROOT.'modules/core/panels/mod/';     }
if(User::$IS_USER){     $corePanels['user']  = cmsROOT.'modules/core/panels/user/';    }

if($module == 'core'){
    //setup the tabs
    $_cp_tabs = 'modules/core/panels/'.$controlPanel.'/menu.php';
    if(!is_file($_cp_tabs) || !is_readable($_cp_tabs)){
        hmsgDie('FAIL', 'Error: Tabs for this panel don\'t exist.');
    }
    $menuTabs = parse_ini_file($_cp_tabs, true);
    if(!is_array($menuTabs) || !count($menuTabs)){
        hmsgDie('FAIL', 'Error: Tabs setup failed.');
    }
}else{
    include(cmsROOT.'modules/'.$module.'/cfg.php');
    if(isset($mod_menu) && count($mod_menu)){
        $menuTabs = $mod_menu[$controlPanel];
    }
}

$_output_tabs = array();
//make sure we are in a good mode
$array = array('user', 'mod', 'admin');
if(count($config['modules']) && in_array($controlPanel, $array)){
    foreach($menuTabs as $parentKey => $v){
        if(is_array($v)){
            foreach($v as $key => $value){
                if($key != 'info'){
                    $_output_tabs[$parentKey][$key] = $value;
                }
            }
        }
    }

    $assign = NULL;
    //run thru the installed modules
    foreach($config['modules'] as $module){
        //make sure its enabled and not core
        if($module['enabled']==false || strtolower($module['name'])=='core'){ continue; }
        //run a few checks and add it to the list
        $modulePath = cmsROOT.'modules/'.$module['name'].'/';
        if(is_dir($modulePath) &&
           is_readable($modulePath.'/cfg.php') &&
           is_readable($modulePath.'/'.$controlPanel.'.'.$module['name'].'.php')){
                include($modulePath.'/cfg.php');
                $_output_tabs['Modules'][$mod_name] = $controlPanel.'/'.$module['name'].'/';
        }
    }
}

$menu = null; $module = doArgs('__module', 'core', $_GET);

//add a hook for the tabs, this will allow the plugin developers to add configuration pages, and the links for them
if($module == 'core'){
    $objPlugins->hook('CMSCore_panelTabs', $_output_tabs);
}

if(!is_empty($_output_tabs)){
    $_class = 'on';
    $_tab = '<li class="%3$s">%1$s <ul class="grid_8 sub">'."\n".'%2$s</ul></li>'."\n";
    $_subTab = '<li>%s</li>'."\n";
    $_link = '<a href="%s">%s</a>';

    foreach($_output_tabs as $tab => $links){
        $subTabs = null;
        $tab = stripslashes($tab);

        $on = false;
        foreach($links as $subTitle => $subLink){
            if($module != 'core'){
                $subLink = '/'.root().$controlPanel.'/'.$module.'/'.$subLink;
            }else{
                $subLink = '/'.root().$subLink;
            }

            $link = sprintf($_link, $subLink, $subTitle);
            $subTabs .= sprintf($_subTab, $link);

            if($config['global']['fullPath'] == $subLink){
                $on = true;
            }
        }
        $menu .= sprintf($_tab, $tab, $subTabs, ($on === true ? $_class : null));
    }

}

$objPage->addCSSFile('/'.root().'images/panels.css');

$objTPL->set_filenames(array(
    'sys_tabs' => 'modules/core/template/panels/panel.panel_tabs.tpl'
));

$objTPL->assign_block_vars('panelMenu', array(
    'TABS' => $menu,
));

$objTPL->parse('sys_tabs', false);
?>