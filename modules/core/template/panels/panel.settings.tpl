{FORM_START}
<div class="content corners">
    <div class="title corners-top iblock">
        <h4 class="float-left">{FORM_TITLE}</h4>
        <div class="float-right padding">{FORM_SUBMIT} {FORM_RESET}</div>
    </div>
    <div>
        {HIDDEN}
        <table width="100%" border="0" cellspacing="0" cellpadding="5">
        <!-- BEGIN msg -->
          <tr>
            <td colspan="2" class="padding">{msg.MSG}</td>
          </tr>
        <!-- END msg -->
        <!-- BEGIN _form_error -->
          <tr>
            <td colspan="2" class="boxred">{_form_error.ERROR_MSG}</td>
          </tr>
        <!-- END _form_error -->
        <!-- BEGIN _form_row -->
            <!-- BEGIN _header -->
              <tr>
                <td colspan="2" class="title" valign="middle">{_form_row._header.TITLE}</td>
              </tr>
            <!-- END _header -->
            <!-- BEGIN _field -->
              <tr class="formRow{_form_row._field.CLASS}">
                <td width="40%" valign="top">
                    <!-- BEGIN label -->
                    <label for="{_form_row._field.L_LABELFOR}">
                    <!-- END label -->
                        <strong>{_form_row._field.L_LABEL}:</strong>        
                        <!-- BEGIN _desc -->
                        <br /><small class="grid_3">{_form_row._field.F_INFO}</small>
                        <!-- END _desc -->
                    <!-- BEGIN _label -->
                    </label>
                    <!-- END _label -->
                </td>
                <td width="60%" valign="middle">{_form_row._field.F_ELEMENT}</td>
              </tr>
            <!-- END _field -->
        <!-- END _form_row -->
        </table>
        <div class="clear"></div>
        <div class="float-right padding">{FORM_SUBMIT} {FORM_RESET}</div>
        <div class="clear"></div>
    </div>
</div><br />
{FORM_END}