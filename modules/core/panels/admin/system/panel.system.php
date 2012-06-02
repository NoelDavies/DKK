<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }
if(!defined('PANEL_CHECK')){ die('Error: Cannot include panel from current location.'); }
$objPage->setTitle(langVar('B_ACP').' > '.langVar('L_SYS_INFO'));
$objPage->addPagecrumb(array( array('url' => $url, 'name' => langVar('L_SYS_INFO')) ));

$page = doArgs('page', null, $_GET);

switch($page){
    //system information, this one will tell you about the enviroment, the cms etc
    case 'sysinfo':
        $objTPL->set_filenames(array(
            'body'  => 'modules/core/template/panels/panel.sysinfo.tpl',
        ));

        //grab some shite about GD
        if(function_exists('gd_info')){
            $a = gd_info(); $gdVer = preg_replace('/[[:alpha:][:space:]()]+/', '', $a['GD Version']);
        }else{
            $gdVer = 'Not Installed.';
        }

        //figure out which DB extensions are avalible
        $dbList = null;
        $dbTests = array('mysql_connect', 'mysqli_connect', 'pg_connect', 'mssql_connect', 'sqlite_open');
        foreach($dbTests as $test){
            if(function_exists($test) && is_callable($test)){
                $db = explode('_', $test);

                switch($db[0]){
                    default:
                        $dbList[] = $db[0];
                    break;

                    case 'mysql':
                        $dbList[] = $db[0].' (Server: '.mysql_get_server_info().')';
                    break;

                    case 'mysqli':
                        include(cmsROOT.'cache/config.php');
                        $mysqli = new mysqli($config['db']['host'], $config['db']['username'], $config['db']['password'], $config['db']['database']);
                        $dbList[] = $db[0].' (Server: '.mysqli_get_server_version($mysqli).', Client: '.mysqli_get_client_version().')';
                        $mysqli->close(); unset($mysqli, $config['db']);
                    break;

                    case 'sqlite':
                        $dbList[] = $db[0].' (Server: '.sqlite_libversion().')';
                    break;

                    case 'pg':
                        $dbList[] = 'postgre (Server: '.pg_version().')';
                    break;
                }
            }
        }

        if(class_exists('SQLite3', false) && is_callable(array('SQLite3', 'open'))){
            $ver = SQLite3::version();
            $dbList[] = 'SQLite3 (Server: '.$ver['versionString'].')';
        }


        //gather some debug content :)
        $content = '
    ;--System Setup
        OS Info: "'.php_uname().'"
        OS Quick: "'.PHP_OS.'"
        CMS Version: "'.cmsVERSION.'"
        CMS Version: "'.cmsVERSIONID.'"
        PHP Version: "'.PHP_VERSION.'" ('.(@ini_get('safe_mode') == '1' || strtolower(@ini_get('safe_mode')) == 'on' ?
                                                'Safe Mode Enabled' : 'Safe Mode Disabled').')
        PHP Version: "'.PHP_VERSION_ID.'"

        Avalible DB Support: '."\n\t\t - ".implode("\n\t\t - ", $dbList).'

        GD Version: "'.$gdVer.'"

    ;--CMS Setup
        URL: "'.$objCore->config('global', 'rootUrl').'"
        root(): "/'.root().'"
        cmsROOT: "'.cmsROOT.'"
        https?: "'.($objCore->config('global', 'secure') ? 'true' : 'false').'"';

    if(doArgs('config', false, $_GET)){
        $array = array('cms', 'db', 'email');
        $var = array('google_analytics', 'admin_email', 'registry_update');

        $content .= "\n\n".json_encode($objSQL->getTable(
            'SELECT * FROM `$Pconfig`
            WHERE array NOT IN("%s")
                AND var NOT IN("%s")
                AND var NOT LIKE "%s"
            ORDER BY array, var ASC',
            array(
                implode('", "', $array),
                implode('", "', $var),
                '%captcha_%'
            )
        ));
    }

        //and output
        include($path.'/cfg.php');
        $objTPL->assign_vars(array(
            'ADMIN_MODE'    => $mod_name,
            'MSG'           => msg('INFO', langVar('L_SYSINFO_MSG'), 'return',
                                    'Information - <a href="'.$objCore->getQueryString($url, array('config'=>'true')).'">With Configuration</a>'),
            'CONTENT'       => $objForm->textarea('sysInfo', $content, array('style'=>'width: 99%;border:0;')),
        ));

    break;

    case 'update':
        hmsgDie('INFO', 'This panel has yet to be implemented. Some ideas for it have been put in the source.');
/* TODO:
    No clue :P

*/
    break;

    case 'about':
//        $objTPL->set_filenames(array(
//            'body'      => 'modules/core/template/panels/panel.settings.tpl',
//        ));
        hmsgDie('INFO', 'This panel has yet to be implemented. Some ideas for it have been put in the source.');
/* TODO:
    Not sure about this one either, prolly wont stay here..

*/

    break;


    default:
        $objCore->throwHTTP(404);
    break;
}

$objTPL->parse('body', false);
?>