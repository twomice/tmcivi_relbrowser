{validate val=$post_cid matchtype = numeric name=val_cid}
{validate val=$post_oid matchtype = numeric name=val_oid}
{validate val=$post_maxsteps matchtype = numeric name=val_maxsteps}
{if !$val_maxsteps}
    {assign var=val_maxsteps value=2}
{/if}

{title}Relationship Path{/title}
<h2>From: {sqltext sql="select display_name from #c where id = %d" t="#c:civicrm_contact" p=$val_cid}<BR>
To: {sqltext sql="select display_name from #c where id = %d" t="#c:civicrm_contact" p=$val_oid}</h2>

<fieldset>

    <div style="float:left; margin-right: 5em;">
        {$form.maxsteps.label} {$form.maxsteps.html}
        {$form.buttons.html}
    </div>

    {l url="civicrm/tm/form" args="tmref=viewRelationshipList&cid=`$val_cid`&maxsteps=`$val_maxsteps`" caption="Done" button=true}
</fieldset>

{capture assign=src}{crmURL p="civicrm/tm/raw" q="tmref=viewRelationshipPathRaw&cid=`$val_cid`&oid=`$val_oid`&maxsteps=`$val_maxsteps`&tmformat=raw" }{/capture}
<iframe id="TM_mapFrame" src="{$src}"></iframe>
