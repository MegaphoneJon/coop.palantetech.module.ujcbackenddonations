<?php

require_once 'ujcbackenddonations.civix.php';

/**
 * Implementation of hook_civicrm_buildForm
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function ujcbackenddonations_civicrm_buildForm( $formName, &$form) {
  if($formName == 'CRM_Contribute_Form_Contribution') {
    if($form->_action == CRM_Core_Action::ADD){
      //Additional Details should default to expanded.
      CRM_Core_Resources::singleton()->addScript("
        CRM.$(function ($) {
          'use strict';
          $('#AdditionalDetail').click();
        });//end cj
      ");


      //make Contribution Page ID required.
      //but this hook gets called for all the subsections of this page, so only call it when applicable.
      if(array_key_exists('contribution_page_id',$form->_elementIndex)) {
        $form->addRule('contribution_page_id', ts('Contribution Page name is required.'), 'required');
      }
    }
  }
}

/**
 * Implementation of hook_civicrm_pre
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_post
 */
function ujcbackenddonations_civicrm_pre( $op, $objectName, $id, &$params ) {
  //When creating a contribution that has no source, set the contribution page name as the source.
  if( $op == 'create' && $objectName == 'Contribution' && !($params['source'])) {
    $APIparams = array(
      'version' => 3,
      'sequential' => 1,
      'id' => $params['contribution_page_id'],
      'return' => 'title',
    );
    $result = civicrm_api('ContributionPage', 'getvalue', $APIparams);

    $source = "Offline Contribution: " . $result;
    $params['source'] = $source;
  }
}

//On credit card contributions, the source is automatically populated with a string like "Submit Credit Card Payment by: Nealon, Gretchen”
//So we search for that and replace it on civicrm_post.  This may make civicrm_pre redundant, not 100% sure.
function ujcbackenddonations_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
  if ($op == 'create' && $objectName == 'Contribution') {
    if(substr($objectRef->source,0,30) == 'Submit Credit Card Payment by:') {
      $APIparams = array(
        'version' => 3,
        'sequential' => 1,
        'id' => $objectRef->contribution_page_id,
        'return' => 'title',
      );
      $result = civicrm_api('ContributionPage', 'getvalue', $APIparams);

      $source = "Offline Contribution: " . $result;
      unset($APIparams);

      $APIparams = array(
        'version' => 3,
        'sequential' => 1,
        'id' => $objectId,
        'source' => $source,
      );
      $result = civicrm_api('Contribution', 'create', $APIparams);
    }
  }
}
