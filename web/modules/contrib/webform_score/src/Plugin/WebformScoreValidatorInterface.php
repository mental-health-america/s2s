<?php

namespace Drupal\webform_score\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Webform score validator plugins.
 */
interface WebformScoreValidatorInterface extends PluginInspectionInterface {


  // Add get/set methods for your plugin type here.

  /**
   * Gets the plugin label of the plugin instance.
   *
   * @return string
   *   The plugin_label of the plugin instance.
   */
  public function getPluginLabel();

  /**
   * Create FAPI elements for configuration of this validation method.
   *
   * @param string $element_type
   *   The element type configuring
   * @param array $config
   *   Validation configuration
   * @return mixed
   */
  public function buildConfigForm($element_type, array $config = []);

  /**
   * Validate the submitted data
   *
   * @param mixed $validate_config
   *   Config of validation settings for this validation
   * @param mixed $submitted_value
   *   Data submitted by user
   *
   * @return boolean
   *   Boolean true of false if validation passes.
   */
  public function validate($validate_config, $submitted_value);

  /**
   * Retireve the score to be recorded based on the validation of submitted data.
   *
   * @param array $config
   *   Array of config settings for this instance of validator.
   * @param mixed $submitted_value
   *   User submitted data to validate against.
   *
   * @return integer
   *    Returns the score to be recorded.
   */
  public function getScore(array $config, $submitted_value);

  /**
   * Returns an array of data we should save to score_validation_data config.
   *
   * @param string $element_type
   *   Webform element type
   * @param array $values
   *   Submitted form values
   *
   * @return mixed
   *   Array of config data.
   */
  public function submitConfigForm($element_type, array $values = []);

}
