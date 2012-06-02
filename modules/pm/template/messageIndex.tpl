<div id="modulePMMainContainer" class="corners" style="height: 100%; width: 100%; clear:both;">
	<div class="title corners">
		<div class="padding" style="font-size: 12px;">
			<h3>{L_YOURINBOX}</h3>
		</div>
	</div>
	<div class="content padding">
    
        <div style="float: left;">
            {B_NEW_PM}
            {B_DEL_PM}
            {B_MARKREAD_PM}
            {B_MARKUNREAD_PM}
        </div>
        <div class="clear"></div>
        
        <!-- BEGIN error -->
        <div class="pmError pm_box_message corners padding">{error.E_ERROR}</div>
        <!-- END error -->
        
		<!-- BEGIN messages -->
        <table style="border-collapse: collapse; width: 99%; border-spacing: 2px 2px;" style="display: block;" class="pm_box_message corners padding cursorClickable">
            <tbody>
                <tr style="height: 56px; vertical-align: middle; border-color: inherit;">
                    <td style="width: 16px;" class="padding"><img src="{messages.READ_ICON}" class="cursorClickable" onclick="markAs(); return false;" width="16px;" /></td>
                    <td style="text-align: center; pading: 0px; margin: 0px; width: 30px !important;">{messages.HIDDEN_INPUT}</td>
                    <td style="width: 50px;" class="padding">{messages.AUTHOR_AVATAR}</td>
                    <td style="width: 150px; padding-left: 5px;" class="padding">   
                        <div>{messages.AUTHOR_NAME}</div>
                        <div><small>{messages.TIMESTAMP}</small></div>
                    </td>
                    <td style="padding-left: 5px;" class="padding">
                        <div><a href="{messages.THREAD_URL}">{messages.FULL_SUBJECT}</a></div>
                        <div style="color: gray;">{messages.MESSAGE}</div>
                    </td>
                    <td class="padding" style="width: 51px;">
                        <!-- BEGIN options -->
                            <a href="{messages.options.HREF}">
                                <img src="{messages.options.IMG_PATH}" alt="{messages.options.IMG_ALT}" title="{messages.options.IMG_ALT}" class="pm_box_preview_list_icon"{messages.options.EXTRA} /> {messages.options.NAME}
                            </a>
        				<!-- END options -->
                    </td>
                </tr>
            </tbody>
        </table>
        <!-- END messages -->
	</div>
</div>