<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

//
//--Before we begin, lets define some stuff up
//
    $START_CMS_LOAD = microtime(true); $START_RAM_USE = memory_get_usage();
    //Lets set a simple error template up till we have the template engine going
    $errorTPL = '<h3>%s</h3> <p>%s Killing Process...</p>';
    @set_magic_quotes_runtime(false);

    //if we havent a session, lets start one up
    if(!isset($_SESSION)){ session_start(); }

    //setup a few things, these are kept out of the constants.php file cause we need em before thats included.
    define('cmsVERSION', '1.0.0');
    define('cmsVERSIONID', '10000');
    
    if(!defined('cmsDEBUG')){
        define('cmsDEBUG', false);
    }

    /**
     * cmsROOT - Internal way of getting to the project root
     * @note for internal use, use cmsROOT, for external use, eg js and html paths, use root();
     */
    define('cmsROOT', (isset($cmsROOT) && !empty($cmsROOT) ? $cmsROOT : null)); unset($cmsROOT);

    //so we can turn errors off if we are not running locally
    define('LOCALHOST', (isset($_SERVER['HTTP_HOST']) &&
                            ($_SERVER['HTTP_HOST']=='localhost' ||
                             $_SERVER['HTTP_HOST']=='127.0.0.1'))
                        ? true
                        : false);

    //define the error reporting level, dont want PHP errors on the live version now do we :)
    error_reporting(LOCALHOST ? E_ALL & ~E_NOTICE | E_STRICT : 0);

    //if the killCMS file is present we need to kill execution right here
    if(is_file(cmsROOT.'killCMS')){
        die(sprintf($errorTPL, 'Fatal Error', 'This CMS has detected a Security Flaw. Please Upgrade to the latest version.'));
    }

    //check if we have config
    $file = cmsROOT.'cache/config.php';
    if(!is_file($file) || (file_get_contents($file) == '')){
        die(sprintf($errorTPL, 'Fatal Error', 'This seems to be your first time running. Are you looking for <a href="install/">Install/</a> ?'));
    }

    //make sure the file is readable, if so require it
    if(!is_readable($file)){
        die(sprintf($errorTPL, 'Fatal Error - 404', 'We have been unable to read the configuration file, please ensure correct owner privledges are given.'));
    }else{ require_once($file); }

    //we need constants.php, same deal as above
    $file = cmsROOT.'core/constants.php';
    if(!is_readable($file)){
        die(sprintf($errorTPL, 'Fatal Error - 404', 'We have been unable to locate/read the constants file.'));
    }else{ require_once($file); }

    //make sure we are running a compatible PHP Version
    if(PHP_VERSION_ID < '50300'){
        die(sprintf($errorTPL, 'Fatal Error - 500',
            'This server is not capable of running this CMS, please upgrade PHP to version 5.3+ before trying to continue.'));
    }

    $redoHandler = false;
    $file = cmsROOT.'core/debugFunctions.php';
    if(!is_readable($file)){
        function dump(){} function getExecInfo(){} function memoryUsage(){}
    }else{ $redoHandler = true; require_once($file); }

    $file = cmsROOT.'core/baseFunctions.php';
    if(!is_readable($file)){
        die(sprintf($errorTPL, 'Fatal Error - 404', 'We have been unable to locate/read the baseFunctions file.'));
    }else{ require_once($file); }

    //kill magic quotes completely
    if(@get_magic_quotes_gpc()){
        //strip all the global arrays
        recursiveArray($_POST,      'stripslashes');
        recursiveArray($_GET,       'stripslashes');
        recursiveArray($_COOKIE,    'stripslashes');
        recursiveArray($_REQUEST,   'stripslashes');
    }

    (LOCALHOST ? set_error_handler('cmsError') : '');
    if($redoHandler && cmsDEBUG == true){
        set_error_handler('error_handler');
        set_exception_handler('exception_handler');
        register_shutdown_function('fatal_error_handler');
    }unset($redoHandler);

    //set the default timezone
    if(function_exists('date_default_timezone_set')){
        date_default_timezone_set('Europe/London'); //ive set it to London, as i use the GMdate functions
    }

//
//--Classes Setup
//
    $classDir = cmsROOT.'core/classes/';
    $libDir = cmsROOT.'core/lib/';
    $classes = array();

    //load in outside classes
    $classFiles = array(
        $classDir.'base.core.php', # all CMS classes extend this one
        $classDir.'base.sql.php', # this is the SQL template

        $classDir.'class.pagination.php', # include pagination functionality
        //$classDir.'class.rating.php', # this one includes a rating system

        $libDir.'phpass/class.phpass.php',
        $libDir.'geshi/class.geshi.php',
        $libDir.'nbbc/class.nbbc.php'
    );

        foreach($classFiles as $file){
            $file = $file;
            if(!is_file($file) || !is_readable($file)){
                msgDie('FAIL', sprintf($errorTPL, 'Fatal Error - 404', 'We have been unable to locate/read the '.$file.' file.'));
            }else{ require_once($file); }
        }

    $objCore = new coreClass;

    //cache setup
    $cachePath = cmsROOT.'cache/';
    if(is_dir($cachePath) && !is_writable($cachePath)){ @chmod($cachePath, 0775); }
    if(!is_writable($cachePath)){
        msgDie('FAIL', sprintf($errorTPL, 'Fatal Error', 'Could not set CHMOD permissions on "<i>cache/</i>" set to 775 to continue.'));
    }

    $cacheWritable = (is_writable($cachePath) ? true : false);

    //try and load in the sql driver
    $file = $classDir.'driver.'.$config['db']['driver'].'.php';
    if(is_file($file) && is_readable($file)){
        $classes['objSQL']      = array($file, $config['db']);
    }

//
//-- We will load the classes in 2 stages..SQL, Cache, Login and User classes first
//
    //if its still unset, default back to mysql
    if(!isset($classes['objSQL'])){
        $classes['objSQL']      = array($classDir.'driver.mysql.php', $config['db']);
    }
    $classes['objCache']        = array($classDir.'class.cache.php', array(
                                    'useCache'     => $cacheWritable,
                                    'cacheDir'     => $cachePath
                                ));
    $classes['objLogin']        = array($classDir.'class.login.php');
    $classes['objUser']         = array($classDir.'class.user.php');

    //plugins have been moved here so we can hook into the init of stage 2 classes
    //and possibly load some custom ones such as reCaptcha etc ;)
    $classes['objPlugins']        = array($classDir.'class.plugins.php');

    //init the sql and cache classes, we need these before we can go any further
    $doneSetup = $objCore->setup($classes);
    if(!$doneSetup){
        msgDie('FAIL', sprintf($errorTPL, 'Fatal Error',
            'Cannot load CMS Classes, make sure file structure is intact and $cmsROOT is defined properly if applicable.'));
    }
    unset($classes, $doneSetup);

    //connect to mysql
    $connectTest = $objCore->objSQL->connect(true, (LOCALHOST && cmsDEBUG ? true : false), is_file(cmsROOT.'cache/ALLOW_LOGGING'));
    if(!$connectTest){
        msgDie('FAIL', sprintf($errorTPL, 'Fatal Error', 'Connecting to SQL failed. '.$objCore->objSQL->getVar('errorMsg').
            (cmsDEBUG ? '<br />'.$objCore->objSQL->getError() : NULL)));
    }
    unset($config['db']['password'], $connectTest);
//
//--Cache Vars init
//
    //if it didnt, check to see which of the files didnt get added and try to get them manually
    if(!isset($config_db)){ newCache('config', $config_db); }

    //We have no configuration DB, and the one generated was NULL...
    if(!isset($config_db) || $config_db===NULL || empty($config_db)){
        //this will only happen if the CMS wasnt installed properly
        //or hasnt got access to her original tables
        msgDie('FAIL', sprintf($errorTPL, 'Fatal Error',
            'Cannot load CMS Configuration, make sure installation ran properly and mySQL user has access to tables.'));
    }

    //sort through the configuration crap, spit out a useable version :D
    foreach($config_db as $array){ $config[$array['array']][$array['var']] = $array['value']; }
    unset($config_db);

        //generate an array with names of files that should be added to the master config array()
        //NULL _SHOULD_ be the last 'file'
        $cache_gen  = array('menus', 'menu_setups', 'menu_blocks', 'groups', 'bans', 'group_subscriptions', 'statistics', 'modules', 'plugins', NULL);

        //set all the *_db vars above into the $config array
        $x = 0;
        while($var = $cache_gen[$x]){
            if(!isset(${$var.'_db'})){ newCache($var, ${$var.'_db'}); }

            $x++; //do this here it only increments $cache_gen anyway

            //if var is empty, continue, no point wasting time
            if(is_empty($var)){ continue; }

            //setup $gen for the var
            $gen = isset(${$var.'_db'}) ? ${$var.'_db'} : NULL;

            if(!is_array($gen) || is_empty($gen)){
                $config[$var] = NULL;
            }else{
                foreach($gen as $k => $v){
                    $config[$var][$k] = $v;
                }
            }

            unset(${$var.'_db'});
        }

    //clean the variable pool, keeping things nice and tidy
    unset($cache_gen, $config_db, $var);

    //do a few checks on the cache, see whats what
    if(is_empty($config['menu_blocks']) && !defined('NO_MENU')){
         define('NO_MENU', true);
    }

    //start plugins setups
    $objCore->objPlugins->loadHooks($config['plugins']);

//
//--Language Setup
//
    //grab the default language info, and test to see if user has a request
    $language = doArgs('language', 'en', $config['site']);
    $langDir = cmsROOT.'languages/';
    if(isset($_SESSION['user']['language'])){
        if(is_dir($langDir.$_SESSION['user']['language'].'/') &&
           is_readable($langDir.$_SESSION['user']['language'].'/main.php')){
                $language = $_SESSION['user']['language'];
        }
    }

    if(is_dir($langDir.$language.'/') || is_readable($langDir.$language.'/main.php')){
        translateFile($langDir.$language.'/main.php');
    }else{
        msgDie('FAIL', sprintf($errorTPL, 'Fatal Error',
            'Cannot open '.($langDir.$language.'/main.php').' for include.'));
    }

//
//-- and now load the rest of the classes
//
    $classes['objTPL']          = array($classDir.'class.template.php', array(
                                    'root'          => '.',
                                    'useCache'      => $cacheWritable,
                                    'cacheDir'      => $cachePath.'template/'
                                ));

    $classes['objPage']         = array($classDir.'class.page.php');
    $classes['objGroups']       = array($classDir.'class.groups.php');
    $classes['objForm']         = array($classDir.'class.form.php');
    $classes['objTime']         = array($classDir.'class.time.php');

    //funky functionality classes here :D
    $classes['objNotify']       = array($classDir.'class.notify.php');
    $classes['objComments']     = array($classDir.'class.comments.php');

    /**
     * this should allow for some custom classes to be init'd
     * keep in mind you can't use any of the classes that get init'd here
     * untill after they actually get init'd
     */
    $objCore->objPlugins->hook('CMSCore_classes_init', $classes);

    //init these classes
    $doneSetup = $objCore->setup($classes);
    if(!$doneSetup){
        msgDie('FAIL', sprintf($errorTPL, 'Fatal Error',
            'Cannot load CMS Classes, make sure file structure is intact and $cmsROOT is defined properly if applicable.'));
    }

    //globalise the class names
    foreach($objCore->classes as $objName => $args){ $$objName =& $objCore->$objName; }
    unset($classes, $objCore->classes);

    $objPage->setVar('language', $language);

//
//--Generate a 'Template' for the Session
//
    $guest['user'] = array(
        'id'        => 0,
        'username'  => 'Guest',
        'theme'     => $objCore->config('site', 'theme'),
        'userkey'   => doArgs('userkey', null, $_SESSION['user']),
        'timezone'  => doArgs('timezone', $objCore->config('time', 'timezone'), $_SESSION['user']),
    );

    //generate user stuff
    $config['global'] = array(
        'user'      => (isset($_SESSION['user']['id']) ? $_SESSION['user'] : $guest['user']),
        'ip'        => User::getIP(),
        'useragent' => doArgs('HTTP_USER_AGENT', null, $_SERVER),
        'browser'   => getBrowser($_SERVER['HTTP_USER_AGENT']),
        'language'  => $language,
        'secure'    => ($_SERVER['HTTPS'] ? true : false),
        'referer'   => doArgs('HTTP_REFERER', null, $_SERVER),
        'rootPath'  => '/'.root(),
        'fullPath'  => $_SERVER['REQUEST_URI'],
        'rootUrl'   => ($_SERVER['HTTPS'] ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].'/'.root(),
        'url'       => ($_SERVER['HTTPS'] ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
    );

    //hook the session template, this is the place to add some more if you want
    $objPlugins->hook('CMSCore_session_tpl', $config['global']);

    $objUser->setIsOnline(!($config['global']['user']['id'] == 0 ? true : false));
    $objUser->initPerms();

    if(!defined('NO_DB')){
        //start the tracker, this sets out a few things so we can kill, ban etc
        $objCore->objUser->tracker();
    }

    $theme = !User::$IS_ONLINE || !$objCore->config('site', 'theme_override')
                ? $objCore->config('site', 'theme')
                : $objUser->grab('theme');

    if(!$objPage->setTheme($theme)){
        msgDie('FAIL', sprintf($errorTPL, 'Fatal Error', 'Cannot find template. Please make sure atleast default/ is uploaded correctly and try again.'));
    }

    if(is_file(cmsROOT.'modules/core/lang.'.$language.'.php')){
        translateFile(cmsROOT.'modules/core/lang.'.$language.'.php');
    }

    //include the templates settings, these will assign them to an array in the page class
    if(is_readable(Page::$THEME_ROOT.'settings.php')){ include(Page::$THEME_ROOT.'settings.php'); }

    //this sets the global theme vars
    $objPage->setThemeVars();

    //set a default breadcrumb
    $objPage->addPagecrumb(array(
        array('url' => '/'.root(), 'name' => langVar('B_MAINSITE')),
    ));

//
//--Setup modules, online system and bbcode stuffz
//
    //
    //--BBCode Setup
    //
        $objBBCode = new BBCode;
        $objBBCode->SetDebug(true);
        $objBBCode->SetDetectURLs(false);
        $objBBCode->ClearSmileys();
        $objBBCode->SetSmileyDir('/'.root().'images/smilies/');
        $file = cmsROOT.'core/bbcode_tags.php';
        if(is_readable($file)){
            require_once($file);
        }else{ hmsgDie('FAIL', 'Fatal Error - BBCode\'s not available.'); }

    //
    //--Module Setup
    //
        $file = cmsROOT.'core/classes/class.module.php';
        if(is_readable($file)){
            require_once($file);
        }else{ hmsgDie('FAIL', 'Fatal Error - Modules cannot be loaded.'); }

    //if site is closed, make it so, kill debug, no menu is needed, 'cmsCLOSED' can be used as a bypass
    if (($objCore->config('site', 'site_closed') == 1) && (!defined('cmsCLOSED'))){
        if($objUser->grab('userlevel') != ADMIN){
            $objSQL->debug = false;
            $objPage->setMenu(false);
            $objPage->setTitle('DISABLED');
            hmsgDie('INFO', 'Site has been disabled. '.contentParse("\n".$objCore->config('site', 'closed_msg')));
        }else{
            $objTPL->assign_block_vars('__MSG', array(
                'MESSAGE' => langVar('L_MAINTENANCE'),
            ));
        }
    }
//
//--Include the CMS's internal CRON
//
    $file = cmsROOT.'core/cron.php';
    if(is_readable($file)){
        require_once($file);
    }else{ hmsgDie('FAIL', 'Fatal Error - Cron cannot be found.'); }

