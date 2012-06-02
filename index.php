<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
define('INDEX_CHECK', 1);
define('cmsDEBUG', 1);
include_once('core/core.php');

//check to make sure the module has the needed function to run
$module = $objCore->config('site', 'index_module');
if(is_dir(cmsROOT.'modules/'.$module.'/') && is_readable(cmsROOT.'modules/'.$module.'/cfg.php')){
    if(!preg_match('/function\sshowMain\(/is', file_get_contents(cmsROOT.'modules/'.$module.'/class.'.$module.'.php'))){
        $module = 'core';
    }
}else{ $module = 'core'; }

if(!empty($module) && $objCore->loadModule($module, true)){
    $objModule = new $module($objCore);
    if(method_exists($objModule, 'showMain')){
        $objModule->showMain();
    }else{
        hmsgDie('FAIL', 'Sorry the Module that was supposed to be supplying this page with data apparently cant....');
    }
}else{
    $objCore->throwHTTP(404);
}

$objPage->showHeader();
    if(!$objTPL->get_html('body')){
        msgDie('FAIL', 'No output received from module.');
    }else{
        echo $objTPL->get_html('body');
    }
$objPage->showFooter();
?>