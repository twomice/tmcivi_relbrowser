<?
/*
 * $Id: RelationshipPathMap.php 198 2010-08-14 01:38:16Z as $
 */

/**
 * Class of relationship maps
 */

class TM_Core_Contact_RelationshipPathMap {

    public $maxsteps = 0;
    public $cid = 0;
    public $oid = 0;
    public $paths = array();

    // used to keep track of which length of paths we're getting at a given moment
    private $targetPathLength = 0;

    // flat list for storage for used contactids
    public $usedContactids = '0';
    
    private $existingPaths = array();

    private $db;

    public function __construct($cid, $oid, $maxsteps) {
        $this->maxsteps = $maxsteps;
        $this->cid = $cid;
        $this->oid = $oid;
        $this->db = TM_Db::get();
    }

    public function getAllPaths() {
        for ($s=1; $s <= $this->maxsteps; $s++) {
            $this->targetPathLength = $s;
            $this->_getLengthPaths(1, array(), array('oid'=>$this->cid), '0');
            if (is_array($this->paths[$s])) {
                foreach ($this->paths[$s] as $path) {
                    $pathSum = implode(',', array_keys($path));
                    $this->existingPaths[$pathSum] = 1;
                }
            }            
        }
    }

    private function _getLengthPaths($step=1, $path=NULL, $contact=array(), $parentid='0') {
        
        if ($step > 1) {
            $path[$contact['oid']] = $contact;
        }

        if (count($path)) {
            $existingPathContactIds = implode(',', array_keys($path));
        } else {
            $existingPathContactIds = '0';
        }

        $query = "
            select if(contact_id_a = %d, contact_id_b, contact_id_a) as oid, if(contact_id_a = %d, label_b_a, label_a_b) as typedetail, if(contact_id_a = %d, contact_id_a, contact_id_b) as cid
            from {civicrm_relationship} r inner join {civicrm_relationship_type} rt on rt.id = r.relationship_type_id
            where (r.contact_id_a = %d and r.contact_id_b not in ($existingPathContactIds)) or (r.contact_id_b = %d and contact_id_a not in ($existingPathContactIds))
        ";

        $children = $this->db->get_rows($query, $contact['oid'], $contact['oid'], $contact['oid'], $contact['oid'], $contact['oid']);
        
        if (is_array($children)) {
            foreach ($children as $child) {
                if ($child['oid'] == $this->oid) {
                    $query = "
                        select if(contact_id_a = %d, contact_id_b, contact_id_a) as oid, if(contact_id_a = %d, label_b_a, label_a_b) as typedetail, if(contact_id_a = %d, contact_id_a, contact_id_b) as cid
                        from {civicrm_relationship} r inner join {civicrm_relationship_type} rt on rt.id = r.relationship_type_id
                        where (r.contact_id_a = %d and r.contact_id_b = %d) or (r.contact_id_b = %d and r.contact_id_a = %d)
                    ";
                    $lastRow = $this->db->get_row($query, $contact['oid'], $contact['oid'], $contact['oid'], $contact['oid'], $this->oid, $contact['oid'], $this->oid);
                    $path[$this->oid] = $lastRow;

                    /* now we have the path; add it if we haven't added it already (point is we don't know the path until it's done,
                     * so we can't know whether we have it already or not until we get to the end of it.
                     */
                    $pathSum = implode( ',', array_keys( $path ));
                    if (! isset( $this->existingPaths[$pathSum] ) ) {
                        $this->paths[$this->targetPathLength][] = $path;
                    }

                } else {
                    if ($step < $this->targetPathLength) {
                        $this->_getLengthPaths($step+1, $path, $child, $contact['oid']);
                    }
                }
            }
        }
    }
}
