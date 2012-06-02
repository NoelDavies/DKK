<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }
if(!defined('PANEL_CHECK')){ die('Error: Cannot include panel from current location.'); }
$objPage->setTitle(langVar('B_ACP').' > '.langVar('L_CORE_SETTINGS'));
$objPage->addPagecrumb(array( array('url' => $url, 'name' => langVar('L_CORE_SETTINGS')) ));
$objTPL->set_filenames(array(
    'body'         => 'modules/core/template/panels/panel.settings.tpl',
    'dateformat'   => 'modules/core/template/dateFormat.tpl',
));

$objTPL->assign_vars(array('ADMIN_MODE' => langVar('L_CORE_SETTINGS')));


$page = doArgs('page', null, $_GET);
switch(strtolower($mode)){
    default:
        //set some security crap
        $_SESSION['site']['acp_edit']['sessid'] = $sessid = md5($uid.time());
        $_SESSION['site']['acp_edit']['id'] = $uid;


        $yn = array(1 => langVar('L_YES'), 0 => langVar('L_NO'));
        $fields = array();

        //make it somewhat easier, this one got split into 2 'pages'
        if(is_empty($page)){
            //this page is the default one, site settings

            //get a list of modules with the showMain()
            $defaultModule = array();
            $dir = cmsROOT.'modules';
            foreach(getFiles($dir) as $file){
                //make sure theres no directories or anything we dont want...index.php for eg
                if($file=='.' || $file=='..' || $file['name']=='index.php' || $file['name']=='index.html'){
                    continue;
                }

                //make sure we have a config and module file
                if(!file_exists($dir.'/'.$file['name'].'/cfg.php') ||
                    !file_exists($dir.'/'.$file['name'].'/class.'.$file['name'].'.php')){ continue; }

                //make sure the needed function exists within the class
                if(preg_match('/function\sshowMain\(/is', file_get_contents($dir.'/'.$file['name'].'/class.'.$file['name'].'.php'))){
                    include ($dir.'/'.$file['name'].'/cfg.php');
                    $defaultModule[$file['name']] = $mod_name.' V'.$mod_version;
                }
            }
            $tzDisable = false;
            if(is_empty($defaultModule)){
                $defaultModule[] = 'No Modules have Index Abilities';
                $tzDisable = true;
            }

            //generate a select box for the timezones
            $timezone_array = array(
                '-12.0', '-11.0',
                '-10.0', '-9.0', '-8.0', '-7.0', '-6.0', '-5.0',
                '-4.0', '-3.5', '-2.0', '-1.0', '0.0',
                '1.0', '2.0', '3.0', '3.5', '4.0', '4.5', '5.0',
                '5.5', '6.0', '6.5', '7.0', '8.0', '9.0', '9.5', '10.0',
                '11.0', '12.0'
            );

            $tcount = count($timezone_array);
            $timezone = '';
            $timezone .= '<select name="timezone" id="timezone" class="chzn-select" data-search="true">'."\n";
            $option = "\t".'<option value="%1$s"%2$s>GMT %1$s</option>'."\n";
            foreach($timezone_array as $tzone){
                $timezone .= sprintf($option, $tzone, ($objCore->config('time', 'timezone')===$tzone ? ' selected="selected"' : ''));
            }
            $timezone .= '</select>';

            //gather the language selections
            $languages = array();
            $dir = cmsROOT.'languages';
            foreach(getFiles($dir) as $file){
                if($file!='.' && $file!='..' && $file['name']!='index.php' && $file['name']!='index.html'){
                    if(file_exists($dir.'/'.$file['name'].'/cfg.php')){
                        include ($dir.'/'.$file['name'].'/cfg.php');
                        $languages[$file['name']] = $mod_name.' V'.$mod_version;
                    }
                }
            }

            //generate a list of themes
            $tpl = array();
            $dir = cmsROOT.'themes';
            foreach(getFiles($dir) as $file){
                if($file!='.' && $file!='..' && $file['name']!='index.php' && $file['name']!='index.html'){
                    if(file_exists($dir.'/'.$file['name'].'/cfg.php')){
                        include ($dir.'/'.$file['name'].'/cfg.php');
                        $tpl[$file['name']] = $mod_name.' V'.$mod_version;
                    }
                }
            }

            $fields = array(
                langVar('L_SITE_CONFIG')            => '_header_',
                    langVar('L_SITE_TITLE')         => $objForm->inputbox('title', 'text', $objCore->config('site', 'title')),
                    langVar('L_SITE_SLOGAN')        => $objForm->inputbox('slogan', 'text', $objCore->config('site', 'slogan')),
                    langVar('L_ADMIN_EMAIL')        => $objForm->inputbox('admin_email', 'text', $objCore->config('site', 'admin_email')),
                    langVar('L_GANALYTICS')         => $objForm->inputbox('google_analytics', 'input', $objCore->config('site', 'google_analytics')),

                langVar('L_CUSTOMIZE')              => '_header_',
                    langVar('L_INDEX_MODULE')       => $objForm->select('index_module', $defaultModule,
                                                        array('disabled' => $tzDisable, 'selected' => $objCore->config('site', 'index_module'))),
                    langVar('L_DEF_LANG')           => $objForm->select('language', $languages,
                                                        array('selected' => $objCore->config('site', 'language'))),
                    langVar('L_DEF_THEME')          => $objForm->select('theme', $tpl,
                                                        array('selected' => $objCore->config('site', 'theme'))),
                    langVar('L_THEME_OVERRIDE')     => $objForm->radio('theme_override', $yn, $objCore->config('site', 'theme_override')),
                    langVar('L_SITE_TZ')            => $timezone,
                    langVar('L_DST')                => $objForm->radio('dst', $yn, $objCore->config('time', 'dst')),
                    langVar('L_DEF_DATE_FORMAT')    => $objForm->inputbox('default_format', 'input', $objCore->config('time', 'default_format')),
            );

        }elseif($page == 'login'){ //if(is_empty($page))
            //this one is the login & registration settings
            $fields = array(
               langVar('L_REG_LOGIN')              => '_header_',
                    langVar('L_USERNAME_EDIT')      => $objForm->radio('username_change', $yn, $objCore->config('user', 'username_change')),
                    langVar('L_ALLOW_REGISTER')     => $objForm->radio('allow_register', $yn, $objCore->config('site', 'allow_register')),
                    langVar('L_EMAIL_ACTIVATE')     => $objForm->radio('register_verification', $yn, $objCore->config('site', 'register_verification')),
                    langVar('L_MAX_LOGIN_TRIES')    => $objForm->select('max_login_tries',
                                                        range($objCore->config('login', 'max_login_tries')-5, $objCore->config('login', 'max_login_tries')+5),
                                                        array('selected' => $objCore->config('login', 'max_login_tries'), 'noKeys'=>true)),
                    langVar('L_REMME')              => $objForm->radio('remember_me', $yn, $objCore->config('login', 'remember_me')),
            );
        }

        //output and processing is one all the same page so it'll return back to whatever page was accessed once saved
        include($path.'/cfg.php');
        $objForm->outputForm(array(
            'FORM_START'    => $objForm->start('panel', array('method' => 'POST', 'action' => $saveUrl)),
            'FORM_END'      => $objForm->finish(),

            'FORM_TITLE'    => $mod_name,
            'FORM_SUBMIT'   => $objForm->button('submit', 'Submit'),
            'FORM_RESET'    => $objForm->button('reset', 'Reset'),

            'HIDDEN'        => $objForm->inputbox('sessid', 'hidden', $sessid).$objForm->inputbox('id', 'hidden', $uid),
        ),
        array(
            'field' => $fields,
            'desc' => array(
                    langVar('L_INDEX_MODULE')       => langVar('L_DESC_IMODULE'),
                    langVar('L_SITE_TZ')            => langVar('L_DESC_SITE_TZ'),
                    langVar('L_DEF_DATE_FORMAT')    => langVar('L_DESC_DEF_DATE'),
                    langVar('L_DEF_THEME')          => langVar('L_DESC_DEF_THEME'),
                    langVar('L_THEME_OVERRIDE')     => langVar('L_DESC_THEME_OVERRIDE'),
                    langVar('L_ALLOW_REGISTER')     => langVar('L_DESC_ALLOW_REGISTER'),
                    langVar('L_EMAIL_ACTIVATE')     => langVar('L_DESC_EMAIL_ACTIVATE'),
                    langVar('L_MAX_LOGIN_TRIES')    => langVar('L_DESC_MAX_LOGIN'),
                    langVar('L_REMME')              => langVar('L_DESC_REMME'),
                    langVar('L_GANALYTICS')         => langVar('L_DESC_GANALYTICS'),
            ),
            'errors' => $_SESSION['site']['panel']['error'],
        ),
        array(
            'header' => '<h4>%s</h4>',
            'dedicatedHeader' => true,
            'parseDesc' => true,
        ));
    break;

    case 'save':
        if (!HTTP_POST && !HTTP_AJAX){
            hmsgDie('FAIL', 'Error: Cannot verify information.');
        }

        //security check 1
        if(doArgs('id', false, $_POST) != $_SESSION['site']['acp_edit']['id']){
            hmsgDie('FAIL', 'Error: I cannot remember what you were saving...hmmmm');
        }
        //security check 2
        if(doArgs('sessid', false, $_POST) != $_SESSION['site']['acp_edit']['sessid']){
            hmsgDie('FAIL', 'Error: I have conflicting information here, cannot continue.');
        }

        //generate an array of what we are expecting
        $settings = array(
            'site' => array(
                'title', 'slogan', 'admin_email', 'index_module', 'language', 'theme',
                'theme_override', 'allow_register', 'register_verification', 'google_analytics'
            ),
            'time'  => array('dst', 'timezone', 'default_format'),
            'login' => array('max_login_tries', 'remember_me'),
            'user'  => array('username_change'),
        );

        //do a loop through
        $update = array();
        foreach($settings as $array => $settings){
            foreach($settings as $setting){
                $postSetting = doArgs($setting, null, $_POST);
                if($postSetting === null){ continue; }

                if(md5($postSetting) != md5($objCore->config($array, $setting, 'true'))){
                    $update[$setting] = $postSetting;
                }
            }
        }

        //if we have stuff to update
        if(count($update)){
            foreach($update as $setting => $value){
                $updateRow = $objSQL->updateRow('config', array('value'=>$value), array('var = "%s"', $setting));
                    if($updateRow===false){
                        $failed[$setting] = $objSQL->error();
                    }
            }
        }

        //if we have a setting that failed, let the user know
        if(!is_empty($failed)){
            $msg = null;
            foreach($failed as $setting => $error){
                $msg .= $setting.': '.$error.'<br />';
            }

            $objPage->redirect($url, 7);
            hmsgDie('FAIL', langVar('L_SET_NOT_UPDATED', $msg));
        }

        //unset the panel info and reset the cache
        unset($_SESSION['site']['panel']);
        $objCache->regenerateCache('config');

        //and redirect back
        $objPage->redirect($url, 3);
        hmsgDie('OK', langVar('L_SET_UPDATED'));
    break;
}

$objTPL->parse('body', false);
?>