<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

class Captcha extends coreClass{

    var $error;
    var $public_key = NULL;
    var $private_key = NULL;
    var $recaptcha_api_server = 'http://api.recaptcha.net';
    var $recaptcha_api_secure_server = 'https://api-secure.recaptcha.net';
    var $recaptcha_verify_server = 'api-verify.recaptcha.net';

    function Captcha($pubKey, $privKey){
        $this->public_key = $pubKey;
        $this->private_key = $privKey;
    }

    function captcha_encode($data){
        $req = NULL;
        foreach ($data as $key => $value)
            $req .= $key.'='.urlencode(stripslashes($value)).'&';

        $req = substr($req, 0, strlen($req) - 1);
        return $req;
    }

    ////////////////////////////////////////////////////////
    // Function:         captcha_http_post
    // Description: Submits an HTTP POST to reCAPTCHA server

    function captcha_http_post($host, $path, $data, $port = 80){
        $req = $this->captcha_encode($data);

        $http_request  = 'POST '.$path." HTTP/1.0\r\n";
        $http_request .= 'Host: '.$host."\r\n";
        $http_request .= 'Content-Type: application/x-www-form-urlencoded;'."\r\n";
        $http_request .= 'Content-Length: '.strlen($req)."\r\n";
        $http_request .= 'User-Agent: reCAPTCHA/PHP'."\r\n";
        $http_request .= "\r\n";
        $http_request .= $req;

        $response = '';
        if(false == ($fs = @fsockopen($host, $port, $errno, $errstr, 10)))
            die('Could not open socket');

        fwrite($fs, $http_request);

        while(!feof($fs))
            $response .= fgets($fs, 1160);

        fclose($fs);
        $response = explode("\r\n\r\n", $response, 2);

        return $response;
    }

    ////////////////////////////////////////////////////////
    // Function:         captcha_get_html
    // Description: Gets the challenge HTML

    function getHtml($use_ssl = false){
        if(is_empty($this->public_key))
            die('To use reCAPTCHA you must get an API key from <a href="https://www.google.com/recaptcha/admin/create">https://www.google.com/recaptcha/admin/create</a>');

        if($use_ssl){
            $server = $this->recaptcha_api_secure_server;
        }else{
            $server = $this->recaptcha_api_server;
        }

        $errorpart = '';
        if($this->error)
            $errorpart = '&amp;error='.$this->error;


        $this->objTPL->set_filenames(array(
            'recaptcha'    => 'modules/core/template/recaptcha.tpl'
        ));

        $this->objTPL->assign_vars(array(
            'ERR' => $this->error,
            'PUBLIC_KEY' => $this->public_key,
        ));

        return $this->objTPL->get_html('recaptcha');
    }

    function checkAnswer($remoteip, $challenge, $response, $extra_params = array()){
        if($this->private_key == null || $this->private_key == '')
            die('To use reCAPTCHA you must get an API key from <a href="https://www.google.com/recaptcha/admin/create">https://www.google.com/recaptcha/admin/create</a>');

        if($remoteip == null || $remoteip == '')
            die('Your IP could not be determined. Script Terminated.');

        if($challenge == null || strlen($challenge) == 0 || $response == null || strlen($response) == 0){
            $this->error = 'incorrect-captcha-sol';
                return false;
        }

        $response = $this->captcha_http_post(
                        $this->recaptcha_verify_server,
                        '/verify',
                        array(
                            'privatekey'    => $this->private_key,
                            'remoteip'      => $remoteip,
                            'challenge'     => $challenge,
                            'response'      => $response
                        )+$extra_params
                    );

        $answers = explode("\n", $response[1]);

        if(trim($answers[0]) == 'true'){
            return true;
        }else{
            $this->error = $answers[1];
            return false;
        }
    }

    function outputCaptcha($key){
        $a = array(
            'captcha'    => $this->getHtml(false),
            'desc'       => '<div id="audio"><a href="#" onclick="Recaptcha.reload(); return false;">Can\'t see the image ?</a><br />'.
                                '<a href="#" onclick="javascript:Recaptcha.switch_type(\'audio\'); document.getElementById(\'audio\').style.display = \'none\';document.getElementById(\'image\').style.display = \'block\';">Get audio captcha</a></div>'.
                                '<div id="image" style="display: none;"><br />'.
                                    '<a href="#" onclick="Recaptcha.reload(); return false;">Can\'t hear the sound ?</a><br />'.
                                    '<a href="#" onclick="javascript:Recaptcha.switch_type(\'image\'); document.getElementById(\'image\').style.display = \'none\';document.getElementById(\'audio\').style.display = \'block\';">Get image captcha</a></div>',
        );

        return (isset($a[$key]) ? $a[$key] : false);
    }

    function getSignupUrl($domain = null, $appname = null) {
        return "https://www.google.com/recaptcha/admin/create?".$this->captcha_encode(array('domain' => $domain, 'app' => $appname));
    }
}
?>