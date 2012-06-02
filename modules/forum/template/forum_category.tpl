{CATEGORY}ffdsfds

<!-- BEGIN threads -->
<div class="float-right">
    {PAGINATION}
</div>
<div class="clear"></div>

<div class="content corners">
    <div class="title corners-top iblock">
        <h4 class="float-left">{threads.CAT}</h4>
        <div class="float-right padding">
        {threads.SEARCH}
        <!-- BEGIN post -->
        <a href="{threads.post.URL}" class="button black"><span class="fnewpost">{threads.post.TEXT}</span></a>
        <!-- END post -->
        </div>
        <div class="clear"></div>
    </div>
    <table width="100%" cellspacing="1" cellpadding="2" class="corners tborder">
      <tr class="thead">
        <th colspan="2">{threads.L_THREAD_TITLE}</th>
        <th width="12%">{threads.L_AUTHOR}</th>
        <th width="12%">{threads.L_VIEWS}</th>
        <th width="12%">{threads.L_REPLIES}</th>
        <th width="32%" colspan="2">{threads.L_LASTPOST}</th>
      </tr>
    <!-- BEGIN row -->
      <tr id="{threads.row.ID}" class="{threads.row.CLASS} highlight">
        <td width="5%" align="center"><img src="{threads.row.ICON}" /></td>
        <td width="36%" align="left" class="padding" data-url="{threads.row.URL}">
        <a href="{threads.row.URL}">{threads.row.TITLE}</a>
        <!-- BEGIN pagination -->
        <br />{threads.row.pagination.SHOW}
        <!-- END pagination -->
        </td>
        <td align="center">{threads.row.AUTHOR}</td>
        <td align="center">{threads.row.VIEWS}</td>
        <td align="center">{threads.row.REPLIES}</td>
        <td class="padding" data-url="{threads.row.LP_URL}">{threads.row.LP_AUTHOR}<br />{threads.row.LP_TIME}</td>
      </tr>
    <!-- END row -->

    <!-- BEGIN search -->
      <tr id="{threads.row.ID}" class="{threads.row.CLASS}">
        <td colspan="7">{threads.row.POST}</td>
      </tr>
    <!-- END search -->

    <!-- BEGIN hr -->
      <tr id="{threads.row.ID}" class="{threads.row.CLASS}">
        <td colspan="7" align="center"><hr /></td>
      </tr>
    <!-- END hr -->

    <!-- BEGIN error -->
      <tr>
        <td colspan="7" align="center">{threads.error.ERROR}</td>
      </tr>
    <!-- END error -->

    </table>
    <div class="clear"></div>
</div>
<!-- END threads -->

<br />
<table border="0" cellspacing="1" cellpadding="1" align="right" class="content corners-top">
  <tr>
    <td class="row_color1 padding" align="center"><img src="{I_NO_POSTS}" /></td>
    <td class="row_color1 padding">{L_NO_POSTS}</td>
    <td class="row_color2 padding" align="center"><img src="{I_NEW_POSTS}" /></td>
    <td class="row_color2 padding">{L_NEW_POSTS}</td>
    <td class="row_color1 padding" align="center"><img src="{I_LOCKED}" /></td>
    <td class="row_color1 padding">{L_LOCKED}</td>
  </tr>
</table>
<div class="clear"></div>
