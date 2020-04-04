<?php

namespace Drupal\webform_score\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Webform score validator plugin manager.
 */
class WebformScoreValidatorManager extends DefaultPluginManager implements WebformScoreValidatorManagerInterface  {

  /**
   * List of already instantiated webform  score validator plugins.
   *
   * @var array
   */
  protected $instances = [];

  protected $element_instances = [];


  /**
   * Constructor for WebformScoreValidatorManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/WebformScoreValidator', $namespaces, $module_handler, 'Drupal\webform_score\Plugin\WebformScoreValidatorInterface', 'Drupal\webform_score\Annotation\WebformScoreValidator');

    $this->alterInfo('webform_score_validator_info');
    $this->setCacheBackend($cache_backend, 'webform_score_validator_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'webform_score_validator';
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    // If configuration is empty create a single reusable instance for each
    // Webform Score Validator plugin.
    if (empty($configuration)) {
      if (!isset($this->instances[$plugin_id])) {
        $this->instances[$plugin_id] = parent::createInstance($plugin_id, $configuration);
      }
      return $this->instances[$plugin_id];
    }
    else {
      return parent::createInstance($plugin_id, $configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInstances() {
    $plugin_definitions = $this->getDefinitions();
    $plugin_definitions = $this->getSortedDefinitions($plugin_definitions);

    // If all the plugin definitions are initialize returned the cached
    // instances.
    if (count($plugin_definitions) == count($this->instances)) {
      return $this->instances;
    }

    // Initialize and return all plugin instances.
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $this->createInstance($plugin_id);
    }

    return $this->instances;
  }

  /**
   * {@inheritdoc}
   */
  public function findInstance($search_value, $filter_by = 'id') {
    $instance = false;
    $plugin_definitions = $this->getDefinitions();
    // Filter the definitions.
    if ($plugin_definitions = $this->getFilteredDefinitions($plugin_definitions, $filter_by, $search_value)) {
        // Use the first one.
        $plugin_definition = array_shift($plugin_definitions);
        // Create instance
        $instance = $this->createInstance($plugin_definition['id']);
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementInstances($element_type) {
    $plugin_definitions = $this->getDefinitions();
    $plugin_definitions = $this->getFilteredDefinitions($plugin_definitions, 'element_types', $element_type);
    $plugin_definitions = $this->getSortedDefinitions($plugin_definitions);

    // If all the plugin definitions for this type are initialized return the
    // cached instances.
    if (isset($this->element_instances[$element_type]) && count($plugin_definitions) == count($this->element_instances[$element_type])) {
      return $this->element_instances[$element_type];
    }

    // Initialize and return all plugin instances for this type.
    foreach ($plugin_definitions as $plugin) {
      $this->element_instances[$element_type][$plugin['id']] = parent::createInstance($plugin['id'],[]);
    }

    return $this->element_instances[$element_type];

  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL, $sort_by = 'label') {
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();

    switch ($sort_by) {

      default:
        uasort($definitions, function ($a, $b) use ($sort_by) {
          return strnatcasecmp($a[$sort_by], $b[$sort_by]);
        });
        break;
    }

    return $definitions;
  }

  public function getFilteredDefinitions(array $definitions = NULL, $filter_by = 'element_types', $filter_value = NULL) {
    $definitions = isset($definitions) ? $definitions : $this->getDefinitions();
    $filtered_definitions = [];
    // Switch filtering method based on definition field.
    switch ($filter_by) {
      case 'element_types':
        // Loop through all definitions.
        foreach ($definitions as $key => $definition) {
          // If labeled as for all types, include it.
          if ($definition['element_types'] == 'all') {
            $filtered_definitions[$key] = $definition;
          } else {
            // Get array of supported element types
            $definition_accepted_types = explode(',', $definition['element_types']);
            // If passed type is in array then add definition.
            if (in_array($filter_value, $definition_accepted_types)) {
              $filtered_definitions[$key] = $definition;
            }
          }
        }
        break;
      default:
        foreach ($definitions as $key => $definition) {
          if (isset($definition[$filter_by]) && $definition[$filter_by] = $filter_value) {
            $filtered_definitions[$key] = $definition;
          }
        }
    }

    return $filtered_definitions;

  }
}
