<?php
/*
 * $Id: viewRelationshipPathRaw.php 349 2010-09-18 17:54:36Z as $
 */

$cid = (int)$post_cid;
$oid = (int)$post_oid;
$maxSteps = (int)$post_maxsteps;

if (!$maxSteps) $maxSteps = 1;
if ($maxSteps > 20) $maxSteps = 20;

require_once('TM/Core/Contact/RelationshipPathMap.php');
$map = new TM_Core_Contact_RelationshipPathMap($cid, $oid, $maxSteps);

// gather all shortest paths up to $maxSteps deep
$map->getAllPaths();

/* paths in $map->paths will not include root and target nodes, so we add them
 * manually here, along with priming for the placement of nodes just below:
 */
// insert cid and oid for fetching nodeProperties (fullname, etc.)
$nodeids[] = $cid;
$nodeids[] = $oid;

// manually insert x,y coordinates for root node
$nodes[0][$cid]['x'] = 0;
$nodes[0][$cid]['y'] = 0;

$x = 0;
$n = 1;
$e = 0;

foreach ($map->paths as $pathLength => $paths) {
    foreach ($paths as $path) {
        // y=0 is reserved for the root node, so, for new paths, reset to y=1
        $y = 1;
        // also, on a new path, the parent is the root node, so set parentX and parentY for that.
        $parentX = 0;
        $parentY = 0;

        foreach ($path as $nodeid => $nodeProps) {
            $nodeids[] = $nodeid;
            $nodes[$n][$nodeid]['x'] = $x;
            $nodes[$n][$nodeid]['y'] = $y;
            $nodes[$n][$nodeid]['typedetail'] = $nodeProps['typedetail'];
            $nodes[$n][$nodeid]['cid'] = $nodeProps['cid'];
            $edges[$e]['x'] = $x;
            $edges[$e]['y'] = $y;
            $edges[$e]['parentx'] = $parentX;
            $edges[$e]['parenty'] = $parentY;
            $parentX = $x;
            $parentY = $y;
            $n++;
            $y++;
            $e++;
        }
    
        // increment $x to scoot things over by one for the next path.
        $x++;
    }
}

// Add edges for all nodes
if (is_array($targetEdges)) {
    foreach ($targetEdges as $edgeProps) {
        $edges[$e]['x'] = 0;
        $edges[$e]['y'] = $y;
        $edges[$e]['parentx'] = $edgeProps['parentx'];
        $edges[$e]['parenty'] = $edgeProps['parenty'];
        $e++;
    }
}

if ($nodeids) {
    $db = TM_Db::get();
    $query = "
        select c.id as cid, c.display_name, cn.name as country, '$step' as steps
        from {civicrm_contact} c
        left join {civicrm_address} a on a.contact_id = c.id and a.is_primary = 1
        left join {civicrm_country} cn on cn.id = a.country_id
        where c.id in (". implode(',', $nodeids) .")
    ";
    $nodeProps = $db->get_rows_keyed($query);
    $this->assign('INC_nodeProps', $nodeProps);
}

$this->assign('INC_nodes', $nodes);
$this->assign('INC_edges', $edges);


$this->assign('INC_civicrm_root_url', base_path() . '/'. drupal_get_path('module','civicrm') . '/..');

