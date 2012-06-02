<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
define('INDEX_CHECK', 1);
define('cmsDEBUG', 0);
include_once('core/core.php');
$objPage->setTitle(langVar('B_REGISTER'));

//no need for them to be here
if(User::$IS_ONLINE){ $objPage->redirect('/'.root().'index.php'); }

if(!$objPage->config('site', 'allow_register')){
    hmsgDie('INFO', 'Error: An administrator has disabled Registrations.');
}

//setup breadcrumbs
$objPage->addPagecrumb(array(
    array('url' => '/'.root().'register.php',  'name' => langVar('B_REGISTER')),
));

if(!HTTP_POST){
    //add our JS in here for the register
    $objPage->addJSFile('/'.root().'scripts/register.js');


    $objPage->showHeader();
        //set the fields to blank if they dont already have a value
        $fields = array('uname', 'password', 'password_verify', 'email');
        foreach($fields as $e){
            $_POST[$e] = $_SESSION['register']['form'][$e];
            if(!isset($_SESSION['register']['form'][$e]) || is_empty($_SESSION['register']['form'][$e])){
                $_POST[$e] = '';
            }
        }

    echo $objForm->outputForm(array(
            'FORM_START'     => $objForm->start('register', array('method'=>'POST', 'action'=>'?')),
            'FORM_END'       => $objForm->finish(),

            'FORM_TITLE'     => 'User Registration',
            'FORM_SUBMIT'    => $objForm->button('submit', 'Submit'),
            'FORM_RESET'     => $objForm->button('reset', 'Reset'),
        ),
        array(
            'field' => array(
                'User Info'			=> '_header_',
                'Username'			=> $objForm->inputbox('username', 'hidden'). //this hidden one is to try and stop spam bots
                                       $objForm->inputbox('uname', 'text', $_POST['username'], array('extra' => 'maxlength="20" size="20"', 'required'=>true)),
                'Password'			=> $objForm->inputbox('password', 'password', $_POST['password'], array('required'=>true)),
                'Verify Password'               => $objForm->inputbox('password_verify', 'password', $_POST['password_verify'], array('required'=>true)),

                'Email'                         => $objForm->inputbox('email', 'text', $_POST['email'], array('required'=>true)),

                'Captcha'			=> '_header_',
                'Recaptcha'			=> $objForm->loadCaptcha('captcha'),
            ),
            'desc' => array(
                'Username'			=> 'This field can be [a-zA-Z0-9-_.]',
                'Recaptcha'			=> $objForm->loadCaptcha('desc').'<br />'.langVar('L_CAPTCHA_DESC'),
            ),
            'errors' => $_SESSION['register']['error'],
        ));

    $objPage->showFooter();
}else{
    $userInfo = array();

    if(is_empty($_POST)){
        $objPage->redirect($objCore->config('global', 'fullPath'), 1, 0);
        msgdie('FAIL', 'Error: Please use the form to submit your registration request.');
    }

    //the normal user cannot see this field so if it has value it is in this case,
    //to be considered as a spam submittion and disregarded
    if(!is_empty($_POST['username'])){
        $objPage->redirect($objCore->config('global', 'fullPath'), 1, 0);
        msgdie('FAIL', 'Error: Spam attempt detected.');
    }

    //run through each of the expected fields and make sure theyre are here
    //we dont add the captcha in here purely cause the admin might hook in another captcha
    //and we wont know what fields it outputs etc
    $fields = array('uname', 'password', 'password_verify', 'email');
    foreach($fields as $e){
        if(!isset($_POST[$e]) || is_empty($_POST[$e])){
            $_error[$e] = 'Please make sure all the fields are populated ('.$e.').';
        }
    }

    //validate the username conforms to site standards
    if(!isset($_error['username']) && !$objUser->validateUsername($_POST['username'])){
        $_error['username'] = 'You have chosen an Username with invalid characters in. Please choose another one.';
    }

    //make sure there isnt already a user in the db with this username
    if(!isset($_error['username']) && strlen($objUser->getUserInfo($_POST['username'], 'username'))>0){
        $_error['username'] = 'You have chosen an Username that already exists. Please choose another one.';
    }

    //validate the email
    if(!isset($_error['email']) && !$objUser->validateEmail($_POST['email'])){
        $_error['email'] = 'The Email address provided couldn\'t be validated properly. Please make sure it is correct and try again.';
    }

    $emailCheck = $objSQL->getTable( 'SELECT email FROM $Pusers WHERE email=\'%s\'', array( $_POST['email'] ) );
    if(!isset($_error['email']) && ( count( $emailCheck ) > 0 )){
        $_error['email'] = 'The Email address provided is invalid. Please make sure it is correct and try again.';
    }

    //check the passwords
    if(!isset($_error['passwords']) && strlen($_POST['password'])<4 || strlen($_POST['password_verify'])<4){
        $_error['passwords'] = 'Your passwords are too small. Please make sure they are longer than 4 characters long.';
    }

    if(!isset($_error['passwords']) && md5($_POST['password'])!=md5($_POST['password_verify'])){
        $_error['passwords'] = 'Your passwords do not match. Please verify them and try again.';
    }

    //validate the captcha
    if($objForm->loadCaptcha('verify')===false){
        $_error['captcha'] = 'The captcha you provided was incorrect. Please try again.';
    }

    if(count($_error)){
        $_SESSION['register']['error'] = $_error;
        $_SESSION['register']['form'] = $_POST;
        $objPage->redirect($objCore->config('global', 'fullPath'), 3, 0);
        exit;
    }
    //set the input array up
    $userInfo['username'] = $_POST['username'];
    $userInfo['password'] = $_POST['password'];
    $userInfo['email'] = $_POST['email'];

    $register = $objUser->register($userInfo);
        if(!$register){
            msgDie('FAIL', $objUser->error());
        }

    if($objPage->config('site', 'register_verification')){
        $user = $objUser->getUserInfo($register);
        $emailVars['URL'] = 'http://'.$_SERVER['HTTP_HOST'].'/'.root().'login.php?action=active&un='.$user['id'].'&check='.$user['usercode'];
        $emailVars['USERNAME'] = $userInfo['username'];
        $emailVars['SITE_NAME'] = $objCore->config('site', 'name');

        sendEmail($userInfo['email'], 'E_REG_SUCCESSFUL', $emailVars);
        $msg = langVar('L_REG_SUCCESS_EMAIL');
    }else{
        $msg = langVar('L_REG_SUCCESS_NO_EMAIL');
    }

    unset($_SESSION['register'], $_SESSION['error'], $query, $userInfo, $_error);
    $objCache->generate_statistics_cache();
    hmsgDie('INFO', $msg);
}
?>