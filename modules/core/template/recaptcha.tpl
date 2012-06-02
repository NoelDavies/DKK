<script>
var RecaptchaOptions = { theme: "custom", lang: "en", custom_theme_widget: "recaptcha_widget" };
</script>

<div id="recaptcha_widget" style="border: 1px solid #abadb3; margin-bottom: 5px; height:57px; background:#fff; float:right; width: 60%">
<div id="recaptcha_image"></div>
<input type="text" id="recaptcha_response_field" name="recaptcha_response_field" style="width: 99%;" required="required" />
</div>

<script type="text/javascript" src="http://api.recaptcha.net/challenge?k={PUBLIC_KEY}{ERR}&lang=en"></script>
<noscript>
  <iframe src="http://api.recaptcha.net/noscript?k={PUBLIC_KEY}{ERR}&lang=en" height="300" width="500" frameborder="0"></iframe><br />
  <textarea name="recaptcha_challenge_field" rows="3" cols="40" required="required"></textarea>
  <input type="hidden" name="recaptcha_response_field" value="manual_challenge" />
</noscript>

<script>
window.onload = function() {
    Recaptcha.focus_response_field();
    document.getElementById("recaptcha_image").style.width = "100%";
    var count = "1";
}
</script>
<style>
.recaptcha_audio_cant_hear_link{ display: none; visibility: hidden; }
</style>
<div class="clear">&nbsp;</div>