<div class="content corners">
    <div class="title corners-top">
        <table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>
            <td align="left" valign="middle">
            <div class="float-left" style="margin: 5px 5px 0 5px;">
                <!-- BEGIN move -->
                    <a href="{move.URL}"><img src="{move.IMG}" alt="{move.TEXT}" title="{move.TEXT}" /></a>
                <!-- END move -->
                
                <!-- BEGIN del -->
                    &nbsp;<a href="{del.URL}"><img src="{del.IMG}" alt="{del.TEXT}" title="{del.TEXT}" /></a>
                <!-- END del -->
                
                <!-- BEGIN locked -->
                    &nbsp;<a href="{locked.URL}"><img src="{locked.IMG}" alt="{locked.TEXT}" title="{locked.TEXT}" /></a> 
                <!-- END locked -->            
            </div>
            <div class="float-left">
                <h4>{THREAD_TITLE}</h4>
            </div>            
            </td>
            <td align="right" class="padding">{PAGINATION}</td>
        </tr></table>
    </div>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="padding"><tr>
        <td align="left">{JUMPBOX}</td>
        <td align="right" width="30%">
            <!-- BEGIN reply -->
                <a href="{reply.URL}" class="button blue float-right"><span class="freply">{reply.TEXT}</span></a>
            <!-- END reply -->
            <!-- BEGIN qreply -->
                &nbsp;<a href="#quick_reply" class="button blue float-right"><span class="freply">{qreply.TEXT}</span></a>
            <!-- END qreply -->
        </td>
    </tr></table>
</div><br />
<div class="clear"></div>

<div id="post_container">
{POSTS}
</div>

<div class="content corners">
    <div class="title corners-top">
        <table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>
            <td align="left" valign="middle"><h4>{WATCH}</h4></td>
            <td align="right" class="padding">{PAGINATION}</td>
        </tr></table>
    </div>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="padding"><tr>
        <td align="left">{JUMPBOX2}</td>
        <td align="right">
        <!-- BEGIN reply -->
            <a href="{reply.URL}" class="button blue float-right"><span class="freply">{reply.TEXT}</span></a>
        <!-- END reply -->
        </td>
    </tr></table>
</div><br />

<!-- BEGIN qreply -->
<a name="quick_reply">&nbsp;</a>
<div class="content corners">
    <div class="title corners-top"><h4>{L_QUICK_REPLY}</h4></div>
    {F_START}
        {HIDDEN}
        <div id="preview" style="display:none;" class="preview"></div> 
        {F_QUICK_REPLY}
        <div class="clear"></div>
        <div class="float-right padding">{SUBMIT}</div>
        <div class="clear"></div>
    {F_END}
</div><br />
<!-- END qreply -->