<?php

namespace Drupal\webform_score\Plugin\WebformScoreValidator;

use Drupal\webform_score\Annotation\WebformScoreValidator;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform_score\Plugin\WebformScoreValidatorBase;

/**
 * @WebformScoreValidator(
 *  id = "element_not_empty",
 *  label = @Translation("Element Not Empty"),
 *  element_types = "all"
 * )
 */
class NotEmpty extends WebformScoreValidatorBase {

  /**
  * {@inheritdoc}
  */
  public function buildConfigForm($element_type, array $config = []) {
    $form['not_empty_description'] = [
      '#type' => 'item',
      '#description' => $this->t('User receive score value if they do not leave this element empty.'),
    ];
    return $form;
  }

  public function validate($validation_settings, $submitted_value) {
    // Just return check if submitted value is equal to an empty value.
    return !empty($submitted_value);
  }

}
