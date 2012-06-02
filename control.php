<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
define('INDEX_CHECK', 1);
define('PANEL_CHECK', 1);
define('cmsDEBUG', 0);
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
#echo dump($_GET);

$mode   = doArgs('__mode',      null,   $_GET);
$module = doArgs('__module',    'core', $_GET);
$action = doArgs('__action',    null,   $_GET);
$extra  = doArgs('__extra',     null,   $_GET);

if(false){
    echo dump($mode) . dump($module) . dump($action) . dump($extra);
}

//user isnt even logged in lets 404 them
if(!User::$IS_ONLINE){
    $objCore->throwHTTP(404);
}

//make sure they are getting at the right panel
$checkMode = array('admin', 'mod', 'user');
if(!in_array($mode, $checkMode)){
    hmsgDie('FAIL', 'Error: Unknown Panel Group');
}

$objPage->addPagecrumb(array(
    array('url' => '/'.root().$mode.'/', 'name' => ucwords($mode).' Control Panel')
));

//if we are tryin to load a core panel..
if(strtolower($module)=='core'){
    require(cmsROOT.'modules/core/handler.panels.php');
}else{
    $controlPanel = $mode;
    require(cmsROOT.'modules/core/handler.panelTabs.php');

    if(!empty($module) && $objPage->loadModule($module, true, $mode)){
        $objModule = new $module($objCore);
        $objModule->doAction($action);
    }else{
        $objCore->throwHTTP(404);
    }
}

//check if we need to force simple mode
$doSimple = false;
if(isset($_GET['ajax']) || HTTP_AJAX || $objPage->getVar('tplMode')){
    $doSimple = true;
}

$objPage->showHeader($doSimple);
if(strtolower($module)!='core'){ $objTPL->output('sys_tabs'); }
    if($__eval = $objTPL->output(($objTPL->isHandle('panel') ? 'panel' : 'body'))){
        msgDie('FAIL', 'No output received from module.');
    }
$objPage->showFooter($doSimple);