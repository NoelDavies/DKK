<!-- BEGIN profile -->
<div class="content corners">
    <div class="title corners-top"><h4>{L_VIEW_PROFILE}: {profile.USERNAME_RAW}</h4></div>
    <div>
    <table width="100%">
      <tr>
            <td width="105" align="center">{profile.AVATAR}</td>
            <td align="center">
                <object type="application/x-shockwave-flash" data="/{ROOT}modules/profile/title.swf" width="100%" height="100">
                    <param name="movie" value="/{ROOT}modules/profile/title.swf" />
                    <param name="pluginurl" value="http://www.macromedia.com/go/getflashplayer" />
                    <param name="wmode" value="transparent" />
                    <param name="menu" value="false" />
                    <param name="quality" value="best" />
                    <param name="scale" value="exactfit" />
                    <param name="flashvars" value="text={profile.USERNAME_RAW}" />
                    <embed src="/{ROOT}modules/profile/title.swf" flashvars="text={profile.USERNAME_RAW}" quality="high" pluginspage="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" type="application/x-shockwave-flash" height="100" width="100%" bgcolor="#3c3c3c" scale="exactfit"></embed>
                </object>        
            </td>
        </tr>
      <tr>
        <td colspan="2" class="row_color2">
            <div>
                <div class="float-left padding">
                    <span id="lastLoggedIn"><b>{profile.L_LOCALTIME}:</b> {profile.LOCALTIME}</span>
                    <span id="hoverInfo" style="display:none;">&nbsp;</span>
                </div>
                <div class="float-right padding">{profile.CONTACT_ICONS}</div>
            </div>
        </td>
      </tr>
    </table>        
    </div>
    <div class="clear"></div>
</div><br />


<div id="tabContents">
    <ul class="tabs" id="tabs">
        <li><a class="tab" href="#bio" id="tab_bio">{profile.L_BIO}</a></li>
        <!--li><a class="tab" href="#recentActivity" id="tab_recentActivity">{profile.L_RECENTA}</a></li-->
        <li><a class="tab" href="#comments" id="tab_comments">{profile.L_COMMENTS}</a></li>
    </ul>
    
    <div class="panel content padding" id="panel_bio">
        <br /><div class="content corners">
            <div class="title corners-top"><h4>{L_BINFO}</h4></div>
            <div>
                <table width="100%" border="0" cellspacing="1" cellpadding="3">
                <!-- BEGIN BINFO -->
                  <tr class="{profile.BINFO.ROW}">
                    <td>{profile.BINFO.VAR}</td>
                    <td>{profile.BINFO.VAL}</td>
                  </tr>
                <!-- END BINFO -->
                </table>
            </div>
            <div class="clear"></div>
        </div>
        <!-- BEGIN ABOUT_ME -->
        <br /><div class="content corners">
            <div class="title corners-top"><h4>{L_ABOUT_ME}</h4></div>
            <div class="padding">
                {profile.ABOUT_ME}
            </div>
            <div class="clear"></div>
        </div>
        <!-- END ABOUT_ME -->

        <!-- BEGIN INTERESTS -->
        <br /><div class="content corners">
            <div class="title corners-top"><h4>{L_MY_INTERESTS}</h4></div>
            <div class="padding">
                {profile.MY_INTERESTS}
            </div>
            <div class="clear"></div>
        </div>
        <!-- END INTERESTS -->

        <br /><div class="content corners">
            <div class="title corners-top"><h4>{L_SIGNATURE}</h4></div>
            <div class="padding">
                {profile.SIGNATURE}
            </div>
            <div class="clear"></div>
        </div>
    </div>
    
    <div class="clear panel content padding" id="panel_recentActivity">
        {RECENT_ACTIVITY_MSG}
    </div>
    
    <div class="panel content padding" id="panel_comments">{PROFILE_COMMENTS}</div>
</div>
<div class="clear"></div>
<!-- END profile -->