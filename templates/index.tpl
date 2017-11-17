{include file='modules_header.tpl'}

<table cellpadding="0" cellspacing="0">
    <tr>
        <td width="45"><a href="index.php"><img src="images/icon_preparser.gif" border="0" width="34" height="34"/></a>
        </td>
        <td class="title">
            <a href="../../admin/modules">{$LANG.word_modules}</a>
            <span class="joiner">&raquo;</span>
            {$L.module_name}
        </td>
    </tr>
</table>

{include file='messages.tpl'}

{if $num_results == 0}

    <div class="notify margin_bottom_large">
        <div style="padding:8px">
            {$L.notify_no_rules}
        </div>
    </div>

{else}

    {$pagination}
    <table class="list_table" style="width:100&" cellpadding="1" cellspacing="1">
        <tr style="height: 20px;">
            <th width="30"></th>
            <th>{$L.phrase_rule_name}</th>
            <th>{$LANG.word_status}</th>
            <th>{$LANG.word_form_sp}</th>
            <th class="edit"></th>
            <th class="del"></th>
        </tr>

        {foreach from=$results item=result name=row}
            {assign var=rule_id value=$result.rule_id}
            <tr>
                <td class="medium_grey" align="center">{$result.rule_id}</td>
                <td>{$result.rule_name}</td>
                <td align="center">
                    {if $result.status == "enabled"}
                        <span class="green">{$LANG.word_enabled}</span>
                    {else if $result.status == "disabled"}
                        <span class="red">{$LANG.word_disabled}</span>
                    {/if}
                </td>
                <td class="pad_left_small">
                    {if $result.form_ids|@count == 0}
                        <span class="medium_grey">{$LANG.phrase_no_forms}</span>
                    {else}
                        {forms_dropdown name_id="tmp" display_single_form_as_text=true only_show_forms=$result.form_ids}
                    {/if}
                </td>
                <td class="edit"><a href="edit.php?rule_id={$rule_id}"></a></td>
                <td class="del"><a href="#" onclick="return page_ns.delete_rule({$rule_id})"></a></td>
            </tr>
        {/foreach}

    </table>
{/if}

<form action="add.php" method="post">
    <p>
        <input type="submit" value="{$L.phrase_add_rule|upper}"/>
    </p>
</form>

{include file='modules_footer.tpl'}
