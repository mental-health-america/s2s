<?php

namespace Drupal\crowdsourcing\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'report_abuse_widget' widget.
 *
 * @FieldWidget(
 *   id = "report_abuse_widget",
 *   label = @Translation("Report abuse widget"),
 *   field_types = {
 *     "report_abuse"
 *   }
 * )
 */
class ReportWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];
    return $element;
  }

}
