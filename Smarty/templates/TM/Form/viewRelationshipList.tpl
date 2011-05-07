{title}Relationship List for {sqltext sql="select display_name from #t where id = %d" t="#t:civicrm_contact" p=$post_cid}{/title}

{capture assign="doneButton"}{l url="civicrm/contact/view" args="reset=1&cid=`$post_cid`&selectedChild=rel" caption="Done" button=true}{/capture}

{if $INC_list}
    <fieldset>
        <div style="float:left; margin-right: 5em;">
            {$form.maxsteps.label} {$form.maxsteps.html}
            {$form.buttons.html}
        </div>

        {$doneButton}

    </fieldset>

    <table>
        <thead class="sticky">
            <tr><th>
                Name
            </th><th>
                Country
            </th><th>
                Steps
            </th></tr>
        </thead>

        {foreach from=$INC_list item=me}
            <tr class="{cycle values="odd-row,even-row"}"><td>
                {if $me.access}
                    <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$me.cid`"}">{$me.display_name}</a>
                {else}
                    {$me.display_name}
                {/if}
            </td><td>
                {$me.country}
            </td><td>
                {$me.steps}
                {if $me.steps > 1}
                    {l ref=viewRelationshipPath caption="[View Path]" args="cid=$post_cid&oid=`$me.cid`&maxsteps=`$me.steps`&reset=1" style="margin-left: .5em"}
                {/if}
            </td></tr>
        {/foreach}

    </table>
{else}
    <em style="display:block; margin: 1em;">No relationships found.</em>
    {$doneButton}
{/if}

