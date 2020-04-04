<?php

namespace Drupal\webform_score\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

interface WebformScoreValidatorManagerInterface extends PluginManagerInterface {

  /**
   * Get all available webform score validator plugin instances.
   *
   * @return \Drupal\webform\WebformScoreValidatorInterface[]
   *   An array of all available webform score validator plugin instances.
   */
  public function getInstances();

  /**
   * Find plugin instances based on a definition value.
   * @param string $search_value
   *   The value to search for
   * @param string $filter_by
   *   The definition meta-data to search on.
   * @return \Drupal\webform\WebformScoreValidatorInterface
   *   An instance of a ScoreValidator or boolean FALSE.
   *
   */
  public function findInstance($search_value, $filter_by = 'id');

  /**
   * Gall all available webform score validators for a particular element type.
   *
   * @param string $element_type
   *   Element type string.
   *
   * @return \Drupal\webform\WebformScoreValidatorInterface[]
   *  An array of all available webform score validator plugin instances for
   *  element type.
   */
  public function getElementInstances($element_type);
}