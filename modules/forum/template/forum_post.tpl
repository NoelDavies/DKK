{F_START}

<!-- BEGIN pm -->
<div class="content corners">
    <div class="title corners-top"><h4>{TO}</h4></div>
    <div class="padding">
    {F_TO} <span id="chkUSR"></span>
    </div>
    <div class="clear"></div>
</div><br />
<!-- END pm -->
<!-- BEGIN title -->
<div class="content corners">
    <div class="title corners-top"><h4>{L_TITLE}</h4></div>
    <div class="padding">
        {F_TITLE}
    </div>
    <div class="clear"></div>
</div><br />
<!-- END title -->
<div class="content corners">
    <div class="title corners-top"><h4>{ID}{L_POST_BODY}</h4></div>
    <div class="padding row_color1">
        <div class="bbrow iblock">{BUTTONS}</div>
        <div class="clear"></div>
    </div>
    <table width="100%" border="0" class="padding">
        <tr><td rowspan="2" width="80%" valign="top">
            <div id="preview" style="display:none;"> </div>
            <div id="post-content">{F_POST}</div>        
        </td><td colspan="2" valign="top">
            <!-- BEGIN new_post -->
            <div class="content corners">
                <div class="title corners-top"><h4></h4></div>
                <div class="padding">
                    {POST_MODE}
                    <div class="clear"></div>
                    {WATCH_TOPIC}<br />{AUTO_LOCK}
                </div>
                <div class="clear"></div>
            </div><br />
            <!-- END new_post -->
            <div class="content corners">
                <div class="title corners-top"><h4></h4></div>
                <div class="padding">
                    <table width="25" border="0" cellspacing="0" cellpadding="0" align="center">
                        <!-- BEGIN smilies -->
                        <tr>
                          <td align="center">{smilies.1}</td>
                          <td align="center">{smilies.2}</td>
                          <td align="center">{smilies.3}</td>
                          <td align="center">{smilies.4}</td>
                        </tr>
                        <!-- END smilies -->
                    </table>
                </div>
                <div class="clear"></div>
            </div><br />
        </td></tr>
        <tr><td valign="bottom" class="editor-btn">{SUBMIT}</td>
          <td valign="bottom" class="editor-btn">{RESET}</td>
        </tr>
    </table>
</div><br />

<!-- BEGIN reply_posts -->
<div class="content corners">
    <div class="title corners-top"><h4>{reply_posts.L_THREAD_RECAP}</h4></div>
    <div class="padding" style="height: 200px; overflow:scroll;">
        {reply_posts.CONTENT}
    </div>
    <div class="clear"></div>
</div><br />
<!-- END reply_posts -->
