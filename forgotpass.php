<?php

/*======================================================================*\
|              Cybershade CMS - Your CMS, Your Way                     |
\*======================================================================*/
define( 'INDEX_CHECK', 1 );
define( 'CMS_DEBUG', 0 );
define( 'CMS_MENU', 'login' );
include 'core/core.php';
$objPage->setTitle( 'ForgotPass' );
if ( IS_ONLINE ) {
    $objPage->redirect( cmsROOT . 'index.php' );
}

$mode = isset( $_GET['action'] ) ? $_GET['action'] : '';
$error = isset( $_GET['error'] ) && is_number( $_GET['error'] ) ? $_GET['error'] :
    '';

$p = $objSQL->prefix();
$objPage->addPagecrumb( array( array( 'url' => '/' . root() . 'login.php',
    'name' => langVar( 'B_LOGIN' ) ), array( 'url' => '/' . root() .
    'forgotpass.php', 'name' => langVar( 'B_FORGOTPASS' ) ), ) );

$objPage->showHeader( ( isset( $_GET['ajax'] ) ? true : false ) );

switch ( $mode ) {
    case 'change':
        $usr = $objSQL->getLine( "
            SELECT id, email, language, password_update
                FROM $Pusers
                WHERE usercode = '%s' LIMIT 1;", array(
                $objSQL->escape( $_GET['check'] )

        ));

        if ( $usr['password_update'] == 1 ) {
            $newpwd = randcode( 8 );
            $quupdate = $objUser->setPassword( $usr['id'], $newpwd );

            if ( $quupdate ) {
                $message = langVar( 'E_PASSUPDATED' ) . '

[pre][noparse]+----------+----------------
| ' . langVar( 'USERNAME' ) . ' :[/noparse] [b]' . $usr['username'] .
                    '[/b][noparse]
| ' . langVar( 'PASSWORD' ) . ' :[/noparse] [b]' . $newpwd . '[/b][noparse]
+----------+----------------[/noparse][/pre]

~' . langVar( 'E_CMS_COPY', $config['site']['title'] ) . '';

                if ( !sendMail( $usr['email'], $config['site']['title'] . ' Password Updated', contentParse( $message ) ) ) {
                    msgDie( 'FAIL', langVar( 'MAIL_FAILED' ) );
                    exit;
                }

                $quupdate = $objSQL->updateRow( 'users', array(
                    'password_update' => '1'
                ), "username = '%s'", array(
                    $objSQL->escape( $_POST['username'] )
                ));


                msgDie( 'OK', langVar( 'PASS_UPDATED' ) );
            } else {
                msgDie( 'FAIL', 'There was a problem and your password wasnt updated.' );
            }
        } else {
            msgDie( 'FAIL', 'User hasnt forgotten their password.' );
        }
        break;

    default:
        if ( HTTP_POST ) {
            if ( isset( $_POST['username'] ) && !empty( $_POST['username'] ) ) {
                $query = $objSQL->getLine( 'SELECT * FROM $Pusers WHERE username = "%s" LIMIT 1;', array(
                    trim( $objSQL->escape( $_POST['username'] ) )
                ));
            } elseif ( isset( $_POST['email'] ) && !empty( $_POST['email'] ) ) {
                    $query = $objSQL->getLine( 'SELECT * FROM $Pusers WHERE email = "%s" LIMIT 1', array(
                        $objSQL->escape( $_POST['email'] )
                    ));
                } else {
                    msgDie( 'FAIL', 'Username or Email Fields were empty. <br /><a href="javascript:history.go(-1);" />Click Here</a> to go back.' );
                }

                if ( count( $query ) && isset( $query['usercode'] ) ) {
                    //captcha checks
                    if ( isset( $objCAPTCHA ) ) {
                        if ( $objCAPTCHA->checkAnswer( User::getIP(), $_POST['recaptcha_challenge_field'],
                            $_POST['recaptcha_response_field'] ) === false ) {
                            msgDie( 'FAIL', 'The captcha you provided was incorrect. <br /><a href="javascript:history.go(-1);" />Click Here</a> to go back.' );
                        }
                    }

                    $message = langVar( 'E_EREQEST', $objUser->getIP() ) . '' . langVar( 'E_REQVISITURL' ) . '[url]http://' . $_SERVER['HTTP_HOST'] . '/' . root() . 'forgotpass.php?action=change&check=' . $query['usercode'] . '[/url]~' . langVar( 'E_CMS_COPY', $objPage->getSetting( 'site', 'title' ) ) . '';

                    if ( !sendMail( $query['email'], $objPage->getSetting( 'site', 'title' ) . ' Forgot Password Request', contentParse( $message ) ) ) {
                        msgDie( 'FAIL', langVar( 'MAIL_FAILED' ) );
                        exit;
                    }

                    $quupdate = $objSQL->updateRow( 'users', array(
                        'password_update' => '1'
                    ), 'username = "%s"', array(
                        $objSQL->escape( $_POST['username'] )
                    ));

                    msgDie( 'OK', langVar( 'MAIL_SENT' ) );
                }
            msgDie( 'FAIL', 'Error: Could not get info for user.' . '<br /><a href="javascript:history.go(-1);" />Click Here</a> to go back.' );
        } else {

            $objTPL->set_filenames( array(
                'body' => 'modules/login/template/forgotpwd_1.tpl'
            ));

            switch ( $error ) {
                default:
                    $L_ERROR = '';
                    break;
                case 1:
                    $L_ERROR = langVar( 'USER_OR_PASSWORD' );
                    break;
                case 2:
                    $L_ERROR = langVar( 'INACTIVE_ACCT' );
                    break;
                case 3:
                    $L_ERROR = langVar( 'WRONG_CAPTCHA' );
                    break;
                case 4:
                    $L_ERROR = langVar( 'INC_EMAIL' );
                    break;
            }

            if ( !empty( $_ERROR ) ){
                $objTPL->assign_block_vars( 'error', array(
                    'ERROR' => $L_ERROR
                ));
            }

            $objTPL->assign_vars( array(
                'PAGE' => langVar( 'FORGOT_PASS' ),
                'FORGOT_MSG' => langVar( 'FORGOT_PASS_MSG' ),
                'FORM_START' => $objForm->start( 'pass', 'POST', ( isset ( $_GET['ajax'] ) ) ? '?ajax' : '?' ),
                'FORM_END' => $objForm->finish(),
                'SUBMIT' => $objForm->button( 'Submit', 'submit' ),
                'RESET' => $objForm->button( 'Reset', 'reset' )
            ));

            $fields = array(
                array(
                    'lang' => '<h4>Either</h4>',
                    'field' => ''
                ), array(
                    'lang' => langVar( 'USERNAME' ) . ':',
                    'field' => $objForm->inputbox( 'input', '', 'username' )
                ), array(
                    'lang' => '<h4>OR</h4>',
                    'field' => ''
                ), array(
                    'lang' => langVar( 'EMAIL' ) . ':',
                    'field' => $objForm->inputbox( 'input', '', 'email' )
                )
            );

            foreach ( $fields as $feild ) {
                $objTPL->assign_block_vars( 'form', array(
                    'KEY' => $feild['lang'],
                    'VALUE' => $feild['field']
                ));
            }

            if ( isset( $objCAPTCHA ) ) {
                $objCAPTCHA->outputCaptcha();
            }

            $objTPL->pparse( 'body' );
        }
        break;
}


$objPage->showFooter( ( isset( $_GET['ajax'] ) ? true : false ) );
