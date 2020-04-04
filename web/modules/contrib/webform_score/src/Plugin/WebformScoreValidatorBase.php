<?php

namespace Drupal\webform_score\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Base class for Webform score validator plugins.
 */
abstract class WebformScoreValidatorBase extends PluginBase implements WebformScoreValidatorInterface {
  use StringTranslationTrait;

  /**
   * WebformScoreValidatorBase constructor.
   * @param array  $configuration
   * @param string $plugin_id
   * @param mixed  $plugin_definition
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getScore(array $config, $submitted_value) {
    $score = 0;
    if ($this->validate($config['score_validation_data'], $submitted_value)) {
      $score = $config['score_amount'];
    }
    return $score;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($validate_config, $submitted_value) {
    return $validate_config == $submitted_value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigForm($element_type, array $config = []) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigForm($element_type, array $values = []) {
    return [];
  }
}
