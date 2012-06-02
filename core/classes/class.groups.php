<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){die('Error: Cannot access directly.');}

/**
 * Group Class designed to allow easier access to expand on the group system implemented
 *
 * @version     1.2
 * @since       1.0.0
 * @author      xLink
 */
class groups extends coreClass {

    /**
     * Returns information on a group
     *
     * @version    1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int $gid     Group ID
     *
     * @return  array
     */
    public function getGroup($gid){
        //check to make sure the args are right
        if(!is_number($gid)){
            $this->setError('$gid is not valid');
            return false;
        }

        //if this particular one is cached already we shall just return it
        if(isset($this->group[$gid])){
            return $this->group[$gid];
        }

        $this->group[$gid] = $this->objSQL->getLine('SELECT id, name, moderator, single_user_group FROM `$Pgroups` WHERE id = "%s" LIMIT 1;', array($gid));
            if(is_empty($this->group[$gid])){
                $this->setError('Cannot query group');
                return false;
            }

        return $this->group[$gid];
    }


    /**
     * Joins a user to a specific group
     *
     * @version    1.0
     * @since   1.1.0
     * @author  xLink
     *
     * @param   int $uid         User's ID
     * @param   int $gid         Group ID
     * @param   int $pending     Whether the user will be accessable to the group
     *
     * @return  bool
     */
    public function joinGroup($uid, $gid, $pending=1){
        if(!is_number($uid)){ $this->setError('$uid is not valid'); return false; }
        if(!is_number($gid)){ $this->setError('$gid is not valid'); return false; }
        if(!is_number($pending)){ $this->setError('$pending is not valid'); return false; }

        //test to see if $uid is already in said group, moderator of the group is added as a subscriber anyway
        if($this->isInGroup($uid, $gid, 0) || $this->isInGroup($uid, $gid, 1)){
            $this->setError('User is already in group'); return false;
        }

        //add if needed
        unset($insert);
        $insert['gid']      = $gid;
        $insert['uid']      = $uid;
        $insert['pending']  = $pending;

        $this->objPlugins->hook('CMSGroups_beforeJoin', $insert);

        $this->objSQL->insertRow('group_subs', $insert);
            if(!mysql_affected_rows()){
                $this->setError('Failed to add user to group: '.$this->objSQL->error());
                return false;
            }

        $args = func_get_args();
        $this->objPlugins->hook('CMSGroups_afterJoin', $args);
        unset($insert);

        return true;
    }

    /**
     * Removes a user from a group
     *
     * @version    1.0
     * @since   1.1.0
     * @author  xLink
     *
     * @param   int $uid     User's ID
     * @param   int $gid     Group ID
     *
     * @return  bool
     */
    function leaveGroup($uid, $gid){
        if(!is_number($uid)){ $this->setError('$uid is not valid'); return false; }
        if(!is_number($gid)){ $this->setError('$gid is not valid'); return false; }

        //remove the user from the group
        $this->objSQL->deleteRow('group_subs', array('uid = "%s" AND gid = "%s"', $uid, $gid),
                'User Groups: Removed '.$this->objUser->profile($uid, RAW).' from '.$gid);

            if(!mysql_affected_rows()){
                $this->setError('Failed to remove user from group: '.$this->objSQL->error());
                return false;
            }

        $this->objPlugins->hook('CMSGroups_leave', func_get_args());

        return true;
    }

    /**
     * Assign a user Moderator status over a group
     *
     * @version    1.0
     * @since   1.1.0
     * @author  xLink
     *
     * @param   int $uid     User's ID
     * @param   int $gid     Group ID
     *
     * @return  bool
     */
    function makeModerator($uid, $gid){
        if(!is_number($uid)){ $this->setError('$uid is not valid'); return false; }
        if(!is_number($gid)){ $this->setError('$gid is not valid'); return false; }

        $group = $this->getGroup($gid);

        //test to make sure group isnt a single user group
        if($group['single_user_group']){ $this->setError('Group is user specific, Cannot reassign Moderator'); return false; }

        //make sure old moderator is a subscriber
        $oldModerator = $this->objSQL->getLine('SELECT * FROM `$Pgroup_subs` WHERE gid = "%s" AND uid = "%s" LIMIT 1', array($gid, $group['moderator']));
            if(is_empty($oldModerator)){
                $this->joinGroup($group['moderator'], $gid, 0);
            }

        //make $uid new moderator
        if($group['moderator'] != $uid){
            unset($update);
            $update['moderator'] = $uid;

            $this->objSQL->updateRow('group_subs', $update, array('id = "%s"', $gid),
                    'User Groups: '.$this->objUser->profile($uid, RAW).' has been made group Moderator of '.$group['name']);

            $this->objPlugins->hook('CMSGroups_changeModerator', array($uid, $gid));
        }

        //make the moderator a subscriber too
        $this->joinGroup($uid, $gid, 0);

        return true;
    }

    /**
     * Toggles the pending status of a user in a group
     *
     * @version    1.0
     * @since   1.1.0
     * @author  xLink
     *
     * @param   int $uid     User's ID
     * @param   int $gid     Group ID
     *
     * @return  bool
     */
    function togglePending($uid, $gid){
        if(!is_number($uid)){ $this->setError('$uid is not valid'); return false; }
        if(!is_number($gid)){ $this->setError('$gid is not valid'); return false; }

        //get group
        $group = $this->getGroup($gid);

        //grab the necesary row
        $subRow = $this->objSQL->getLine('SELECT uid, gid, pending FROM `$Pgroup_subs` WHERE gid = "%s" AND uid = "%s" LIMIT 1', array($gid, $uid));
            if(is_empty($subRow)){
                $this->setError('User is not in group');
                return false;
            }

        //update the pending status
        unset($update);
        $update['pending'] = !$subRow['pending'];

        $this->objSQL->updateRow('group_subs', $update, array('gid = "%s" AND uid = "%s"', $gid, $uid));
            if(!mysql_affected_rows()){
                $this->setError('Updating pending status failed');
                return false;
            }

        return true;
    }

    /**
     * Determine whether user is in a group
     *
     * @version    1.0
     * @since   1.1.0
     * @author  xLink
     *
     * @param   int $uid         User's ID
     * @param   int $gid         Group ID
     * @param   array $query     Group Query
     *
     * @return  bool
     */
    function isInGroup($uid, $gid, $query=null){
        if(!is_number($uid)){ $this->setError('$uid is not valid'); return false; }
        if(!is_number($gid)){ $this->setError('$gid is not valid'); return false; }
        if(!is_array($query) && !is_empty($query)){ $this->setError('$query is not valid'); return false; }

        //get group
        if(is_empty($query)){
            $query = $this->objSQL->getTable(
                'SELECT ug.uid, g.type, g.moderator
                    FROM `$Pgroups` g, `$Pgroup_subs` ug
                    WHERE g.id = %s
                        AND g.type != %s
                        AND ug.gid = g.id',
                array($gid, GROUP_HIDDEN)
            );
                if(is_empty($query)){
                    $this->setError('No group for ID: '.$gid);
                    return false;
                }
        }

        //test to see if user is in group and return accordingly
        foreach($query as $row){
            if($uid == $row['uid']){ return true; }
        }

        return false;
    }

    /**
     * Returns an array of user id in said group according to whether they are $pending
     *
     * @version    1.0
     * @since   1.1.0
     * @author  xLink
     *
     * @param   int $uid         User's ID
     * @param   int $pending
     *
     * @return  array
     */
    function usersInGroup($gid, $pending=0){
        if(!is_number($gid)){ $this->setError('$gid is not valid'); return false; }
        if(!is_number($pending)){ $this->setError('$pending is not valid'); return false; }

        //get group
        $query = $this->objSQL->getTable(
            'SELECT ug.uid, ug.pending, g.type, g.moderator
                FROM `$Pgroups` g, `$Pgroup_subs` ug
                WHERE g.id = %s
                    AND ug.gid = g.id',
            array($gid)
        );
            if(is_empty($query)){
                $this->setError('No group for ID: '.$gid);
                return false;
            }

        //create an array of uid's in group according to $pending
        $users = array();
        foreach($query as $row){
            if($row['pending']==$pending){ $users[] = $row['uid']; }
        }

        return $users;
    }
}

?>