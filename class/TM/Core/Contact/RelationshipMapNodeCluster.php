<?php
/* 
 * $Id: RelationshipMapNodeCluster.php 198 2010-08-14 01:38:16Z as $
 */

/** This is a node and all its descendents
 * A mapNodeCluster may contain nodes (direct children who themselves have no children) and
 * other mapNodeClusters.
 */
require_once 'TM/Core/Contact/RelationshipMapNode.php';

class TM_Core_Contact_RelationshipMapNodeCluster extends TM_Core_Contact_RelationshipMapNode {

    // array of direct children (not all descendents) (may be nodes or mapNodeClusters)
    public $childNodes;

    // array of direct children with basic properties of each

    public function __construct($props, $childNodes) {
        $this->childNodes = (array)$childNodes;
        foreach ($props as $prop => $val) {
            $this->$prop = $val;
        }
    }

    public function rotate($direction) {

    }

    public function recenter() {
        foreach ($this->childNodes as $node) {
            //dump("recentering {$node->fullname} (child of {$this->fullname}) around {$this->x}, {$this->y}");
            $node->x = $this->x + $node->relativeX;
            $node->y = $this->y + $node->relativeY;
            $node->parentX = $this->x;
            $node->parentY = $this->y;
            if ($node->childNodes) {
                $node->recenter();
            }
        }
    }

    public function layout() {
        /*  Premise: node clusters centering around children of the central node will be treated as nodes; thus the term "node" in this section may refer to a single node or to a node cluster.
        Premise: nodes having weight (descendents) (i.e., nodes which are actually node clusters) must be on outer ring.
        Premise: arrange nodes in this pattern to accommodate one right-angle line from "due north" on outer ring to center node: ring 1 is completely empty, and "due north" position on each ring is empty.
        */
        /* For processing, build of list of child nodes in order from heaviest to lightest
         * (i.e., any with no children will be last).
         * Determine the greatest height and greatest width of all nodes.  We'll use the greater
         * of these two as the size of any node in this cluster.
         */
        $maxWidth = 0;
        $maxHeight = 0;
        $childBearingNodeCount = 0;
        $totalNodeCount = count($this->childNodes);
        $blockHeight = 1;
        $blockWidth = 1;
        foreach ($this->childNodes as $node) {
            $sort[] = $node->weight;
            // Count the number of child-bearing nodes
            if ($node->weight) $childBearingNodeCount++;
            $blockWidth = $node->width > $blockWidth ? $node->width : $blockWidth;
            $blockHeight = $node->height > $blockHeight ? $node->height : $blockHeight;
        }

        array_multisort($sort, SORT_DESC, $this->childNodes);

        /* Expand outer ring until it's large enough to accommodate all child-bearing nodes AND
         * until all nodes can be placed in available spaces within the outer ring or interior
         * rings. (rings increase by size in increments of 8, except one is blank, and we start
         * from ring 2).
         */
        $outerRing = 2;
        $outerRingSpaces = 15;
        $totalSpaces = 15;

        while ( $outerRingSpaces < $childBearingNodeCount || $totalSpaces < $totalNodeCount) {
            $outerRing++;
            $outerRingSpaces = (($outerRing * 8) - 1);
            $totalSpaces += $outerRingSpaces;
        }

        $newRing = true;
        $ring = $outerRing;
        foreach ($this->childNodes as $cid => $node) {
            /* place the node on the next available space according to this order:
                on any ring, placement starts from the "due south" position and progresses alternately outward toward "south west" and "south east" corners, then alternately up the "west" and "east" sides, continuing to the "NNW" and "NNE" positions directly adjacent to "due north".
            */
            if (isset($corners["$x, $y"]) && $corners["$x, $y"] == 'END') {
                $newRing = true;
                $ring--;
            }

            if ($newRing) {
                $newRing = false;
                $x = 0;
                $y = $ring * -1;
                $yfixed = $y;
                $xincrement = 1;
                $i = 1;
                $corners = array (
                    "$ring, -$ring" => 'se',
                    "-$ring, -$ring" => 'sw',
                    "$ring, $ring" => 'ne',
                    "-$ring, $ring" => 'nw',
                    "-1, $ring" => 'END'
                );
            } else {
                if (isset($corners["$x, $y"])) {
                    switch ($corners["$x, $y"]) {

                        case 'se':
                        // after southeast corner:
                            // abs($x) is fixed
                            unset($xincrement);
                        break;

                        case 'sw':
                        // after southwest corner:
                            // y is not fixed (it's varying)
                            unset($yfixed);
                        break;

                        case 'nw':
                        // after northwest corner:
                            // y is fixed again (not varying)
                            $yfixed = $y;
                            // abs($x) is decrementing
                            $xincrement = -1;
                            // x is varying again
                            $xroot = false;
                        break;
                    }
                }

                // calculate whether iteration is even or odd
                $iIsOdd = ($i % 2);

                if (isset($yfixed)) {
                    $y = $yfixed;
                } else {
                    $y = $iIsOdd ? $y : $y + 1 ;
                }

                if (!isset($xincrement)) {
                    $x = ($x * -1);
                } else {
                    $x = $iIsOdd ? ($x * -1) : (abs($x) + $xincrement);
                }
            }

            $node->x = $x * $blockWidth;
            $node->y = $y * $blockHeight;
            $node->relativeX = $node->x;
            $node->relativeY = $node->y;

            /*
            b. if the node has weight (descendents) (meaning it's already a node cluster) rotate the node as necessary based on its placement, so the the node's "due north" path is turned to west, south or east, as needed, to be pointing most nearly toward the central node.
            */
            if ($node->weight) {
                // rotate based on the side of the cluster we're on
                if ($y == $ring * -1) {
                    //side = "south";
                    // no rotation.
                } elseif ($y == $ring) {
                    //side = "north"
                    $node->rotate('south');
                } elseif ($x < 0) {
                    //side = "west"
                    $node->rotate('east');
                } else {
                    //side = "east"
                    $node->rotate('west');
                }

                // recenter the cluster's children around the node's x,y coordinates
                $node->recenter();
            }
            // record number of nodes on outer ring, for later calculation of height/width
            if ($ring == $outerRing) {
                $outerRingI = $i;
            }
            $i++;
        }

        // largest grid size (in relative units), based on outer ring:
        $maxGridSize = ($outerRing * 2 + 1);
        // grid width is the smaller of $outerRingI or $maxGridSize
        $gridWidth = $outerRingI < $maxGridSize ? $outerRingI : $maxGridSize;

        if ($outerRingI <= ($maxGridSize * 2 - 1)) {
            $gridHeight = $outerRing + 1;
        } else {
            $gridHeight = ceil($outerRingI - ($maxGridSize * 2 - 1)/2) + $outerRing + 1;
            $gridHeight = $gridHeight < $maxGridSize ? $gridHeight : $maxGridSize;
        }
        $this->height = $blockHeight * $gridHeight;
        $this->width = $blockWidth * $gridWidth;
    }
}