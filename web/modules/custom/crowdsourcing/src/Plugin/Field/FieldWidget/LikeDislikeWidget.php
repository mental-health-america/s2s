<?php

namespace Drupal\crowdsourcing\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'like_dislike_widget' widget.
 *
 * @FieldWidget(
 *   id = "like_dislike_widget",
 *   label = @Translation("Like dislike widget"),
 *   field_types = {
 *     "like_dislike"
 *   }
 * )
 */
class LikeDislikeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = [];
    return $element;
  }

}
