<?php

namespace Drupal\webform_score\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Webform score validator item annotation object.
 *
 * @see \Drupal\webform_score\Plugin\WebformScoreValidatorManager
 * @see plugin_api
 *
 * @Annotation
 */
class WebformScoreValidator extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The Webform element types supported. Separated by commas.
   *
   * @var string
   */
  public $element_types;
}
