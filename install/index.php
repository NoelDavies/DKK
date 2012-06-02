<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
define('INDEX_CHECK', true);
define('INSTALLER', true);
$cmsROOT = '../';
require 'installerCore.php';
$mode = doArgs('action', 1, $_GET, 'is_number');
set_time_limit(0); //this could take a while

$version = 'V1.0'; //curent CMS version, this should be updated as needed
$title = 'Cybershade Installer '.$version;

//set some vars before we use em, also the header and footer tpls
$info = NULL; $content = NULL;
$objTPL->set_filenames(array(
    'header' => 'install/template/header.tpl',
    'footer' => 'install/template/footer.tpl',
));

$objTPL->set_filenames(array(
    'content' => 'install/template/step_'.$mode.'.tpl'
));

$steps = array('Checking Compatibility', 'Database Information', 'Administration Setup', 'Website Configuration', 'Save Configuration', 'Install Tables');
$objTPL->assign_var('STEP', '<h2>Step '.$mode.' - '.$steps[($mode-1)].'</h2>');

$Y = 'Yes';
$N = 'No';

switch($mode){

    default:
    case 1:
        $objTPL->assign_var('SUBMIT', $objForm->button('submit', 'Next',  array('extra'=>' onclick="window.location=\'?action=2\'"')));
        $info = 'Thanks for downloading Cybershade CMS V1.0. Before you can use the CMS we have to run through some small things. Please have your database information to hand, and make sure you have access to your FTP client.';


        $checks = array();
        $checks[] = array('check'       => '<strong>PHP Settings</strong>',
                          'setting'     => '<strong>Setting</strong>');

        if(version_compare(PHP_VERSION, '5.2.0', '<=')){
            $result = '<strong style="color:red">'.$N.'</strong>';
        }else{
            $passed['php'] = true;

            // We also give feedback on whether we're running in safe mode
            $result = '<strong style="color:green">'.$Y;
            if (@ini_get('safe_mode') == '1' || strtolower(@ini_get('safe_mode')) == 'on'){
                $result .= ', Safe Mode Enabled';
            }
            $result .= '</strong>';
        }
        $checks[] = array('check'       => 'PHP Version >= 5.0.0',
                          'setting'     => $result);

        // Check for sql abilities
        if (function_exists('mysql_connect')){
            $result = '<strong style="color:green">'.$Y.'</strong>';
        }else{
            $result = '<strong style="color:red">'.$N.' -- CANNOT CONTINUE</strong>';
        }
        $checks[] = array('check'       => 'MySQL Support',
                          'setting'     => $result);

        // Check for rewrite abilities
        /** Below is commented out till i can figure out wth is goin on and how to fix it
         *         $modules = apache_get_modules();
         *         if(in_array('mod_rewrite', $modules)){
         *             $result = '<strong style="color:green">Enabled</strong>';
         *         }else{
         *             $result = '<strong style="color:red">Disabled -- CANNOT CONTINUE</strong>';
         *         }
         *         $checks[] = array('check'       => 'Apache Rewrite Module',
         *                           'explain'     => '<b>Required</b> - The CMS uses several Rewrite rules to allow easier access and cleaner URLs for your website. Should this be disabled your website installation will not work as expected.',
         *                           'setting'     => $result);
         */

        // Check for register_globals being enabled
        if (@ini_get('register_globals') == '1' || strtolower(@ini_get('register_globals')) == 'on'){
            $result = '<strong style="color:red">Enabled</strong>';
        }else{
            $result = '<strong style="color:green">Disabled</strong>';
        }
        $checks[] = array('check'       => 'register_globals() is disabled',
                          'explain'     => '<b>Optional</b> - This setting is optional, it is however recommended that register_globals is disabled on your PHP install for security reasons.',
                          'setting'     => $result);

        // Check for url_fopen
        if (@ini_get('allow_url_fopen') == '1' || strtolower(@ini_get('allow_url_fopen')) == 'on'){
            $result = '<strong style="color:green">'.$Y.'</strong>';
        }else{
            $result = '<strong style="color:red">'.$N.'</strong>';
        }
        $checks[] = array('check'       => 'allow_url_fopen() is enabled',
                          'explain'     => '<b>Optional</b> - This setting is optional, however certain functions like off-site avatars will not work properly without it.',
                          'setting'     => $result);

        // Check for getimagesize
        if(function_exists('gd_info')){
            $a = gd_info(); $ver = preg_replace('/[[:alpha:][:space:]()]+/', '', $a['GD Version']);
             if(version_compare($ver, '2', '>=')){
                $result = '<strong style="color:green">'.$Y.'</strong>';
            }else{
                $result = '<strong style="color:red">'.$N.'</strong>';
            }
        }else{
            $result = '<strong style="color:red">'.$N.'</strong>';
        }
        $checks[] = array('check'       => 'GD >= 2',
                          'explain'     => '<b>Required</b> - This is required for things like the captcha and other image generation to work correctly.',
                          'setting'     => $result);

        $checks[] = array('check'       => '&nbsp;', 'setting'     => '');
        $checks[] = array('check'       => '<strong>Directories and Such</strong>',
                          'setting'     => '<strong>Setting</strong>');

        $dirs = array('cache/', 'cache/template/', 'cache/media/', 'images/avatars/');
        foreach($dirs as $dir){
            $exists = $write = false;

            // Try to create the directory if it does not exist
            if(!file_exists(cmsROOT.$dir)){
                @mkdir(cmsROOT.$dir, 0775);
                @chmod(cmsROOT.$dir, 0775);
            }

            // Now really check
            if(file_exists(cmsROOT.$dir) && is_dir(cmsROOT.$dir)){
                if(!@is_writable(cmsROOT.$dir)){
                    @chmod(cmsROOT.$dir, 0775);
                }
                $exists = true;
            }

            // Now check if it is writable by storing a simple file
            $fp = @fopen(cmsROOT.$dir.'test_lock', 'wb');
            if($fp !== false){ $write = true; }
            @fclose($fp);

            @unlink(cmsROOT.$dir.'test_lock');
            $exists = ($exists ? '<strong style="color:green">FOUND</strong>' : '<strong style="color:red">NOT FOUND</strong>');
            $write = ($write
                        ? ', <strong style="color:green">WRITABLE</strong>'
                        : ($exists
                            ? ', <strong style="color:red">UNWRITABLE</strong>'
                            : ''
                          )
                     );

            $checks[] = array('check'       => $dir,
                              'setting'     => $exists.$write);

        }

        $counter = 0;
        foreach($checks as $check){
            $objTPL->assign_block_vars('checks', array(
                'CHECK'     => $check['check'],
                'EXPLAIN'   => isset($check['explain']) ? $check['explain'] : NULL,
                'ROW'       => ($counter++ % 2==0 ? '#202123' : '#3D3E42'),
                'SETTING'   => $check['setting'],
            ));
            if(isset($check['explain'])){
                $objTPL->assign_block_vars('checks.explain', array());
            }
        }

    break;

    case 2:
        $info = 'Currently, Cybershade CMS only supports MySQL database. No doubt this will change as time goes on but for now it\'ll do.';

        if(isset($_GET['try'])){
            if(!mysql_connect($_POST['db_server'], $_POST['db_uname'], $_POST['db_passwd'])){
                $info .= '<br /><br /><font style="color:red;"><strong>ERROR: Could not connect to MySQL.<br />'.mysql_error().'</strong></font>';
            }else{
                if(!mysql_select_db($_POST['db_db'])){
                    $info .= '<br /><br /><font style="color:red;"><strong>ERROR: Could not select MySQL Database.<br />'.mysql_error().'</strong></font>';
                }else{
                    $_SESSION['db']['driver']   = $_POST['db_driver'];
                    $_SESSION['db']['host']     = $_POST['db_server'];
                    $_SESSION['db']['username'] = $_POST['db_uname'];
                    $_SESSION['db']['password'] = $_POST['db_passwd'];
                    $_SESSION['db']['database'] = $_POST['db_db'];
                    $_SESSION['db']['prefix']   = $_POST['db_prefix'];
                    header('Location: ?action=3');
                }
            }
        }

        $dbList = array();
        $dbTests = array('mysql_connect', 'mysqli_connect', 'pg_connect', 'mssql_connect', 'sqlite_open');
        foreach($dbTests as $test){
            $db = explode('_', $test);
            if(function_exists($test) && is_callable($test) && is_readable(cmsROOT.'core/classes/driver.'.$db[0].'.php')){
                switch($db[0]){
                    default: $text = $db[0]; break;

                    case 'mysql': $text = 'MySQL'; break;
                    case 'mysqli': $text = 'MySQLi'; break;
                    case 'sqlite': $text = 'SQLite'; break;
                    case 'pg': $text = 'PostgreSQL'; break;
                }

                $dbList[$db[0]] = $text;
               }
        }

            $vars = array(
                'Database Driver'        => $objForm->select('db_driver', $dbList),
                'Database Server'        => $objForm->inputbox('db_server', 'input', 'localhost'),
                'Database Username'        => $objForm->inputbox('db_uname', 'input', ''),
                'Database Password'        => $objForm->inputbox('db_passwd', 'password', ''),
                'Database Name'            => $objForm->inputbox('db_db', 'input', ''),
                'Database Prefix'        => $objForm->inputbox('db_prefix', 'input', 'cscms_'),
            );
            $objTPL->assign_vars(array(
                'FORM_START'    => $objForm->start('databse', array('method' => 'POST', 'action' => '?action=2&try')),
                'FORM_END'      => $objForm->finish(),
                'SUBMIT'        => $objForm->button('submit', 'Next'),
            ));

            foreach($vars as $key => $value){
                $objTPL->assign_block_vars('settings', array(
                    'VALUE'     => $key,
                    'SETTING'     => $value,
                ));
            }
    break;

    case 3:
        $info = 'Fill the form below in with your user details, you will be automatically added to the website and made an administrator.';

        if(isset($_GET['try'])){
            $fields = array('username', 'passwd', 'v_passwd', 'email');

            $errors = array();
            foreach($fields as $f){
                $field = 'adm_'.$f;
                if(!isset($_POST[$field]) || is_empty($_POST[$field])){
                    $errors[] = $field.' was empty';
                    continue;
                }
                switch($f){
                    case 'username':
                        if(!$objUser->validateUsername($_POST[$field])){ $errors[] = $field.': '.$objUser->getError(); continue; }
                    break;

                    case 'email':
                        if(!$objUser->validateEmail($_POST[$field])){ $errors[] = $field.' was deemed invalid'; continue; }
                    break;

                    case 'v_passwd':
                    case 'passwd':
                        if((strlen($_POST[$field])<4)){ $errors[] = $field.' needs to be >= 4 chars'; continue; }
                    break;
                }
            }

            if(count($errors)){
                $info .= '<br /><br /><font style="color:red;"><strong>ERROR: <br />-'.implode('<br />-', $errors).'</strong></font>';
            }else{
                $_SESSION['adm']['username']    = $_POST['adm_username'];
                $_SESSION['adm']['password']    = $_POST['adm_passwd'];
                $_SESSION['adm']['email']       = $_POST['adm_email'];
                header('Location: ?action=4');
            }
        }

        $vars = array(
            'Administrator Username'    => $objForm->inputbox('adm_username', 'input', doArgs('adm_username', 'admin', $_POST)),
            'Administrator Password'    => $objForm->inputbox('adm_passwd', 'password', doArgs('adm_passwd', '', $_POST)),
            'Verify Password'            => $objForm->inputbox('adm_v_passwd', 'password', doArgs('adm_v_passwd', '', $_POST)),
            'Administrator Email'        => $objForm->inputbox('adm_email', 'input', doArgs('adm_email', '', $_POST)),
        );

        $objTPL->assign_vars(array(
            'FORM_START'    => $objForm->start('databse', array('method' => 'POST', 'action' => '?action=3&try', 'onsubmit'=>'return confirm(\'Only continue if you are happy with the details you provided.\')')),
            'FORM_END'      => $objForm->finish(),
            'SUBMIT'        => $objForm->button('submit', 'Next'),
        ));

        foreach($vars as $key => $value){
            $objTPL->assign_block_vars('settings', array(
                'VALUE'                => $key,
                'SETTING'            => $value,
            ));
        }
    break;

    case 4:
        $info = 'Use the form below to setup your inital website settings.';

        if(isset($_GET['try'])){
            $fields = array('title', 'slogan', 'description', 'keywords', 'time');

            $errors = array();
            foreach($fields as $f){
                if(!isset($_POST[$f]) || is_empty($_POST[$f])){
                    $errors[] = $f.' was empty';
                    $$f = NULL;
                }else{
                    $$f = $_POST[$f];
                }
            }

            if(count($errors)){
                $info .= '<br /><br /><font style="color:red;"><strong>ERROR: <br />-'.implode('<br />-', $errors).'</strong></font>';
            }else{
                $_SESSION['POST'] = $_POST;
                header('Location: ?action=5');
                break;
            }
        }

        $options = array('style' =>'width: 94%;');
        $vars = array(
            'Site Title'                    => $objForm->inputbox('title', 'text', $title, $options),
            'Site Slogan'                   => $objForm->inputbox('slogan', 'text', $slogan, $options),
            'Site Description'              => $objForm->textarea('description', $description, array('style' =>'height: 50px;')),
            'Site Keywords'                 => $objForm->textarea('keywords', $keywords, array('style' =>'height: 50px;')),
            'Default Time Format'           => $objForm->inputbox('time', 'text', ($time==NULL ? 'jS F h:ia' : $time), $options),
        );
        $objTPL->assign_vars(array(
            'FORM_START'    => $objForm->start('siteConfig', array('method' => 'POST', 'action' => '?action=4&try')),
            'FORM_END'      => $objForm->finish(),
            'SUBMIT'        => $objForm->button('submit', 'Next'),
        ));

        foreach($vars as $key => $value){
            $objTPL->assign_block_vars('settings', array(
                'VALUE'        => $key,
                'SETTING'    => $value,
            ));
        }
    break;

    case 5:
        $file = cmsROOT.'cache/config.php';
        if(!file_exists($file)){
            $test = fopen($file, 'w');
            fwrite($test, 'test');
            fclose($test);
        }
        if(!is_writable($file)){
            @chmod($file, 0777);
        }


        if(is_writable($file)) {
            $open = fopen($file, 'w');
            $_SESSION['db'] = array_map('addslashes', $_SESSION['db']);
            $config = '<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined(\'INDEX_CHECK\')){die(\'Error: Cannot access directly.\');}

//some db settings and the like etc
    $config[\'db\'][\'driver\']                 = \''.$_SESSION['db']['driver'].'\';
    $config[\'db\'][\'host\']                     = \''.$_SESSION['db']['host'].'\';
    $config[\'db\'][\'username\']                 = \''.$_SESSION['db']['username'].'\';
    $config[\'db\'][\'password\']                 = \''.$_SESSION['db']['password'].'\';
    $config[\'db\'][\'database\']                 = \''.$_SESSION['db']['database'].'\';
    $config[\'db\'][\'prefix\']                 = \''.$_SESSION['db']['prefix'].'\';
//the cookie prefix
    $config[\'db\'][\'ckefix\']                 = \'CMS_\';

//some settings for the cron
    $config[\'cron\'][\'hourly_time\']            = (3600); //1 Hour
    $config[\'cron\'][\'daily_time\']            = (3600*24); //1 Day
    $config[\'cron\'][\'weekly_time\']            = (3600*24*7); //1 Week

//some default settings, incase the cms dies before getting
//the chance to populate the config array.
    $config[\'cms\'][\'name\']                  = \'CyberShade CMS\';
    $config[\'cms\'][\'version\']               = \'N/A\';
    $config[\'site\'][\'title\']                = \'CyberShade CMS\';
    $config[\'site\'][\'theme\']                = \'default\';
    $config[\'site\'][\'language\']             = \'en\';

?>';

            fwrite($open,$config);
            fclose($open);
            $objTPL->assign_var('MSG', 'Config Written Successfully.');
            $objTPL->assign_var('SUBMIT', $objForm->button('submit', 'Next', array('extra'=>' onclick="window.location=\'?action=6\'"')));
            $_SESSION['allow_config'] = true;
        }else{
            $objTPL->assign_var('MSG', 'CONFIG.PHP isnt writable, please chmod it 0777 before continuing. To continue please press Refresh or F5 and RETRY the process');
        }
    break;

    case 6:
        include(cmsROOT.'cache/config.php');
    //
    //--SQL Setup
    //
        $objSQL     = new driver_mysql($config['db']);
        //check and see whether we can connect to the db
        if(!$objSQL->connect(true, (LOCALHOST && cmsDEBUG ? true : false), is_file(cmsROOT.'cache/ALLOW_LOGGING'))){
            msgDie('FAIL', '<b>Fatal Error</b>: <i>No Connection to the database</i>. SQL Said: '.$objSQL->error(), __LINE__, __FILE__);
        }
        unset($config['db']['password']); //dont want this info being used now :D

        if(is_readable('sql.php')){
            include_once('sql.php');

            if(!is_array($sql) || !count($sql)){ $info = '<font color=red>ERROR: No SQL to process.</font>'; break; }

            $content = '';
            foreach($sql as $s){
                //replace the table prefix's with the wanted version :D
                $s = str_replace('cs_', $config['db']['prefix'], $s);
                $query = $objSQL->query($s);
                $content .= ($query===false ? dump($s, mysql_error()) : NULL);
            }

            //it worked
            if(is_empty($content)){
                //reset the cache
                $cacheFiles = glob(cmsROOT.'cache/cache_*.php');
                if(is_array($cacheFiles)){ foreach($cacheFiles as $file){ unlink($file); }}

                $content = 'Tables Successfully Installed..<br /><strong>You have been set temporary a temporary Admin bypass which will allow you to get into the admin panel and reset your PIN. If you choose not to reset your PIN, your Admin Control Panel will be unuseable.</strong>';
                session_destroy();
                session_start();
                $_SESSION['acp']['adminAuth'] = true;
                $_SESSION['acp']['adminTimeout'] = time();
                $objTPL->assign_var('SUBMIT', $objForm->button('submit', 'Finish', array('extra' => ' onclick="window.location=\'/'.root().'index.php\'"')));
            }
            $objTPL->assign_var('MSG', $content);
        }
    break;
}



    //do 'menu'
    $stepsCount = count($steps);
    $menu = '<ul>'; $count = 1;
    foreach($steps as $step){
        if($count == $mode){
            $menu .= '<li>'.$step.'</li>';
        }else{
            $menu .= '<li class="disabled">'.$step.'</li>';
        }$count++;
    }
    $menu .= '</ul>';

    $objTPL->assign_vars(array(
        'ROOT'          => root(),

        'TITLE'         => $title,
        'MENU'          => $menu,

        'HEADER'        => $header,
        'WELCOME'       => 'Welcome to Cybershade CMS Installer '.$version.'!',
        'PROGRESS'  	=> number_format(($mode / $stepsCount) * 100, 0),
        'STEPS'         => sprintf('Step %d of %d', $mode, $stepsCount),
        'INFO'          => !is_empty($info) ? $info : '',
    ));

    if(!is_empty($info)){
        $objTPL->assign_block_vars('info', array());
    }

$objTPL->parse('header');
if(is_file(cmsROOT.'install/template/step_'.$mode.'.tpl')){
    $objTPL->parse('content');
}
$objTPL->parse('footer');
?>