{include file='modules_header.tpl'}

<table cellpadding="0" cellspacing="0">
    <tr>
        <td width="45"><a href="index.php"><img src="images/icon_preparser.gif" border="0" width="34" height="34"/></a>
        </td>
        <td class="title">
            <a href="../../admin/modules">{$LANG.word_modules}</a>
            <span class="joiner">&raquo;</span>
            <a href="./">{$L.module_name}</a>
            <span class="joiner">&raquo;</span>
            {$L.word_settings}
        </td>
    </tr>
</table>

{ft_include file='messages.tpl'}

<form action="{$same_page}" method="post">

    <div>
        {$L.phrase_num_rules_per_page_c} <input type="text" size="5" name="num_rules_per_page"
                                                value="{$num_rules_per_page}"/>
    </div>

    <p>
        <input type="submit" name="update" value="{$LANG.word_update|upper}"/>
    </p>

</form>

{include file='modules_footer.tpl'}
