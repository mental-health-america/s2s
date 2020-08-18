<?php

namespace Drupal\crowdsourcing\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'report_abuse' field type.
 *
 * @FieldType(
 *   id = "report_abuse",
 *   label = @Translation("Report Abuse"),
 *   description = @Translation("Report Abuse"),
 *   default_widget = "report_abuse_widget",
 *   default_formatter = "report_abuse_formatter"
 * )
 */
class ReportField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = array(
      'columns' => array(
        'report' => array(
          'type' => 'varchar',
          'length' => 256,
          'not null' => FALSE,
        ),
        'unreport' => array(
          'type' => 'varchar',
          'length' => 256,
          'not null' => FALSE,
        ),
        'clicked_by' => array(
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
        ),
        'ip_address' => array(
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
        ),
      ),
    );

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['report'] = DataDefinition::create('string')
      ->setLabel(t('report label'));
    $properties['unreport'] = DataDefinition::create('string')
      ->setLabel(t('unreport label'));
    $properties['clicked_by'] = DataDefinition::create('string')
      ->setLabel(t('clicked by label'));
      $properties['ip_address'] = DataDefinition::create('string')
      ->setLabel(t('ip address label'));
    return $properties;
  }

}
