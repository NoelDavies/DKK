<div class="content corners">
    <div class="title corners-top iblock">
        <h4 class="float-left">{ADMIN_MODE}</h4>
        <div class="float-right padding">{L_LAST_CHANGED}</div>
    </div>
    <div class="padding">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td align="center">{CREATE_NEW}</td>
            <td align="center">{UPDATE_OLD}</td>
            <td align="center">{CHANGED_ONLY}</td>
          </tr>
        </table>
        <br />
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tborder">
          <tr>
            <td class="thead padding">{L_FILENAME}</td>
            <td class="thead padding">{L_STATUS}</td>
          </tr>
        <!-- BEGIN filestructure -->
          <tr class="{filestructure.ROW}">
            <td class="padding">{filestructure.FNAME}</td>
            <td align="center">{filestructure.STATUS}</td>
          </tr>
        <!-- END filestructure -->
        </table>
    </div>
</div><br />
{OUTPUT}