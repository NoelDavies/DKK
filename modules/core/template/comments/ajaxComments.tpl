<!-- BEGIN comment -->
<script> Effect.SlideDown('{comment.cID}'); </script>
<article id="{comment.cID}" class="comment corners {comment.ROW}">
    <header id="comment" class="padding corners-top">
        <div class="comment-title float-left">Posted By: {comment.AUTHOR} on {comment.POSTED}</div>
        <!-- BEGIN functions -->
        <a href="{comment.functions.URL}" id="btnRM" cmntId="{comment.ID}" class="float-right button remove">x</a>
        <!-- END functions -->
    </header><div class="clear">&nbsp;</div>
    <blockquote class="comments">{comment.POST}</blockquote>
</article>
<!-- END comment -->