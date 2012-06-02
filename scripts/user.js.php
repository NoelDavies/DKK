<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
header('Content-type: text/javascript');
define('INDEX_CHECK', true);
define('NO_LOG', true);

$cmsROOT = '../';
include_once($cmsROOT.'core/core.php');

if(!User::$IS_ONLINE){ die(); }
if(is_readable('/'.Page::$THEME_ROOT.'extras.php')){ include '/'.Page::$THEME_ROOT.'extras.php'; }
$vars = $objPage->getVar('tplVars');

//notifications
$notifications = null;
if(User::$IS_ONLINE){
    $notes = $objNotify->getNotifications(false);
    if($notes){
        foreach($notes as $note){
            $notifications .= $objNotify->outputNotification($note, true);
        }
    }
}
?>
function growl(message, header, sticky){
    growler.growl(message, {header: header || "", sticky: Boolean(sticky)});
}

function showNotification(id, message, header, sticky){
    growler.growl(message, {header: header || "", sticky: Boolean(sticky),
        destroyed: function(){
            new Ajax.Request("/"+cmsROOT+"scripts/ajax.php?action=notificationRead", {
                method: "post",
                parameters: {id: id} 
            });
        }
    });
    $$("div.Growler-notice([id=\"\"])").reject(function(el){ return (strlen(el.id) > 0); }).each(function(ele){
        ele.writeAttribute("id", "notify_"+id);
    });
}

var avatarMenu = [
    { name: "Avatar Options", className: "title", disabled: true },
    { name: "Remove", className: "ava_remove", callback: function(){ console.log(this); } },
    { name: "Upload New", className: "ava_upload", callback: function(){ console.log(this); } },
    { name: "Off Link New", className: "ava_offlink", callback: function(){ console.log(this); } },
    { separator: true },
    { name: "More User Preferences", className: "title", callback: function(){ document.location = "/"+root+"user/"; } }
];

function usernameAutocomplete(input){
    new Ajax.Autocompleter(input, "user_autocomplete", "/"+cmsROOT+"scripts/ajax.php?action=userAutocomplete", {
        paramName: "var", 
        height: "20", 
        width: "200", 
        tokens: ", " 
    });
}

document.observe('dom:loaded', function(){
    //setup growl (notifications)
    growler = new k.Growler({location: 'nu'});
    
    
    //input autocomplete
    if($("user_autocomplete")){ usernameAutocomplete($("user_autocomplete").readAttribute("data-input")); }
    
    //setup notifications
    if(User.IS_ONLINE){
        if($("notificationGrabber")){
            new Ajax.PeriodicalUpdater('notificationGrabber', '/'+cmsROOT+'scripts/ajax.php?action=grabNewNotifications', {
                method: "post", frequency: 5, decay: 3, evalScripts: true
            });
        }
        <?php echo $notifications; ?>
    }
    
    //context menu on the users own avatar frames
    new Proto.Menu({
        selector: "#"+User.username+"_avatar",
        className: "menu cms",
        menuItems: avatarMenu
    });

    $$("img[class*=avatar][data-avatar]:not([data-avatar~="+User.username+"])").each(function (ava){
        var user = ava.readAttribute("data-avatar");

        new Proto.Menu({
            selector: "#"+user+"_avatar",
            className: "menu cms",
            menuItems: [
                { name: user+" Avatar Options", className: "title", disabled: true },
                { name: "Remove Avatar", className: "ava_reset", callback: function(){
                    inWindow("/"+cmsROOT+"modules/profile/avatar/?action=reset&username="+user, "Reset Avatar", 400, 100);
                }}
            ]
        });
    });

});