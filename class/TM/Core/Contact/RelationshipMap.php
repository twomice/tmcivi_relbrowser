<?
/*
 * $Id: RelationshipMap.php 349 2010-09-18 17:54:36Z as $
 */

/**
 * Class of relationship maps
 */

class TM_Core_Contact_RelationshipMap {
    // cid of root individual
    private $cid = 0;

    // maximum number of steps in the map
    private $maxSteps = 0;

    // string of cids already reviewed
    private $doneCids = '';

    // flat array of descendents, with direct children and identifying properties for each
    public $descendents = array();

    // number of actual steps in the map
    private $steps = 0;

    // database access object
    public $db;

    // array of nodes and node clusters for entire map
    public $nodes = array();


    public function __construct($cid, $maxSteps) {
        $this->cid = $cid;
        $this->doneCids = $cid;
        $this->maxSteps = $maxSteps;
        $this->db = TM_Db::get();
    }

    public function getDescendents() {
        return $this->descendents;
    }

    public function build() {
        $this->_addDescendents($this->cid, 0);
        $this->_populateNodeProperties();
    }

    public function layout() {
        require_once 'TM/Core/Contact/RelationshipMapNode.php';
        require_once 'TM/Core/Contact/RelationshipMapNodeCluster.php';

        $this->_weightDescendent($this->descendents[$this->cid]);

         /* build an array of steps (descending) and descendent cids (descending by weight) in
         * each step */
        // sort all descendents descending by weight
        $this->_sortDescendents('weight', SORT_DESC);
         
         // build array of steps and descendents in each step
        foreach ($this->descendents as $descendent) {
            $stepDescendents[$descendent['steps']][] = $descendent['cid'];
        }

        // reverse-sort by keys:
        krsort($stepDescendents);

        // For each of these steps and its descendents
        foreach ($stepDescendents as $step => $descendentIds) {
            foreach ($descendentIds as $descendentId) {
                $childNodes = array();
                    if ($this->descendents[$descendentId]['children']) {
                        // build list of child nodes
                        foreach ($this->descendents[$descendentId]['children'] as $childId => $childProps) {
                            if (!$this->nodes[$childId]) {
                                $this->nodes[$childId] = new TM_Core_Contact_RelationshipMapNode($childProps);
                            }
                            $childNodes["$childId"] = $this->nodes[$childId];
                        }
                        // build list of nodeCluster properties (essentially, exclude 'children')
                        $nodeProperties = array_intersect_key(
                            $this->descendents[$descendentId],
                            array('cid'=>'', 'weight'=>'', 'steps'=>'', 'display_name'=>'', 'country' =>'', 'parentid' => '', 'typedetail' => '', 'access' => '')
                        );
                        // create the new nodeCluster
                        $this->nodes[$descendentId] = new TM_Core_Contact_RelationshipMapNodeCluster($nodeProperties, $childNodes);
                        // layout the nodeCluster
                        $this->nodes[$descendentId]->layout();
                    } else {
                        $this->nodes[$descendentId] = new TM_Core_Contact_RelationshipMapNode($this->descendents[$descendentId]);
                    }
                    
                /*
                createNodeCluster($nodeArray);
            a. Place child nodes around central node using placement algorithm.
            c. Calculate and store final width and height of placed node cluster (or, if node, height/width is 1).
           */
           }
        }
    }

    private function _sortDescendents($key, $sortArg = SORT_ASC) {
        foreach ($this->descendents as $descendent) {
            $sort[] = $descendent[$key];
        }
        array_multisort($sort, $sortArg, $this->descendents);
        foreach($this->descendents as $descendent) {
            $indexedDescendents[$descendent['cid']] = $descendent;
        }
        $this->descendents = $indexedDescendents;
    }

    private function _weightDescendent($descendent, $parents = NULL) {
        if (is_array($parents)) {
            foreach ($parents as $parentid => $bool) {
                $this->descendents[$parentid]['weight'] += 1;
            }
        }
        if ($descendent['children']) {
            $parents[$descendent['cid']] = true;
            foreach ($descendent['children'] as $childid => $child) {
                if ($this->descendents[$childid]) {
                    $this->_weightDescendent($this->descendents[$childid], $parents);
                }
            }

        } else {
            $this->descendents[$descendent['cid']]['weight'] = 0;
        }
    }

    private function _addDescendents($cid, $step) {
        $cid = (int)$cid;

        require_once('CRM/Contact/BAO/Contact/Permission.php');
        
        $this->steps = $step;

        $ar = array();
        $ar['cid'] = $cid;
        $ar['steps'] = $step;

        $query = "
            select if(contact_id_a  = $cid, contact_id_b, contact_id_a) as cid
            from {civicrm_relationship} 
            where (
                (
                    contact_id_a = $cid and contact_id_b not in ({$this->doneCids})
                ) or (
                    contact_id_b = $cid and contact_id_a not in ({$this->doneCids})
                )
            )";

        $ar['children'] = $this->db->get_rows_keyed($query);
        if ( ! is_array( $ar['children'] )) {
            return;
        }
        $children = array_keys($ar['children']);

        $this->descendents[$cid] = $ar;

        $this->doneCids .= rtrim(",".implode(',', $children), ',');

        $nextStep = $step+1;
        if ($nextStep >= $this->maxSteps) {
            if (!$ar['children']) {
                $this->descendents[$cid]['haschildren'] = 0;
            } else {
                foreach ($children as $childid) {
                    $this->descendents[$childid]['haschildren'] = $this->db->get_value(
                        "select 1 from {civicrm_relationship} where (contact_id_a = %d and contact_id_b <> %d) or (contact_id_b = %d and contact_id_a <> %d)",
                        $childid, 
                        $cid
                    );
                    $this->descendents[$childid]['cid'] = $childid;
                    $this->descendents[$childid]['steps'] = $nextStep;
                    $this->descendents[$childid] = array_merge($this->descendents[$childid], $ar['children'][$childid]);
                }
            }
            return;
        } else {
            foreach ($children as $childid) {
                if ( CRM_Contact_BAO_Contact_Permission::allow( $childid, CRM_Core_Permission::VIEW ) )  {
                    $this->_addDescendents($childid, $nextStep);
                }
            }
        }
    }

    private function _populateNodeProperties() {

        foreach ($this->descendents as $descendentId => $descendent) {
            if (is_array($descendent['children'])) {
                foreach ($descendent['children'] as $childId => $child) {
                    /* This complicated query collects the name and country for both the particular descendent we're looking at
                     * (the "parent") and the particular child of that descendent we're looking.  We apply these all differently below.
                     */
                    $row = $this->db->get_row("
                        select c.id as cid, c.display_name, cn.name as country, if(c.id = r.contact_id_a, rt.label_b_a, rt.label_a_b) as typedetail,
                            if(c.id = r.contact_id_a, r.contact_id_a, r.contact_id_b) as parentid,
                            child.display_name as child_display_name, child_cn.name as child_country
                        from {civicrm_contact} c
                        left join {civicrm_address} a on a.contact_id = c.id and a.is_primary = 1
                        left join {civicrm_country} cn on cn.id = a.country_id
                        left join {civicrm_relationship} r on c.id in (r.contact_id_a, r.contact_id_b)
                        left join {civicrm_relationship_type} rt on rt.id = r.relationship_type_id
                        left join {civicrm_contact} child on child.id = if(c.id = r.contact_id_a, r.contact_id_b, r.contact_id_a)
                        left join {civicrm_address} child_a on child_a.contact_id = child.id and child_a.is_primary = 1
                        left join {civicrm_country} child_cn on child_cn.id = child_a.country_id
                        where c.id = %d and %d in (r.contact_id_a, r.contact_id_b)",
                        $descendentId,
                        $childId
                    );

                    // Separate an array of child properties and another array of parent properties.
                    $childProps = array(
                        'typedetail' => $row['typedetail'],
                        'parentid'   => $row['parentid'],
                        'display_name' => $row['child_display_name'],
                        'country'   => $row['child_country'],
                    );
                    $descendentProps = array(
                        'display_name' => $row['display_name'],
                        'country'   => $row['country'],
                    );

                    /* Apply those child properties to the child element in $this->descendents[$x]['children'], and to the
                     * descendent itself (in $this->descendents)
                     */
                    $this->descendents[$descendentId]['children'][$childId] = array_merge($this->descendents[$descendentId]['children'][$childId], $childProps);
                    if ($this->descendents[$childId]) {
                        $this->descendents[$childId] = array_merge($this->descendents[$childId], $childProps);
                        $this->descendents[$childId]['access'] = CRM_Contact_BAO_Contact_Permission::allow( $childId, CRM_Core_Permission::VIEW );
                    }

                    // Also apply those descendent properties to the descendent itself.
                    if ($this->descendents[$descendentId]) {
                        $this->descendents[$descendentId] = array_merge($this->descendents[$descendentId], $descendentProps);
                        $this->descendents[$descendentId]['access'] = CRM_Contact_BAO_Contact_Permission::allow( $descendentId, CRM_Core_Permission::VIEW );
                    }
                }
            }
        }
    }
}
