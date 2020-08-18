<?php

namespace Drupal\crowdsourcing\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;

/**
 * Configure 'crowdsourcing' settings for this site.
 */
class RadioForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'idea_radio_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $option_selected = $this->getSelectedOption();
    $form['negative_thoughts_status'] = array(
      '#type' => 'radios',
      '#title' => $this
        ->t(''),
      '#default_value' => $option_selected,
      '#options' => array(
        'totally_true' => $this
          ->t('Totally_true'),
        'true' => $this
          ->t('True'),
        'somewhat_true' => $this
          ->t('Somewhat True'),
        'not_quite_true' => $this
          ->t('Not quite true'),
        'not_true' => $this
          ->t('Not true'),
        'not_true_at_all' => $this
          ->t('Not true at all'),
      ),
      '#ajax' => [
        'callback' => [$this, '_submit_callback'],
        'effect' => 'fade',
        'event' => 'change',
      ],
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }

  public function _submit_callback(array $form, FormStateInterface $form_state){
    $negativeThoughtsStatus = $form_state->getValue('negative_thoughts_status');
    $userID = \Drupal::currentUser()->id();
    $ip_address = \Drupal::request()->getClientIp();
    $connection = Database::getConnection();
    $transaction = $connection->startTransaction();
    $entity_id = $this->getEntityId();
    if($userID != 0){
      $query = $connection->merge('node_radio_field')
        ->key('uid', $userID)
        ->fields([
            'entity_id' => $entity_id,
            'entity_type' => 'idea',
            'uid' => $userID,
            'ip_address' => '',
            'field_radio_value' => $negativeThoughtsStatus,
            'created' => time(),
        ]);
    }else{
      $query = $connection->merge('node_radio_field')
        ->key('ip_address', $ip_address)
        ->fields([
            'entity_id' => $entity_id,
            'entity_type' => 'idea',
            'uid' => 0,
            'ip_address' => $ip_address,
            'field_radio_value' => $negativeThoughtsStatus,
            'created' => time(),
        ]);
    }
    $query->execute();
    $ajax_response = new AjaxResponse();
    return $ajax_response;
  }

  public function getSelectedOption(){
    $connection = Database::getConnection();
    $entity_id = $this->getEntityId();
    $userID = \Drupal::currentUser()->id();
    $ip_address = \Drupal::request()->getClientIp();
    $options = array();
    $value = '';
    if($userID != 0){
      $result = $connection->query('SELECT * FROM node_radio_field WHERE uid = :uid and entity_id = :nid', array(':uid' => $userID, ':nid' => $entity_id), $options);
    }else{
      $result = $connection->query('SELECT * FROM node_radio_field WHERE ip_address = :ip_address and entity_id = :nid', array(':ip_address' => $ip_address, ':nid' => $entity_id), $options);
    }
    foreach($result as $item) {
      $value = $item->field_radio_value;
    }
    if(!empty($value)){
      return $value;
    }
  }

  public function getEntityId(){
    $current_path = \Drupal::service('path.current')->getPath();
    $pathArgs = explode('/', $current_path);
    return $pathArgs[2];
  }
}
