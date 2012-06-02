<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }
if(!defined('PANEL_CHECK')){ die('Error: Cannot include panel from current location.'); }
$objPage->setTitle(langVar('B_ACP').' > '.langVar('L_RECAPTCHA_SETTINGS'));
$objPage->addPagecrumb(array( array('url' => $url, 'name' => langVar('L_RECAPTCHA_SETTINGS')) ));
$objTPL->set_filenames(array(
    'body' => 'modules/core/template/panels/panel.settings.tpl'
));

$objTPL->assign_vars(array('ADMIN_MODE' => langVar('L_RECAPTCHA_SETTINGS')));

switch(strtolower($mode)){
    default:

        //set some security crap
        $_SESSION['site']['acp_edit']['sessid'] = $sessid = md5($uid.time());
        $_SESSION['site']['acp_edit']['id'] = $uid;

        $objTPL->assign_block_vars('msg', array(
            'MSG' => msg('INFO', 'You need to visit <a href="https://www.google.com/recaptcha/admin/create">https://www.google.com/recaptcha/admin/create</a> to get these keys. Without a set of valid keys the Captcha will be disabled on this website. Your website will be vulnerable to spam bot attacks.', 'return')
        ));

        $captcha = array(1=>langVar('L_ENABLE'), 0=>langVar('L_DISABLE'));
        $objForm->outputForm(array(
            'FORM_START'    => $objForm->start('panel', array('method' => 'POST', 'action' => $saveUrl)),
            'FORM_END'      => $objForm->finish(),

            'FORM_TITLE'    => langVar('L_RECAPTCHA'),
            'FORM_SUBMIT'   => $objForm->button('submit', 'Submit'),
            'FORM_RESET'    => $objForm->button('reset', 'Reset'),

            'HIDDEN'        => $objForm->inputbox('sessid', 'hidden', $sessid).$objForm->inputbox('id', 'hidden', $uid),
        ),
        array(
            'field' => array(
                langVar('L_RECAPTCHA')  => $objForm->radio('captcha_enable', $captcha, $objCore->config('site', 'captcha_enable')),
                langVar('L_PUB_KEY')    => $objForm->inputbox('captcha_pub', 'text', $objCore->config('site', 'captcha_pub')),
                langVar('L_PRIV_KEY')   => $objForm->inputbox('captcha_priv', 'text', $objCore->config('site', 'captcha_priv')),
            ),
            'errors' => $_SESSION['site']['panel']['error'],
        ),
        array(
            'header' => '<h4>%s</h4>',
            'dedicatedHeader' => true,
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

        //run through each of the defined settings and make sure they have a value and its not the same as the stored one
        $update = array(); $failed = array();
        $settings = array('captcha_enable', 'captcha_priv', 'captcha_pub');
        foreach($settings as $setting){
            if(doArgs($setting, false, $_POST)!=$objCore->config('site', $setting, true)){
                $update[$setting] = $_POST[$setting];
            }
        }

        //if we have stuff to update
        if(count($update)){
            foreach($update as $setting => $value){
                $update = $objSQL->updateRow('config', array('value'=>$value), array('var = "%s"', $setting));
                    if(!$update){
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