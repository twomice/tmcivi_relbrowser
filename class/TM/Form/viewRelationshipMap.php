<?php
/*
 * $Id: viewRelationshipMap.php 198 2010-08-14 01:38:16Z as $
 */

function viewRelationshipMap_preProcess( $form ) {
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
    $url = CRM_Utils_System::url('civicrm/tm/form', "tmref=viewRelationshipMap&cid={$cid}&maxsteps={$form->properties['maxsteps']}");
    $session->pushUserContext($url);

    return;
}

function viewRelationshipMap_setDefaultValues( $form ) {
    $defaults = array ('maxsteps' => $form->properties['maxsteps']);
    return $defaults;
}

function viewRelationshipMap_buildQuickForm ( $form ) {

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

    drupal_add_css(drupal_get_path('module', 'tmcivi_relbrowser') .'/resource/css/common.css');
    
    return;
}

function viewRelationshipMap_validate( $form ) {
    return;
}

function viewRelationshipMap_postProcess( $form ) {
    return;
}

