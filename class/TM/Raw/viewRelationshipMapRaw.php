<?
/*
* $Id: viewRelationshipMapRaw.php 198 2010-08-14 01:38:16Z as $
*/

// INCOMING VARIABLES:

//* $post_steps (optional, default = 2)
//* $post_cid


$cid = (int)$post_cid;
$maxSteps = (int)$post_maxsteps;
if (!$maxSteps) $maxSteps = 1;
if ($maxSteps > 20 ) $maxSteps = 20;

require_once('TM/Core/Contact/RelationshipMap.php');
$map = new TM_Core_Contact_RelationshipMap($cid, $maxSteps);

$map->build();
$map->layout();

//$height = $map->nodes[$cid]->height / 2;
//$width = $map->nodes[$cid]->width / 2;

$maxY = 0; // highest point
$minX = 0; // leftmost point
foreach ($map->nodes as $node) {
    if ($node->y) {
        $maxY = $node->y > $maxY ? $node->y : $maxY;
    }
    if ($node->x) {
        $minX = $node->x < $minX ? $node->x : $minX;
    }
}

if ($map->nodes[$cid]->childNodes) {
    $e=0;
    foreach ($map->nodes as $cid => $node) {
        $x = ($node->x - $minX);
        $y = ($node->y * -1 + $maxY);
        $nodes[$cid]['x'] = $x;
        $nodes[$cid]['y'] = $y;
        $nodes[$cid]['display_name'] = $node->display_name;
        $nodes[$cid]['country'] = $node->country;
        $nodes[$cid]['typedetail'] = $node->typedetail;
        $nodes[$cid]['parentid'] = $node->parentid;
        $nodes[$cid]['access'] = $node->access;
        $nodes[$cid]['weight'] = $node->weight;
        $nodes[$cid]['haschildren'] = $map->descendents[$cid]['haschildren'];

        $edges[$e]['parentx'] = ($node->parentX - $minX);
        $edges[$e]['parenty'] = ($node->parentY * -1 + $maxY);
        $edges[$e]['x'] = $x;
        $edges[$e]['y'] = $y;
        $edges[$e]['weight'] = $node->weight;
        $e++;
    }
    $this->assign('INC_scrollXtarget', $nodes[$cid]['x']);
    $this->assign('INC_scrollYtarget', $nodes[$cid]['y']);
    $this->assign('INC_nodes', $nodes);
    $this->assign('INC_edges', $edges);
    $this->assign('INC_mapWidth', $map->nodes[$cid]->width);
    $this->assign('INC_mapHeight', $map->nodes[$cid]->height);
}


global $base_url;
$this->tpl->assign('INC_civicrm_root_url', $base_url . '/'. drupal_get_path('module','civicrm') . '/..');
$this->tpl->assign('INC_maxsteps', $maxSteps);
$this->tpl->assign('INC_base_url', $base_url);
$this->tpl->assign('INC_cid', $post_cid);


