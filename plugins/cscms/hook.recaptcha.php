<?php
if(!defined('INDEX_CHECK')){die('Error: Cannot access directly.');}

function form_recaptcha($args){
    global $objTPL;

    $file = 'plugins/cscms/class.recaptcha.php';
    if(!is_file($file) || !is_readable($file)){
        msgDie('FAIL', 'Fatal Error - 404'.'<br />We have been unable to locate/read the '.$file.' file.');
    }else{ require_once($file); }

    if(class_exists('Captcha', false) && !is_empty($objTPL->config('site', 'captcha_pub')) && !is_empty($objTPL->config('site', 'captcha_priv'))){
        $objCAPTCHA = new Captcha($objTPL->config('site', 'captcha_pub'), $objTPL->config('site', 'captcha_priv'));
        $objCAPTCHA->objTPL = $objTPL;
    }else{
        return false;
    }

    if(!HTTP_POST){
        return $objCAPTCHA->outputCaptcha($args);
    }else{
        return $objCAPTCHA->checkAnswer(User::getIP(), $_POST['recaptcha_challenge_field'], $_POST['recaptcha_response_field']);
    }

    return false;
}

$this->addHook('CMSForm_Captcha', 'form_recaptcha');


function recaptcha_announce(&$args){
    $args['recaptcha'] = langVar('L_RECAPTCHA');

    return true;
}

$this->addHook('CMSConfig_CaptchaAnnounce', 'recaptcha_announce');
?>