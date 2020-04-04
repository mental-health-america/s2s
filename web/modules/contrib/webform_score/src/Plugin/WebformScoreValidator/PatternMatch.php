<?php

namespace Drupal\webform_score\Plugin\WebformScoreValidator;

use Drupal\webform_score\Annotation\WebformScoreValidator;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform_score\Plugin\WebformScoreValidatorBase;

/**
 * @WebformScoreValidator(
 *  id = "element_pattern_match",
 *  label = @Translation("Value Matches Pattern"),
 *  element_types = "textfield"
 * )
 */
class PatternMatch extends WebformScoreValidatorBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigForm($element_type, array $config = []) {
    $form['pattern_match'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value matches pattern'),
      '#description' => $this->t('A <a href=":href">regular expression</a> that the element\'s value is checked against.', [':href' => 'http://www.w3schools.com/js/js_regexp.asp']),
      '#default_value' => isset($config['pattern_match']) ? $config['pattern_match'] : [] ,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigForm($webform_element_type, array $values = []) {
    return [
      'pattern_match' => isset($values['pattern_match']) ? $values['pattern_match'] : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate($validate_config, $submitted_value) {
    $validation = FALSE;
    // Check if regular expression matches.
    if (isset($validate_config['pattern_match']) && preg_match($validate_config['pattern_match'], $submitted_value)) {
      $validation = TRUE;
    }

    return $validation;
  }

}
