<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){die('Error: Cannot access directly.');}

/**
 * This class handles page generation
 *
 * @version 1.0
 * @since   1.0.0
 * @author  xLink
 */
class page extends coreClass{

    static $THEME = '', $THEME_ROOT = '';
    private $jsFiles = array(), $cssFiles = array(), $jsCode = array(), $cssCode = array();
    protected $tplVars = array(), $pageCrumbs = array();
    public $acpThemeROOT = 'core/coreThemes/acp/', $acpMode = false;

    /**
     * Init class, and set some options
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     */
    public function __construct(){
        $this->setVars(array(
            'simpleTpl' => false,
            'pageTitle'    => '',
        ));
    }

    /**
     * Sets the current Page's title
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string  $value
     */
    public function setTitle($value){
        $this->setVar('pageTitle', secureMe($value));
    }

    /**
     * Merges values with tplVars
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string  $value
     */
    public function updateTplVars($values){
        $this->tplVars = array_merge($this->tplVars, $values);
    }

    /**
     * Sets the menu to the version that is wanted.
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string  $moduleName
     * @param   string  $page_id
     */
    public function setMenu($moduleName, $page_id='default'){
        $this->moduleMenu = array('module' => $moduleName, 'page_id' => $page_id);
    }

    /**
     * Adds a JS File to be output
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string  $file
     */
    public function addJSFile($file){
        if(!is_array($file)){
            $file = array($file);
        }
        if(!is_array($this->jsFiles)){
            $this->jsFiles = array();
        }

        $this->jsFiles = array_merge($this->jsFiles, $file);
    }

    /**
     * Adds a CSS File to be output
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string  $file
     */
    public function addCSSFile($file){
        if(!is_array($file)){
            $file = array($file);
        }
        if(!is_array($this->cssFiles)){
            $this->cssFiles = array();
        }

        $this->cssFiles = array_merge($this->cssFiles, $file);
    }

    /**
     * Adds a string of JS to be output
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string $code
     */
    public function addJSCode($code){
        if(!is_array($code)){
            $code = array($code);
        }
        if(!is_array($this->jsCode)){
            $this->jsCode = array();
        }

        $this->jsCode = array_merge($this->jsCode, $code);
    }

    /**
     * Adds a string of CSS to be output
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string $code
     */
    public function addCSSCode($code){
        if(!is_array($code)){
            $code = array($code);
        }
        if(!is_array($this->cssCode)){
            $this->cssCode = array();
        }

        $this->cssCode = array_merge($this->cssCode, $code);
    }

    /**
     * Adds a pagecrumb to the array
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   array     $value
     */
    function addPagecrumb(array $value){
        $this->pageCrumbs = array_merge($this->pageCrumbs, $value);
    }

    /**
     * Outputs the pagecrumbs
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @return  string
     */
    public function showPagecrumbs(){
        $breadcrumbs = $this->pageCrumbs;
        if(is_empty($breadcrumbs)){ return null; }

        //setup some vars
        $counter = count($breadcrumbs);
        $x = $counter;
        $return = '';
        $glue = ' >> ';

        //loop through each breadcrumb
        foreach ($breadcrumbs as $k => $link) {
            //if its empty, minus one to $x, and continue
            if(is_empty($link['name'])){
                $x--;
            }else{
                //set the string up
                $string = '<a href="%s">%s</a>';

                //secure the string up
                $link['name'] = secureMe($link['name']);

                //if its the last one, make it bold
                if(($x-1) == $k){ $link['name'] = '<b>'. $link['name'] .'</b>'; }

                //set the string up properly
                $return .= sprintf($string, $link['url'], $link['name']);

                //set the glue if its not the last one
                if(($x-1) != $k){ $return .= $glue; }
            }
        }

        return $return;
    }

    /**
     * Redirect using PHP Header function or JS redirect
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink

     * @param   string    $location
     * @param   int     $time
     * @param   int        $mode         Definitions - 1=>GET['redirect'], 2=>HTTP_REFFERER, => 0=>$location
     */
    public function redirect($location=null, $time=0, $mode=0){
        switch($mode) {
            case 1:
                $url = doArgs('redirect', $location, $_GET);
            break;

            case 2:
                $url = $this->config('global', 'referer');
            break;

            case 0:
            default:
                $url = $location;
            break;
        }

        //check to see weather headers have already been sent, this prevents us from using the header() function
        if(!headers_sent() && $time==0) {
            header('Location: '.$url); exit;
        } else { //headers have already been sent, so use a JS and even META equivalent
            echo '<script type="text/javascript">';
            if($time!=0){
                echo 'function redirect(){';
            }
            echo '  window.location.href="'.$url.'";';
            if($time!=0){
                echo '} setTimeout(\'redirect()\', '.($time*1000).');';
            }
            echo '</script>';
            echo '<noscript>';
            echo '  <meta http-equiv="refresh" content="'.$time.';url='.$url.'" />';
            echo '</noscript>';
        }
    }

    /**
     * Set the Page Theme
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   bool     $theme
     *
     * @return  bool
     */
    public function setTheme($theme=null){
        if(is_empty($theme)){
            $theme = $this->config('site', 'theme');
        }

        if(User::$IS_ONLINE &&
            $this->objUser->grab('theme') &&
            is_dir(cmsROOT.'themes/'.$this->objUser->grab('theme').'/')){

            $theme = $this->objUser->grab('theme');
        }

        if($this->config('site', 'theme_override', false)){
            $theme = $this->config('site', 'theme');
            if(!is_dir(cmsROOT.'themes/'.$theme.'/') || !is_readable(cmsROOT.'themes/'.$theme.'/cfg.php')){
                $theme = 'default';
            }
        }

        if(!is_dir(cmsROOT.'themes/'.$theme.'/') || !is_readable(cmsROOT.'themes/'.$theme.'/cfg.php')){
            return false;
        }

        $langFile = cmsROOT.'themes/'.$theme.'/languages/'.$this->config('global', 'language').'/main.php';
        if(is_file($langFile) && is_readable($langFile)){
            translateFile($langFile);
        }

        self::$THEME = $theme;
        self::$THEME_ROOT = cmsROOT.'themes/'.$theme.'/';

        return true;
    }

    /**
     * Loads the theme header up
     *
     * @version 2.0
     * @since   1.0.0
     *
     * @param   bool $simple
     */
    public function showHeader($simple=false){
        if($this->header['completed']){ return; }

        $themeRoot = self::$THEME_ROOT;
        if($this->getVar('acpMode') === true && is_dir($this->acpThemeROOT)){
            $themeRoot = $this->acpThemeROOT;
            $simple = false;
        }

        //figure out which version of the header we wanna use
        $header = $themeRoot . ($simple==true ? 'simple_header.tpl' : 'header.tpl');

        //set simpleTpl, so anything that needs to output layout other than these funcs know what to expect
        $tplVar = ($this->getVar('simpleTpl')===true ? true : ($simple===true ? true : false) );
        $this->setVar('simpleTpl', $tplVar);

        //set the page header template file
        $this->objTPL->set_filenames(array(
            'tpl_header' => $header
        ));

        //set some vars we need later on
        $nl = "\n"; $js = null; $css = null;
        $jsCode = $jsFiles = $cssCode = $cssFiles = array();

    //
    //--Load Meta Definitions
    //
        //generate an array of meta lines
        $metaArray = array(
            'author'        => $this->config('cms', 'name', 'Cybershade CMS'),
            'description'   => $this->config('site', 'description'),
            'keywords'      => $this->config('site', 'keywords'),
            'copyright'     => langVar('L_SITE_COPYRIGHT', $this->config('site', 'title'), $this->config('cms', 'name'), cmsVERSION),
            'generator'     => $this->config('cms', 'name').' v'.cmsVERSION,
            'ROBOTS'        => 'INDEX, FOLLOW',
            'GOOGLEBOT'     => 'INDEX, FOLLOW',
        );

        //add a hook to it so it can be added to
        $this->objPlugins->hook('CMSPage_meta', $metaArray);


        //set a default value, and go with it
        $meta = '<meta http-equiv="content-type" content="text/html; charset=utf-8" />'.$nl;
        $meta .= '<meta http-equiv="content-language" content="'.$this->config('site', 'language').'" />'.$nl;

		//Adding "maximum-scale=1" fixes the Mobile Safari auto-zoom bug: http://filamentgroup.com/examples/iosScaleBug/
        $meta .= '<meta name="viewport" content="width=device-width, initial-scale=1" />'.$nl;
        if(count($metaArray)){
            foreach($metaArray as $name => $value){
                $meta .= sprintf('<meta name="%s" content="%s" />', $name, $value).$nl;
            }
        }
        $meta .= '<link rel="alternate" type="application/atom+xml" title="'.$this->config('site', 'title').' '.langVar('RSS_FEED').'" href="/'.root().'rss.php" />'.$nl;

    //
    //--Load JS
    //
        //load in the root vars, we do this first so we can use em in the JS files etc
        $js .=
            '<script>'.
            'var cmsROOT = "'.root().'"; var THEME_ROOT = "'.root().page::$THEME_ROOT.'"; '.
            'var User = { username: "'.$this->objUser->grab('username').'", IS_ONLINE: '.(User::$IS_ONLINE ? 'true' : 'false').', IS_MOD: '.(User::$IS_MOD ? 'true' : 'false').', IS_ADMIN: '.(User::$IS_ADMIN ? 'true' : 'false').' }; '.
            'var Page = { row_highlight: "'.$vars['row_highlight'].'", row_color1: "'.$vars['row_color1'].'", row_color2: "'.$vars['row_color2'].'" }; '.
            '</script>'.$nl;

        //files first
        $jsFiles[] = '/'.root().'scripts/framework-min.js';
        $jsFiles[] = '/'.root().'scripts/extras-min.js';
        $jsFiles[] = '/'.root().'scripts/user.js.php';

        //load in the anything thats been passed in via addJSFiles()
        $jsFiles = array_merge($jsFiles, $this->jsFiles);

        //if the template has an extras.js add it
        if(file_exists($themeRoot . 'extras.js')){
            $jsFiles[] = '/'.root().'themes/'. Page::$THEME .'/extras.js';
        }

        //hook here too
        $this->objPlugins->hook('CMSPage_jsFiles', $jsFiles);

        //now add em to the $js
        foreach($jsFiles as $file){
            $js .= sprintf('<script src="%s"></script>', $file).$nl;
        }

        //support for google analytics out of the box
        if($this->config('site', 'google_analytics', false)){
            $jsCode[] = 'var _gaq = _gaq || []; '.
                        '_gaq.push(["_setAccount", "'.$this->config('site', 'google_analytics').'"]); '.
                        '_gaq.push(["_trackPageview"]); '.

                        '(function() { '.
                            'var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true; '.
                            'ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js"; '.
                            'var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s); '.
                        '})(); ';
        }

        //grab the current notifications and if needed output them to the page
        $notifGrab = $this->getVar('notifications'); $notifications = null;
        if(!is_empty($notifGrab)){
            $notifications = '<script type="text/javascript">$(document).ready(function(){ '.$notifGrab.' });</script>';
        }

        //load in the anything thats been passed in via addJSCode()
        $jsCode = array_merge($jsCode, $this->jsCode);

        //hook here too
        $this->objPlugins->hook('CMSPage_jsCode', $jsCode);

        //now add em to the $js
        foreach($jsCode as $code){
            $js .= '<script>'.str_replace(array("\n", "\r", "\t"), '', trim($code)).'</script>'.$nl;
        }

        $headerJs = null; //this is the var we will store this JS
        $header_jsCode = array(); $header_jsFiles = array(); //these are for the hooks to populate

        //process the js files for the header
        $this->objPlugins->hook('CMSPage_header_jsFiles', $header_jsFiles);
        if(count($header_jsFiles)){
            foreach($header_jsFiles as $file){
                $headerJs .= sprintf('<script src="%s"></script>', $file).$nl;
            }
        }

        $this->objPlugins->hook('CMSPage_header_jsCode', $header_jsCode);
        if(count($header_jsCode)){
            foreach($header_jsCode as $code){
                $headerJs .= sprintf('<script>%s</script>', str_replace(array("\n", "\r", "\t"), '', trim($code))).$nl;
            }
        }

    //
    //--Load CSS
    //
        $cssTag = '<link rel="stylesheet" href="%s" />';

        //add the adaptive css file
        $cssFiles[] = '/'.root().'images/grid.adapt.css';

        //we want the default css minified dont forget!
        $cssFiles[] = '/'.root().'images/framework-min.css';
        $cssFiles[] = '/'.root().'images/extras-min.css';

        //load in the anything thats been passed in via addCSSFile()
        $cssFiles = array_merge($cssFiles, $this->cssFiles);

        //hookty hook
        $this->objPlugins->hook('CMSPage_cssFiles', $cssFiles);

        //add it to the $css
        foreach($cssFiles as $file){
            $css .= sprintf($cssTag, $file).$nl;
        }

        //load in the anything thats been passed in via addCSSCode()
        $cssCode = array_merge($cssCode, $this->cssCode);

        //hook in here too
        $this->objPlugins->hook('CMSPage_cssCode', $cssCode);

        //add it to the $css
        foreach($cssCode as $code){
            $css .= '<style>'.$nl.str_replace(array("\n", "\r", "\t"), '', trim($code)).$nl.'</style>'.$nl;
        }

    //
    //--Header Vars
    //
        if(file_exists($themeRoot.'images/favicon.ico')){
            $meta .= '<link rel="shortcut icon" href="/'.root().$themeRoot.'images/favicon.ico" />';
        }

        //if the site is closed, the only way they can get this far is if the user has privs so
        if($this->config('site', 'closed') == 1){
            $this->setVar('HEADER_MSG', langVar('L_MAINTENANCE'));
        }

        //if we want to put out a msg in the header as a warning or something
        $headerMsg = $this->getVar('HEADER_MSG');
        if(!is_empty($headerMsg)){
            $this->objTPL->assign_vars('__MSG', array('MSG' => $headerMsg));
        }

        //get some stuff from the config so they can be called in the template
        $array = array(
            'SITE_TITLE'    => $this->config('site', 'title'),
            'CMS_VERSION'   => cmsVERSION,

            //some template stuff
            'PAGE_TITLE'    => $this->getVar('pageTitle'),
            '_META'         => $meta,

            'L_BREADCRUMB'  => langVar('L_BREADCRUMB'),
            'BREADCRUMB'    => $this->showPagecrumbs().'<span id="ajaxcrumb">&nbsp;</span>',

            '_JS_FOOTER'    => $js . $notifications,
            '_JS_HEADER'    => $headerJs,
            '_CSS'          => $css,
        );

    //
    //--Menu Setup
    //
        global $config;

        $noMenu = false;
        if(defined('NO_MENU') && NO_MENU==true){ $noMenu = true; }

        $menu = $this->getVar('moduleMenu');
        if($menu['module'] === false){ $noMenu = true; }

        //we cant do nothin without any blocks
        if(!$noMenu && !is_empty($config['menu_blocks'])){
            //if it got set to null, or wasnt set atall, default to the core menu
            if(!isset($menu['module']) || is_empty($menu['module'])){ $menu['module'] = 'core'; }
            if(!isset($menu['page_id']) || is_empty($menu['page_id'])){ $menu['page_id'] = 'default'; }

            //then do the output
            $menuSetup = show_menu($menu['module'], $menu['page_id']);
            if($menuSetup){
                $this->objTPL->assign_block_vars('menu', array());
            }
        }else{
            //if we cant show menu, may aswell set the no_menu block
            $this->objTPL->assign_block_vars('no_menu', array());
        }

        //ouput the header and set completed to 1
        $this->objTPL->assign_vars($array);
        $this->objTPL->parse('tpl_header');

        $this->header['completed'] = 1;
    }

    /**
     * Loads the theme footer and debug up if needed
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   bool $simple
     */
    public function showFooter($simple=false){
        global $START_CMS_LOAD, $START_RAM_USE, $config;

        //no need for a footer if the header hasnt been called to
        if(!$this->header['completed']){ return; }

        //add this in just at the top of the block, this'll be echo'd in between the header and footer
        if(!isset($_SESSION['notifications'])){
            echo '<div id="notificationGrabber" style="display:none;"></div>';
        }

        //find which root we want
        $themeRoot = self::$THEME_ROOT;
        if($this->getVar('acpMode') === true && is_dir($this->acpThemeROOT)){
            $themeRoot = $this->acpThemeROOT;
            $simple = false;
        }

        //figure out which version of the footer we wanna use
        if($this->getVar('simpleTpl')){ $simple = true; }
        $footer = $themeRoot . ($simple==true ? 'simple_footer.tpl' : 'footer.tpl');

        //set the page footer template file
        $this->objTPL->set_filenames(array(
            'tpl_footer' => $footer
        ));

    //
    //--Output the Footer
    //
        $queries = 0; $sqlTimer = 0; $crons = null; $debug = $this->objSQL->debugtext;
        if(!is_empty($debug)){
            foreach($debug as $row){
                if($row['time']!= '---------'){ $queries++; }
                if($row['status']  == 'error'){ $queries++; }
                $sqlTimer += $row['time'];
            }
        }

        //check for admin privs and file(debug) existing in the root
        if(User::$IS_ADMIN && !file_exists('debug')){
            //if the debug happened..
            if($this->objSQL->debug && cmsDEBUG){
                //output some debug vars
                if(!is_array($this->debugVars)){
                    $this->debugVars[] = dump($_POST);
                    $this->debugVars[] = dump($_SESSION);
                    $this->debugVars[] = dump($config);
                }

                $this->objTPL->assign_block_vars('debug', array(
                    'DEBUG'    => implode("\n", $this->debugVars),
                ));

                $string = null;
                if(!is_empty($debug)){
                    foreach($debug as $row){
                        $this->objTPL->assign_block_vars('debug.info', array(
                            'CLASS'     => ($counter++%2==0 ? 'row_color1' : 'row_color2'),
                            'TIME'      => $row['time'],
                            'QUERY'     => $row['query']
                        ));
                    }
                }

                //grab the logs and output em if needed
                $logs = $this->objSQL->getTable('SELECT * FROM `$Plogs` ORDER BY id DESC LIMIT 10');
                if(!is_empty($logs)){
                    foreach($logs as $log){
                        $this->objTPL->assign_block_vars('debug.log', array(
                            'DESC'      => $log['description'],
                            'IP'        => $log['ip_address'],
                            'SQL'       => $log['query'],
                            'TIME'      => $this->objTime->mk_time($log['date']),
                        ));
                    }
                }

            }

            $crons = '<br />
            Next Hourly CRON -> '.$this->objTime->mk_time($this->config('statistics', 'hourly_cron')+$this->config('site', 'hourly_time')).'<br />
            Next Daily CRON ->  '.$this->objTime->mk_time($this->config('statistics', 'daily_cron')+$this->config('site', 'daily_time')).'<br />
            Next Weekly CRON -> '.$this->objTime->mk_time($this->config('statistics', 'weekly_cron')+$this->config('site', 'weekly_time')).'<br />
            Current Time:       '.$this->objTime->mk_time(time());
        }

        //here we throw some vars into an array to pass to the footer hook,
        //this will give plugin and module makers critical information about the pages
        $footerVars = array();
        $this->timer = isset($START_CMS_LOAD) ? $START_CMS_LOAD : microtime(true);
        $footerVars['sqlQueries'] = $queries;
        $footerVars['sqlTimer'] = $sqlTimer;
        $footerVars['pageGen'] = round(microtime(true)-$this->timer, 5)-$sqlTimer;
        $footerVars['nextCron'] = $this->objTime->mk_time($this->config('statistics', 'hourly_cron') + $this->config('cron', 'hourly_time'));
        $footerVars['ramUsage'] = formatBytes(memory_get_usage()-$START_RAM_USE);

        $this->objPlugins->hook('CMSPage_footer', $footerVars);

        $page_gen = NULL;
        //i'm not using IS_ADMIN here because it will only be true once the user has auth'd, this is information that isnt mission critical
        //and thus dosent need the auth, but is still handy under the admin switch
        if($this->objUser->grab('userlevel') == ADMIN){
            $page_gen = langVar('L_PAGE_GEN', $footerVars['sqlQueries'], $footerVars['sqlTimer'], $footerVars['pageGen'], $footerVars['ramUsage'], $footerVars['nextCron']);
        }

        $footer = array();
        if(is_readable($themeRoot.'cfg.php')){
            include($themeRoot.'cfg.php');
            $footer += array(
                'L_TPL_INFO' =>  langVar('TPL_INFO', '<a href="'.$mod_url.'">'.$mod_name.'</a>', $mod_author, $mod_version).' | '.langVar('L_LANG_PACK'),
            );
        }
        $footer += array(
            'L_PAGE_GEN'        => $page_gen,
            'L_SITE_COPYRIGHT'  => langVar('L_SITE_COPYRIGHT', $this->config('site', 'title'), $this->config('cms', 'name'), cmsVERSION),
        );


        $this->objTPL->assign_vars($footer);
        $this->objTPL->parse('tpl_footer');
    }

    /**
     * Loads the inital vars for the tpls
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     */
    public function setThemeVars(){
        $vars = $this->getVar('tplVars');

        //incude extras.php from the theme if it exists
        $extras = Page::$THEME_ROOT.'extras.php';
        if(is_readable($extras)){ include($extras); }

        //define array of vars that we want
        $vars = array(
            'ROOT'          => root(),
            'THEME_ROOT'    => root(). Page::$THEME_ROOT,
            'ACP_TROOT'     => root(). $this->acpThemeROOT,

            'SITE_NAME'     => $this->config('site', 'site_name'),

            'ROW_COLOR1'    => $vars['row_color1'],
            'ROW_COLOR2'    => $vars['row_color2'],

            'USERNAME'      => $this->objUser->grab('username'),
            'TIME'          => $this->objTime->mk_time(time(), 'l H:i:s a'),


            'U_UCP'         => '/'.root().'user/',
            'U_LOGIN'       => '/'.root().'login.php',
            'U_LOGOUT'      => '/'.root().'login.php?action=logout&check='.$this->objUser->grab('usercode'),

            'L_UCP'         => langVar('L_UCP'),
            'L_LOGIN'       => langVar('L_LOGIN'),
            'L_LOGOUT'      => langVar('L_LOGOUT'),
        );

        //this needs to show up if we have admin perms and dont have the acp auth atm
        if($this->objUser->grab('userlevel') == ADMIN){
            $vars += array(
                'ACP_LINK' => '- <a href="/'.root().'admin/">'.langVar('L_ACP').'</a>',
            );
        }

        //hook onto the array to allow others to add to this list
        $this->objPlugins->hook('CMSCore_global_tplvars', $vars);

        //if user is online, set the IS_ONLINE, and IS_LOGGED_IN blocks
        if(User::$IS_ONLINE){
            $this->objTPL->assign_block_vars('IS_ONLINE', array());
            $this->objTPL->assign_block_vars('IS_LOGGED_IN', array());
        }

        //if user is not online, set the NOT_LOGGED_IN
        if(!User::$IS_ONLINE){
            $this->objTPL->assign_block_vars('NOT_LOGGED_IN', array());
        }

        //if user is logged in, and is admin
        if(User::$IS_ONLINE && User::$IS_ADMIN){
            $this->objTPL->assign_block_vars('IS_ADMIN', array());
        }

        //merge, assign and unset ^_^
        $vars = (!is_empty($_more_vars) && is_array($_more_vars) ? array_merge($vars, $_more_vars) : $vars);
        $this->objTPL->assign_vars($vars);
        unset($vars);
    }

}
?>