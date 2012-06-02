<div id="modulePMMainContainer" class="corners" style="height: 100%; width: 100%; clear:both;">
	<div class="title corners">
		<div class="padding" style="font-size: 12px;">
			<h3>{TITLE}</h3>
		</div>
	</div>
	<div class="content padding">
    
        <div style="float: left;">
            {INVOLVED} <br />
			{SENT}
    	</div>
        <div class="clear"></div>
        
        <!-- BEGIN error -->
        <div class="pmError pm_box_message corners padding">
        	{error.E_ERROR}
		</div>
        <!-- END error -->
        
		<!-- BEGIN messages -->
        <table style="border-collapse: collapse; width: 99%; border-spacing: 2px 2px; display: block;" class="pm_box_message corners padding cursorClickable">
            <tbody>
                <tr style="height: 56px; vertical-align: middle; border-color: inherit;">
				
                    <td style="text-align: center; pading: 0px; margin: 0px; width: 30px !important;">{messages.HIDDEN_INPUT}</td>
                    <td style="width: 150px; padding-left: 5px;" class="padding">   
                        <div><small>{messages.TIMESTAMP}</small></div>
                    </td>
                    <td style="padding-left: 5px;" class="padding">
                        <div style="color: gray;">
							{messages.MESSAGE}
						</div>
                    </td>
                    <td class="padding" style="width: 51px;">
                    </td>
                </tr>
            </tbody>
        </table>
        <!-- END messages -->
	</div>
</div>