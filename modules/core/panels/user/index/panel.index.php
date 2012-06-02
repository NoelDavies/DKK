<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }
if(!defined('PANEL_CHECK')){ die('Error: Cannot include panel from current location.'); }
$objPage->setTitle(langVar('B_ACP').' > '.langVar('L_OVERVIEW'));
$objPage->addPagecrumb(array( array('url' => $url, 'name' => langVar('L_OVERVIEW')) ));
$objTPL->set_filenames(array(
    'body'  => 'modules/core/template/panels/panel.admin_overview.tpl',
));

$objTPL->assign_vars(array(
    'ADMIN_MODE' => langVar('L_SITE_OVERVIEW'),
));

$objTPL->assign_block_vars('msg', array(
    'MSG' => msg('INFO', 'This panel is currently incomplete, please check panel source for info on this panel.', 'return'),
));

/*
Functionality of the ACP Dashboard -
    A, Dashboard Menu                           |-------------------------------------------|
    B, Interactive Graph                        | |---------------------------------------| |
        |- Registered Users                     | |--A------------------------------------| |
        |- Posted Content?                      | |---------------------------------------| |
        |- User Inactivity Count                |                                           |
        |-                                      | |------------------------------||-------| |
    C, Latest Updates / Quick Notifications     | |                              ||   C   | |
    D, http://i.imgur.com/M9vzG.jpg             | |              B               ||-------| |
    E, Who is online                            | |                              |          |
        |- Tabs                                 | |------------------------------||-------| |
            |- Guests                           |                                 |       | |
            |- Search engine bots               | |------------------------------||   D   | |
            |- Users                            | |              E               ||       | |
                                                | |------------------------------||-------| |
                                                |-------------------------------------------|
*/



$objTPL->parse('body', false);
?>