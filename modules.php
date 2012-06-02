<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
define('INDEX_CHECK', 1);
define('cmsDEBUG', 1);
include_once('core/core.php');

/**
 * Essentially with the rewrite action, _GET variables are ignored
 * this reverses that, so anything passed to the page via _GET is
 * usable as normal
 */
$url = explode('?', $_SERVER['REQUEST_URI']);
if(isset($url[1])){
    //backup the _GET array parse_str overwrites the $_GET array
    $GET = $_GET;
    //parse the _GET vars from the url
    parse_str($url[1], $_GET);
    //and merge away :D
    $_GET = array_merge($GET, $_GET);
}

$mode   = doArgs('__mode',      null, $_GET);
$module = doArgs('__module',    null, $_GET);
$action = doArgs('__action',    null, $_GET);
$extra  = doArgs('__extra',     null, $_GET);

if(!preg_match('#install($|/)#i', $action)){
    if(!empty($module) && $objCore->loadModule($module, true)){
        $objModule = new $module($objCore);
        $objModule->doAction($action);
    }else{
        $objCore->throwHTTP(404);
    }
}else{
    $objCore->autoLoadModule('core', $objModule);
    $objModule->installModule($module);
}

$tplMode = $objPage->getVar('tplMode');
$objPage->showHeader((!$tplMode&&!isset($_GET['ajax']) ? false : true));
    if($__eval = $objTPL->output('body')){
        msgDie('FAIL', 'No output received from module.');
    }
$objPage->showFooter((!$tplMode&&!isset($_GET['ajax']) ? false : true));
?>