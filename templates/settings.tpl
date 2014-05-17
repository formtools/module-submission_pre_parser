{include file='modules_header.tpl'}

  <div class="title">{$LANG.word_settings|upper}</div>

  {include file='messages.tpl'}

  <form action="{$same_page}" method="post">

    <div>
      {$L.phrase_num_rules_per_page_c} <input type="text" size="5" name="num_rules_per_page" value="{$num_rules_per_page}" />
    </div>

    <p>
      <input type="submit" name="update" value="{$LANG.word_update|upper}" />
    </p>

  </form>

{include file='modules_footer.tpl'}