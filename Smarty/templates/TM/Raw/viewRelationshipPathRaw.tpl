<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

{assign var=ruleSpacing value=60}
{assign var=nodeSize value=50}
{assign var=margin value=25}

<html>
<head>
<META http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Relationship Path</title>

<script type="text/javascript">
    var graphHeight = {$INC_mapHeight*$ruleSpacing+$margin};
    var graphWidth = {$INC_mapWidth*$ruleSpacing+$margin};
    var hasNodes = '{$INC_nodes}';
</script>
<script type="text/javascript" src="{$TM_RAW_RESOURCE_ROOT_URL}/js/wz_jsgraphics.js"></script>
<script type="text/javascript" src="{$INC_civicrm_root_url}/packages/jquery/jquery.js"></script>
<script type="text/javascript" src="{$TM_RAW_RESOURCE_ROOT_URL}/js/auto/viewRelationshipMapRaw.js"></script>
<link href="{$TM_RAW_RESOURCE_ROOT_URL}/css/auto/viewRelationshipMapRaw.css" rel="stylesheet" type="text/css">
<link href="{$TM_RAW_RESOURCE_ROOT_URL}/css/auto/viewRelationshipPathRaw.css" rel="stylesheet" type="text/css">

<style type="text/css">
    .node {$smarty.ldelim}
        height: {$nodeSize}px;
        width: {$nodeSize}px;
    {$smarty.rdelim}
</style>

</head>
<body>

<div id="graph">
    {if $INC_nodes}
        {foreach from=$INC_nodes item=path}
            {foreach from=$path key=cid item=me}
                {assign var=oid value=$me.cid}
                <div id="node{$me.x}_{$me.y}"
                    class="node {if $cid == $post_contactid}root{/if}{if $me.haschildren === 0}nochildren{/if}"
                    style="
                        left: {$me.x*$ruleSpacing+$margin}px;
                        top: {$me.y*$ruleSpacing+$margin}px;
                    "
                    title="{$INC_nodeProps.$cid.display_name} {if $INC_nodeProps.$cid.country} ({$INC_nodeProps.$cid.country}){/if}{if $me.typedetail}, {$me.typedetail} {$INC_nodeProps.$oid.display_name}{/if}"
                >
                    <span>{$INC_nodeProps.$cid.display_name} {if $INC_nodeProps.$cid.country}({$INC_nodeProps.$cid.country}){/if}</span>

                </div>
            {/foreach}
        {/foreach}
    {else}
        <div id="noRelationships">No relationships found.</div>
    {/if}
</div>


<script type="text/javascript">
/*    var targetX = {$INC_scrollXtarget*$ruleSpacing+$nodeSize/2+$margin};
    var targetY = {$INC_scrollYtarget*$ruleSpacing+$nodeSize/2+$margin};

    window.scroll(targetX - getWindowWidth() / 2, targetY - getWindowHeight() / 2);
*/

    var wz = new jsGraphics('graph');
    wz.setColor('gray');
    {foreach from=$INC_edges item=me}
        wz.drawLine({$me.parentx*$ruleSpacing+$nodeSize/2+$margin}, {$me.parenty*$ruleSpacing+$nodeSize/2+$margin}, {$me.x*$ruleSpacing+$nodeSize/2+$margin}, {$me.y*$ruleSpacing+$nodeSize/2+$margin});
    {/foreach}
    wz.paint();


    $("#noRelationships").css("top", getWindowHeight() / 2 - 100);
    $("#noRelationships").css("left", getWindowWidth() / 2 - 100);

</script>

</body>
</html>


