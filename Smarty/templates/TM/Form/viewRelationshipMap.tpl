{validate val=$post_cid matchtype = numeric name=val_cid}
{validate val=$post_maxsteps matchtype = numeric name=val_maxsteps}
{if !$val_maxsteps}
    {assign var=val_maxsteps value=2}
{/if}

{title}Relationship Map for {sqltext sql="select display_name from #c where id = %d" t="#c:civicrm_contact" p=$post_cid}{/title}

<fieldset>

    <div style="float:left; margin-right: 5em;">
        {$form.maxsteps.label} {$form.maxsteps.html}
        {$form.buttons.html}
    </div>

    {l url="civicrm/contact/view" args="reset=1&cid=`$post_cid`&selectedChild=rel" caption="View Contact" button=true}
</fieldset>

{capture assign=src}{crmURL p="civicrm/tm/raw" q="tmref=viewRelationshipMapRaw&cid=`$val_cid`&maxsteps=`$val_maxsteps`&tmformat=raw" }{/capture}
<iframe id="TM_mapFrame" src="{$src}"></iframe>
