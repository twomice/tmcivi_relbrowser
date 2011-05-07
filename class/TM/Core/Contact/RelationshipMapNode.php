<?php
/* 
 * $Id: RelationshipMapNode.php 198 2010-08-14 01:38:16Z as $
 */


class TM_Core_Contact_RelationshipMapNode {
    // weight of the node
    public $weight = 0;
    // node's distance from map's root node
    public $steps = 0;

    // absolute x,y coordinates
    public $x;
    public $y;

    // x,y coordinates relative to central node
    public $relativeX;
    public $relativeY;

    // absolute x,y coordinates of parent node
    public $parentX;
    public $parentY;

    public $cid;
    public $parentid;
    public $typedetail;
    public $access;

    public $height = 1;
    public $width = 1;

    public function __construct($props) {
        foreach($props as $prop => $val) {
            $this->$prop = $val;
        }
    }

}


