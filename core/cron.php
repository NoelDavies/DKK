<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

//make sure we have a db connection before we continue
if(defined('NO_DB')){ return; }

    //set hourly cron to exec, run every 1 hour
    $crons = array(
        'hourly'    => $objCore->config('cron', 'hourly_time'),
        'daily'     => $objCore->config('cron', 'daily_time'),
        'weekly'    => $objCore->config('cron', 'weekly_time'),
    );

    //loop thru each of the crons and set em to go if needed
    foreach($crons as $name => $time){
        $name = $name.'_cron'; ${$name} = false;
        if((time()-$time) > $objCore->config('statistics', $name)){
            $objSQL->updateRow('statistics', array('value' => time()), array('variable = "%s"', $name));
            ${$name} = true;
        }
    }


//
//--Start the CRONs
//
    //regenerate the statistics cache if any of them are run
    if($hourly_cron || $daily_cron || $weekly_cron){
        $objCache->generate_statistics_cache();
    }

    //do hourly cron
    if($hourly_cron){
    $objSQL->recordMessage('Hourly CRON is running', 'INFO');

        //update the user table with last active timestamp from online table
        $objSQL->query(
            'UPDATE `$Pusers` u SET u.last_active =
                (SELECT online.timestamp
                    FROM `$Ponline`
                    WHERE online.uid = u.id)
            WHERE EXISTS
                (SELECT online.timestamp
                    FROM `$Ponline`
                    WHERE online.uid = u.id)'
        );

        //remove the inactive ones..atm 20 mins == inactive
        $objSQL->deleteRow('online', 'timestamp < '.$objTime->mod_time(time(), 0, 20, 0, 'TAKE'));

    $objPlugins->hook('CMSCron_hourly');
    }

    //do daily cron
    if($daily_cron){
    $objSQL->recordMessage('Daily CRON is running', 'INFO');

        //VV Update Checker
            $errstr = NULL; $errno = 0;
            $updateAvalible = false;

            $info = get_remote_file('www.cybershade.org', '/', 'checkxml.php?action=cmsVersion', $errstr, $errno, 80, 5);
            if(!is_empty($info)){
                //try and parse the xml back from the server
                $xml = @simplexml_load_string($info);

                //actually check the version here
                $updateAvalible =  (version_compare($xml->version, cmsVERSION, '>') ? true : false );
                if($updateAvalible && $xml->updateType==1){ touch(cmsROOT.'killCMS'); }
            }
        unset($errstr, $errno, $updateAvalible, $info, $xml);
        //^^ Update Checker

        $objCache->regenerateCache('group_subscriptions');

    $objPlugins->hook('CMSCron_daily');
    }

    //do weekly cron
    if($weekly_cron){
    $objSQL->recordMessage('Weekly CRON is running', 'INFO');

        //remove a few caches (dw they will regenerate :P)
        $objCache->regenerateCache('config');
        $objCache->regenerateCache('groups');

        //Optimise all of the tables in the DB
        $alltables = $objSQL->getTable('SHOW TABLES');
        $tables = '';
        $counter = count($alltables);
        $x = 0;
        foreach($alltables as $table){
            foreach ($table as $tablename){
                $tables .= '`'.$tablename.'`'.($x++==$counter-1 ? '' : ', ');
            }
        }
        $objSQL->query('OPTIMIZE TABLE '.$tables);


    $objPlugins->hook('CMSCron_weekly');
    }

?>