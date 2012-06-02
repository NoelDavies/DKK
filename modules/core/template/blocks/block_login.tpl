    <div id="loginBox">
    {FORM_START}
    <table width="100%">
      <tr>
        <td width="45%" valign="middle">{L_USERNAME}</td>
        <td>{F_USERNAME}</td>
      </tr>
      <tr>
        <td valign="middle">{L_PASSWORD}</td>
        <td>{F_PASSWORD}</td>
      </tr>
      <!-- BEGIN remember_me -->
      <tr>
        <td valign="middle">{L_REMME}</td>
        <td>{F_REMME}</td>
      </tr>
      <!-- END remember_me -->
      <!-- BEGIN captcha -->
      <tr>
        <td valign="middle">{L_CAPTCHA}</td>
        <td>{captcha.CAPTCHA}</td>
      </tr>
      <!-- END captcha -->
      <tr>
        <td colspan="2" align="center">{SUBMIT} {REGISTER} {FORGOT_PWD}</td>
      </tr>
    </table>
    {FORM_END}    
    </div>