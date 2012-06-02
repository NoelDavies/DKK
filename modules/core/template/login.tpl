{FORM_START}
<fieldset class="block grid_6 center">

    <!-- BEGIN form_error -->
    <div class="{form_error.CLASS} padding">{form_error.ERROR}</div>
    <div class="clear">&nbsp;</div>
    <!-- END form_error -->

    <div>
        <label for="username">{L_USERNAME}:</label>
        <span class="field">{F_USERNAME}</span>
    </div><div class="clear">&nbsp;</div>

    <div>
        <label for="username">{L_PASSWORD}:</label>
        <span class="field">{F_PASSWORD}</span>
    </div><div class="clear">&nbsp;</div>

    <!-- BEGIN pin -->
    <div>
        <label for="username">{L_PIN}:
            <br /><small class="wrap grid_2">{L_PIN_DESC}</small>
        </label>
        <span class="field">{F_PIN}</span>
    </div><div class="clear">&nbsp;</div>
    <!-- END pin -->

    <!-- BEGIN remember_me -->
    <div>
        <label for="username">{L_REMBER_ME}:</label>
        <span class="field">{F_REMBER_ME}</span>
    </div><div class="clear">&nbsp;</div>
    <!-- END remember_me -->

    <div class="clear">&nbsp;</div>
    <div class="align-center"> {F_SUBMIT} {F_RESET} </div>
</fieldset>
{FORM_END}