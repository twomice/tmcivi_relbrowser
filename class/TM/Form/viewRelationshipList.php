<?php
/*
 * $Id: viewRelationshipList.php 349 2010-09-18 17:54:36Z as $
 */

function viewRelationshipList_preProcess( $form ) {
    $session = new CRM_Core_Session();

    require_once('CRM/Contact/BAO/Contact/Permission.php');
    if ( ! CRM_Contact_BAO_Contact_Permission::allow( $form->post['cid'], CRM_Core_Permission::VIEW ) )  {
        TM_Util::trigger_error('Unauthorized access', TM_ERROR_ERROR, $repeat = FALSE);
        $toUrl   = $session->popUserContext();
        CRM_Utils_System::redirect($toUrl);
        return;
    }

    $steps = (int)$form->post['maxsteps'];
    $form->properties['maxsteps'] = ( $steps ? $steps : 2);

    // push this page into user context
    $cid = (int)$form->post['cid'];
    $url = CRM_Utils_System::url('civicrm/tm/form', "tmref=viewRelationshipList&cid={$cid}&reset=1");
    $session->pushUserContext($url);

    return;
}

function viewRelationshipList_setDefaultValues( $form ) {
    $defaults = array ('maxsteps' => $form->properties['maxsteps']);
    return $defaults;
}

function viewRelationshipList_buildQuickForm ( $form ) {

    // INCOMING VARIABLES:

    //* $form->post['maxsteps'] (optional, default = 1)
    //* $form->post['cid']

    $steps = $form->properties['maxsteps'];

    $strContactids = (int)$form->post['cid'];
    $strDoneContactids = 0;

    $db = TM_Db::get();

    for ($i = 1; $i <= $steps; $i++) {
        if (!$strContactids) continue;
        $query = "
            select if(contact_id_a in ($strContactids), contact_id_b, contact_id_a)
            from {civicrm_relationship}
            where (
                (
                    contact_id_a in ($strContactids) and contact_id_b not in ($strDoneContactids)
                ) or (
                    contact_id_b in ($strContactids) and contact_id_a not in ($strDoneContactids)
                )
            )";

        $arContactids[$i] = $db->get_column($query);
        
        if (!$arContactids[$i]) continue;

        $strDoneContactids .= ",$strContactids";
        $nextContactidsToCheck = array();
        foreach ($arContactids[$i] as $contactid) {
            if (CRM_Contact_BAO_Contact_Permission::allow( $contactid, CRM_Core_Permission::VIEW )) {
                $nextContactidsToCheck[] = $contactid;
            }
        }
        $strContactids = implode(',', $nextContactidsToCheck);
    }

    $list = array();
    foreach ($arContactids as $step => $contactids) {
        if (!$contactids) continue;
        $query = "
            select c.display_name, cn.name as country, c.id as cid, '$step' as steps
            from {civicrm_contact} c
            left join {civicrm_address} a on a.contact_id = c.id and a.is_primary = 1
            left join {civicrm_country} cn on cn.id = a.country_id
            where c.id in (". implode(',', $contactids) .")
        ";
        $rows = $db->get_rows($query);
        foreach ($rows as $key => $row) {
            $rows[$key]['access'] = CRM_Contact_BAO_Contact_Permission::allow( $row['cid'], CRM_Core_Permission::VIEW );
        }
        $list = array_merge($list, $rows);
    }

    $form->assign('INC_list', $list);

    // Now set up form elements
    for ($i = 1; $i <= 20; $i++) {
        $stepOptions[$i] = $i;
    }
    $form->add('select', 'maxsteps', ts('Max. Steps'), $stepOptions);

    $buttonParams = array(
        'type' => 'submit',
        'name' => 'Refresh',
        'subName' => '',
        'isDefault' => TRUE,
    );
    $form->addButton($buttonParams);

    return;
}

function viewRelationshipList_validate( $form ) {
    return;
}

function viewRelationshipList_postProcess( $form ) {
    return;
}

