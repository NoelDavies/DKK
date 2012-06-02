<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){die('Error: Cannot access directly.');}

$sql = array();
$now             = $objSQL->escape(time());
$version         = $objSQL->escape(str_replace('V', '', $version));
$admUsername     = $objSQL->escape($_SESSION['adm']['username']);
$admPasswd       = $objSQL->escape($objUser->mkPassword($_SESSION['adm']['password']));
$admEmail        = $objSQL->escape($_SESSION['adm']['email']);
$admKey          = $objSQL->escape(randcode(6));
$ckeauth         = $objSQL->escape(randcode(6));
$dst             = date('I')==0 ? 1 : 0;
$timezone        = 0;
//$userIp = getIP();

    $fields = array('title', 'slogan', 'description', 'keywords', 'time');
    foreach($fields as $f){
        if(doArgs($f, false, $_SESSION['POST'][$f])){ 
            ${$f} = $objSQL->escape($_SESSION['POST'][$f]); continue; 
        }
    }

//
//--Core System
//

//--Config
$sql[] = <<<SQL
DROP TABLE IF EXISTS `cs_config`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_config` (
  `array` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `var` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  KEY `array` (`array`,`var`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_config` (`array`, `var`, `value`) VALUES
    ('db', 'ckeauth', '{$ckeauth}'),
    ('cms', 'name', 'Cybershade CMS'),
    
    ('site', 'title', '{$title}'),
    ('site', 'slogan', '{$slogan}'),
    ('site', 'theme', 'default'),
    ('site', 'language', 'en'),
    ('site', 'keywords', '{$keywords}'),
    ('site', 'description', '{$description}'),
    ('site', 'admin_email', '{$_SESSION[adm][email]}'),
    ('site', 'site_closed', '0'),
    ('site', 'closed_msg', 'Administrator has closed this website.'),
    ('site', 'register_verification', '1'),
    ('site', 'index_module', 'forum'),
    ('site', 'registry_update', '1303735825'),
    ('site', 'allow_register', '1'),
    ('site', 'default_pagination', '10'),
    ('site', 'user_group', '3'),
    ('site', 'google_analytics', ''),
    ('site', 'theme_override', '1'),
    ('site', 'smilie_pack', 'default'),  
    ('site', 'captcha_enable', '0'),
    ('site', 'captcha_pub', ''),
    ('site', 'captcha_priv', ''),
        
    ('time', 'default_format', '{$time}'),
    ('time', 'dst', '{$dst}'),
    ('time', 'timezone', '{$tz}'),
    
    ('user', 'username_change', '0'),
    
    ('login', 'max_login_tries', '8'),
    ('login', 'remember_me', '1'),
    ('login', 'max_whitelist', '5'),
    ('login', 'lockout_time', '15'),    
    ('login', 'ip_lock', '0'),    
    
    ('rss', 'global_limit', '15'),
    
    ('ajax', 'settings', 'forum_eip,forum_sortables'),

    ('email', 'E_USER_POSTED', 'Hello {USERNAME},\r\n\r\n{AUTHOR} has posted a reply to [b]{THREAD_NAME}[/b]. This was posted at {TIME}.\r\n\r\nYou can view the topic by visiting the following URL: [url]{THREAD_URL}[/url].\r\n\r\n~{SITE_NAME}'),
    ('email', 'E_LOGIN_ATTEMPTS', 'Hello {USERNAME},\r\n\r\nWe''ve become aware of somebody, if not yourself, trying to login to your account with incorrect details.\r\n\r\nYour account has been locked for security purposes.\r\n\r\nTo Reactivate your account, please click the link below, or copy and paste it into your address bar.\r\n\r\n[url]{URL}[/url]\r\n\r\n~{SITE_NAME}'),
    ('email', 'E_REG_SUCCESSFUL', 'Hello {USERNAME},\r\n\r\nThank you for registering on {SITE_NAME}. If this was not you then please disregard this email.\r\n\r\nThe administrator of {SITE_NAME} has requested that all users validate their email accounts before being allowed to login. The following URL will allow you to do that:\r\n\r\n[url]{URL}[/url]'),

    ('site', 'internetCalls', '0'),

    ('forum', 'news_category', '2'),
    ('forum', 'sortable_categories', '0'),
    ('forum', 'guest_restriction', '0'),
    ('forum', 'post_edit_time', '3600');
SQL;

//--Logs & Error Tables
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_sqlerrors`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_sqlerrors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `date` int(11) NOT NULL DEFAULT '0',
  `query` text COLLATE utf8_unicode_ci,
  `page` text COLLATE utf8_unicode_ci,
  `vars` text COLLATE utf8_unicode_ci,
  `error` text COLLATE utf8_unicode_ci,
  `lineInfo` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_logs`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `query` text COLLATE utf8_unicode_ci,
  `refer` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` int(11) unsigned NOT NULL DEFAULT '0',
  `ip_address` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;

//--FileHashes
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_fileregistry`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_fileregistry` (
  `filename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hash` char(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

//--Groups
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_groups`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL DEFAULT '1',
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci,
  `moderator` int(11) unsigned NOT NULL DEFAULT '0',
  `single_user_group` tinyint(1) NOT NULL DEFAULT '1',
  `color` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_groups` (`id`,     `type`, `name`,     `description`,             `moderator`, `single_user_group`,     `color`,     `order`) VALUES
                            (1,     1,         'Admin',     'Site Administrator',         1,                 1,                 '#ff0000',     1),
                            (2,     1,         'Mods',     'Site Moderator',             1,                 0,                 '#146eca',     3),
                            (3,     0,         'Users',     'Registered User',            1,                 0,                 '#b7b7b7',     10);

SQL;

//--Group Subscriptions
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_group_subs`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_group_subs` (
  `uid` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  `pending` tinyint(1) NOT NULL DEFAULT '1',
  KEY `gid` (`gid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_group_subs` (`uid`, `gid`, `pending`) VALUES 
    (1, 1, 0), (1, 2, 0), (1, 3, 0);
SQL;

//--CMS Menus
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_menus`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_menus` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `link_value` tinytext COLLATE utf8_unicode_ci,
  `link_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `link_color` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `blank` tinyint(1) NOT NULL DEFAULT '0',
  `order` int(11) NOT NULL DEFAULT '0',
  `perms` int(1) NOT NULL DEFAULT '0',
  `external` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_menus` (`id`, `menu_id`, `link_value`, `link_name`, `link_color`, `blank`, `order`, `perms`, `external`) VALUES
    (1, 'menu_mm', 'index.php', 'Site home', NULL, 0, 1, 0, 0),
    (2, 'menu_mm', 'admin/', 'Admin Panel', '#FF0000', 0, 10, 3, 0),
    (3, 'menu_mm', 'modules/forum/', 'Forum', NULL, 0, 2, 0, 0),
    (4, 'menu_mm', 'modules/pm/', 'Private Messages', NULL, 0, 3, 1, 0),
    (5, 'menu_mm', 'user/', 'User Control Panel', NULL, 0, 4, 1, 0),
    (6, 'menu_mm', 'mod/', 'Moderator Panel', NULL, 0, 9, 3, 0),
    (7, 'main_nav', 'index.php', 'Site Home', NULL, 0, 1, 0, 0),
    (8, 'main_nav', 'modules/profile/view/', 'Profile', NULL, 0, 2, 1, 0),
    (9, 'main_nav', 'modules/forum/', 'Forum', NULL, 0, 3, 0, 0);
SQL;

$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_menu_blocks`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_menu_blocks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `unique_id` char(10) COLLATE utf8_unicode_ci NOT NULL,
  `module` text COLLATE utf8_unicode_ci,
  `function` text COLLATE utf8_unicode_ci,
  `position` tinyint(2) NOT NULL DEFAULT '0',
  `perms` tinyint(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_menu_blocks` (`id`, `unique_id`, `module`, `function`, `position`, `perms`) VALUES
    (1, 'jv1h9w6m2y', 'NULL', 'NULL', 0, 0),
    (2, 'x91z6yvmrw', 'core', 'affiliates', 0, 0),
    (3, 'ndxhzj9w54', 'core', 'wio', 0, 0),
    (4, '9rgtdk2zv8', 'core', 'login', 0, 0),
    (5, 'n4fym8r9gd', 'forum', 'forum_posts', 0, 0),
    (6, '343fwfwr34', 'forum', 'forum_users', 0, 0);
SQL;
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_menu_setups`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_menu_setups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `page_id` text COLLATE utf8_unicode_ci NOT NULL,
  `menu_id` char(10) COLLATE utf8_unicode_ci NOT NULL,
  `params` longtext COLLATE utf8_unicode_ci,
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_menu_setups` (`module`, `page_id`, `menu_id`, `params`, `order`) VALUES
    ('core',     'default',  'jv1h9w6m2y', 'menu_name=mm\r\nmenu_title=Main Menu', 1),
    ('core',     'default',  'x91z6yvmrw', 'menu_title=m_affiliates\r\nlimit=6\r\nperRow=2', 3),
    ('core',     'default',  'ndxhzj9w54', 'menu_title=m_wio', 4),
    ('core',     'default',  '9rgtdk2zv8', 'menu_title=m_login', 2),
    ('forum',    'default',  'jv1h9w6m2y', 'menu_name=mm\r\nmenu_title=Main Menu', 1),
    ('forum',    'default',  'n4fym8r9gd', 'menu_title=m_latest_post\r\nlimit=5', 3),
    ('forum',    'default',  'ndxhzj9w54', 'menu_title=m_wio', 4),
    ('forum',     'default',  '343fwfwr34', 'menu_title=m_top_user\r\nlimit=5', 2);
SQL;

/*--Affiliate System
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_affiliates`;
SQL;
$sql[] = <<<SQL
    CREATE TABLE IF NOT EXISTS `cs_affiliates` (
      `id` int(5) NOT NULL AUTO_INCREMENT,
      `img` text NOT NULL,
      `title` text NOT NULL,
      `url` text NOT NULL,
      `in` int(11) NOT NULL DEFAULT '0',
      `out` int(11) NOT NULL DEFAULT '0',
      `active` int(1) NOT NULL DEFAULT '0',
      `showOnMenu` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;
SQL;
$sql[] = <<<SQL
    INSERT INTO `cs_affiliates` (`id`, `img`, `title`, `url`, `in`, `out`, `active`, `showOnMenu`) VALUES
    (1, 'http://www.cybershade.org/images/aff.gif', 'CybershadeCMS', 'http://cybershade.org', 0, 0, 1, 1);
SQL;*/

//--Notifications
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_notifications`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT '0',
  `body` text COLLATE utf8_unicode_ci,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `read` int(11) NOT NULL DEFAULT '0',
  `module` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `module_id` int(11) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_notification_settings`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_notification_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `setting` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `default` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_notification_settings` (`id`, `module`, `setting`, `description`, `default`) VALUES
    (1, 'forum', 'forumReplies', 'Forum Replies', '1');
SQL;

//--Comments
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_comments`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(255) NOT NULL,
  `module_id` int(11) NOT NULL,
  `author` int(11) unsigned NOT NULL,
  `comment` text NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;

//--Online Table
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_online`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_online` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL,
  `username` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ip_address` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  `timestamp` int(11) NOT NULL,
  `hidden` int(1) NOT NULL DEFAULT '0',
  `location` text COLLATE utf8_unicode_ci NOT NULL,
  `referer` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `language` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `useragent` text COLLATE utf8_unicode_ci NOT NULL,
  `login_attempts` tinyint(2) NOT NULL DEFAULT '0',
  `login_time` int(11) NOT NULL,
  `userkey` char(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mode` enum('active','kill','ban','update') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userkey` (`userkey`),
  KEY `uid` (`uid`),
  KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;

/*--Ban Table
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_banned`;
SQL;
$sql[] = <<<SQL
    CREATE TABLE IF NOT EXISTS `cs_banned` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_ip` varchar(15) NOT NULL DEFAULT '',
      `ban_time` int(11) NOT NULL DEFAULT '0',
      `ban_untill` int(11) NOT NULL DEFAULT '0',
      `reason` text,
      `whoby` int(11) NOT NULL DEFAULT '0',
      `url` text,
      PRIMARY KEY (`id`),
      UNIQUE KEY `user_ip` (`user_ip`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;
SQL;*/

//--Hooks
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_plugins`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_plugins` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `filePath` text COLLATE utf8_unicode_ci NOT NULL,
  `author` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `priority` enum('1','2','3') COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;

//--Stats
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_statistics`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_statistics` (
  `variable` varchar(255) NOT NULL DEFAULT '',
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`variable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_statistics` (`variable`, `value`) VALUES
    ('site_opened', '{$now}'),
    ('hourly_cron', '{$now}'),
    ('daily_cron', '{$now}'),
    ('weekly_cron', '{$now}');
SQL;

//--Modules
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_modules`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_modules` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `hash` char(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_modules` (`name`, `hash`, `enabled`) VALUES
    ('core', '6e112747dd0843bfbf5b91589a79b2d7', 1),
    ('forum', 'aaa3078a02a0de4e85925611c8a9b677', 1),
    ('group', '87603fa76cd79eab08cd0ff0f2f1306d', '1'),
    ('profile', 'd71c3fe357813fe6fa54b0de738eece0', '1');
SQL;


//
//--Core Modules
//

//--Forum
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_forum_cats`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_forum_cats` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `desc` text COLLATE utf8_unicode_ci,
  `order` int(3) NOT NULL DEFAULT '0',
  `last_post_id` int(11) unsigned NOT NULL DEFAULT '0',
  `postcounts` int(1) NOT NULL DEFAULT '1',
  `auth_view` int(1) NOT NULL DEFAULT '0',
  `auth_read` int(1) NOT NULL DEFAULT '0',
  `auth_post` int(1) NOT NULL DEFAULT '0',
  `auth_reply` int(1) NOT NULL DEFAULT '0',
  `auth_edit` int(1) NOT NULL DEFAULT '0',
  `auth_del` int(1) NOT NULL DEFAULT '0',
  `auth_move` int(1) NOT NULL DEFAULT '0',
  `auth_special` int(1) NOT NULL DEFAULT '0',
  `auth_mod` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_forum_cats` (`parent_id`, `title`, `desc`, `order`, `last_post_id`, `auth_view`, `auth_read`, `auth_post`, `auth_reply`, `auth_edit`, `auth_del`, `auth_move`, `auth_special`, `auth_mod`) VALUES
    (0, 'Test Parent Category', NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
    (1, 'Test Forum', 'Test Forum', 1, 1, 0, 1, 1, 1, 1, 3, 3, 3, 0);
SQL;

$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_forum_threads`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_forum_threads` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` int(11) unsigned NOT NULL DEFAULT '0',
  `author` int(11) unsigned NOT NULL DEFAULT '0',
  `subject` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `first_post_id` int(11) unsigned NOT NULL DEFAULT '0',
  `last_uid` int(11) unsigned NOT NULL DEFAULT '0',
  `locked` int(1) NOT NULL DEFAULT '0',
  `mode` int(1) NOT NULL DEFAULT '0',
  `views` int(1) NOT NULL DEFAULT '0',
  `old_cat_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_forum_threads` (`cat_id`, `author`, `subject`, `timestamp`, `first_post_id`, `last_uid`) VALUES
    (2, 1, 'Test Thread', {$now}, 1, 1);
SQL;

$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_forum_posts`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_forum_posts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` int(11) unsigned NOT NULL DEFAULT '0',
  `author` int(11) unsigned NOT NULL DEFAULT '0',
  `post` text COLLATE utf8_unicode_ci,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `poster_ip` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `edited` int(5) NOT NULL DEFAULT '0',
  `edited_uid` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `thread_id` (`thread_id`),
  KEY `author` (`author`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;
$sql[] = <<<SQL
INSERT INTO `cs_forum_posts` (`thread_id`, `author`, `post`, `timestamp`, `poster_ip`, `edited`, `edited_uid`) VALUES
    (1, 1, 'Welcome to Cybershade CMS. Install seems to have worked so you can reorder the forum from the admin panel.', {$now}, '{$userIp}', 0, 0);
SQL;

$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_forum_watch`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_forum_watch` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `thread_id` int(11) NOT NULL DEFAULT '0',
  `seen` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;

$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_forum_auth`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_forum_auth` (
  `group_id` int(11) unsigned NOT NULL,
  `cat_id` int(11) unsigned NOT NULL DEFAULT '0',
  `auth_view` int(1) NOT NULL DEFAULT '0',
  `auth_read` int(1) NOT NULL DEFAULT '0',
  `auth_post` int(1) NOT NULL DEFAULT '0',
  `auth_reply` int(1) NOT NULL DEFAULT '0',
  `auth_edit` int(1) NOT NULL DEFAULT '0',
  `auth_del` int(1) NOT NULL DEFAULT '0',
  `auth_move` int(1) NOT NULL DEFAULT '0',
  `auth_special` int(1) NOT NULL DEFAULT '0',
  `auth_mod` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

/*--PM Sys
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_pm`;
SQL;
$sql[] = <<<SQL
    CREATE TABLE IF NOT EXISTS `cs_pm` (
      `id` int(100) NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `author` int(11) NOT NULL DEFAULT '0',
      `recipient` int(11) NOT NULL DEFAULT '0',
      `message` text NOT NULL,
      `inBox` int(1) NOT NULL DEFAULT '1',
      `read` set('1','0') NOT NULL DEFAULT '0',
      `replied` set('1','0') NOT NULL DEFAULT '0',
      `parent` int(100) NOT NULL DEFAULT '0',
      `sent` int(15) NOT NULL DEFAULT '0',
      `rm_recipient` int(1) NOT NULL DEFAULT '0',
      `rm_author` int(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;
SQL;*/

/*--Shoutbox
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_shoutbox`;
SQL;
$sql[] = <<<SQL
    CREATE TABLE IF NOT EXISTS `cs_shoutbox` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `uid` int(11) NOT NULL DEFAULT '0',
      `time` int(11) NOT NULL DEFAULT '0',
      `message` text,
      `ip` varchar(15) NOT NULL DEFAULT '',
      `color` int(6) DEFAULT NULL,
      `module` varchar(50) NOT NULL DEFAULT 'NULL',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;
SQL;*/

//
//--User Stuff
//

//--Userkeys
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_userkeys`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_userkeys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uData` varchar(11) COLLATE utf8_unicode_ci DEFAULT NULL,
  `uAgent` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `uIP` varchar(15) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `uid` (`uData`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;

//--Users
$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_users`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` char(34) COLLATE utf8_unicode_ci DEFAULT NULL,
  `pin` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `register_date` int(11) NOT NULL DEFAULT '0',
  `last_active` int(11) NOT NULL DEFAULT '0',
  `usercode` varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `show_email` tinyint(1) NOT NULL DEFAULT '0',
  `avatar` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `language` char(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `timezone` decimal(5,1) NOT NULL DEFAULT '0.0',
  `theme` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'default',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `userlevel` tinyint(1) NOT NULL DEFAULT '0',
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `primary_group` int(5) NOT NULL DEFAULT '0',
  `login_attempts` int(3) NOT NULL DEFAULT '0',
  `pin_attempts` int(3) NOT NULL DEFAULT '0',
  `autologin` tinyint(1) NOT NULL DEFAULT '0',
  `reffered_by` int(11) unsigned NOT NULL DEFAULT '0',
  `password_update` tinyint(1) NOT NULL DEFAULT '0',
  `whitelist` tinyint(1) NOT NULL DEFAULT '0',
  `whitelisted_ips` text COLLATE utf8_unicode_ci,
  `warnings` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `usercode` (`usercode`),
  KEY `email` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;
SQL;
$sql[] = <<<SQL
    INSERT INTO `cs_users` (`username`, `password`, `register_date`, `last_active`, `usercode`, `email`, `show_email`, `language`, `timezone`, `theme`, `active`, `userlevel`) VALUES
('{$admUsername}', '{$admPasswd}', {$now}, {$now}, '{$admKey}', '{$admEmail}', 0, 'en', '0.0', 'default', 1, 3);
SQL;

$sql[] = <<<SQL
    DROP TABLE IF EXISTS `cs_user_extras`;
SQL;
$sql[] = <<<SQL
CREATE TABLE IF NOT EXISTS `cs_user_extras` (
  `uid` int(11) unsigned NOT NULL,
  `birthday` varchar(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '00/00/0000',
  `sex` tinyint(1) NOT NULL DEFAULT '0',
  `contact_info` text COLLATE utf8_unicode_ci,
  `about` text COLLATE utf8_unicode_ci,
  `interests` text COLLATE utf8_unicode_ci,
  `signature` text COLLATE utf8_unicode_ci,
  `usernotes` text COLLATE utf8_unicode_ci NOT NULL,
  `ajax_settings` text COLLATE utf8_unicode_ci,
  `notification_settings` text COLLATE utf8_unicode_ci,
  `forum_show_sigs` tinyint(1) NOT NULL DEFAULT '0',
  `forum_autowatch` tinyint(1) NOT NULL DEFAULT '0',
  `forum_quickreply` tinyint(1) NOT NULL DEFAULT '0',
  `forum_cat_order` text COLLATE utf8_unicode_ci,
  `forum_tracker` text COLLATE utf8_unicode_ci,
  `pagination_style` int(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `uid_2` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;
$sql[] = <<<SQL
    INSERT INTO `cs_user_extras` (`uid`) VALUES (1);
SQL;

?>