<table width="100%" border="0" cellspacing="0" cellpadding="0">
<!-- BEGIN error -->
<tr><td align="center">{error.MESSAGE}</td></tr>
<!-- END error -->
<!-- BEGIN threadRow -->
<tr>
    <td id="{threadRow.ID}" class="{threadRow.CLASS}">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="13%" rowspan="3" align="center"><img src="{threadRow.ICON}" /></td>
            <td width="87%">{threadRow.L_TITLE}: <a href="{threadRow.HREF}" title="{threadRow.TR_TITLE}">{threadRow.TITLE}</a></td>
          </tr>
          <tr><td>{threadRow.L_AUTHOR}: {threadRow.AUTHOR}</td></tr>
        </table>
    </td>
</tr>
<!-- END threadRow -->
<!-- BEGIN userRow -->
<tr>
    <td id="{userRow.ID}" class="{userRow.CLASS} padding">
        <div class="float-left">
            {userRow.USERNAME} ( {userRow.COUNT} )
        </div>
        <div class="float-right">
            {userRow.PER_DAY}
        </div>
    </td>
</tr>
<!-- END userRow -->
</table>
