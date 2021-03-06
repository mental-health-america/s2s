<?php

namespace Drupal\crowdsourcing\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation;

/**
 * Plugin implementation of the 'like_dislike_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "like_dislike_formatter",
 *   label = @Translation("Like Dislike"),
 *   field_types = {
 *     "like_dislike"
 *   }
 * )
 */
class LikeDislikeFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, RequestStack $request) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->requestStack = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      // Implement default settings.
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array(
      // Implement settings form.
    ) + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    global $base_url;
    $entity = $items->getEntity();
    $elements = [];

    // Data to be passed in the url.
    $initial_data = [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'field_name' => $items->getFieldDefinition()->getName(),
    ];
    //\Drupal::logger('intial_data')->warning('<pre><code>' . print_r($initial_data, TRUE) . '</code></pre>');
    foreach ($items as $delta => $item) {

      $initial_data['likes'] = $items[$delta]->likes;
      $initial_data['dislikes'] = $items[$delta]->dislikes;
      $initial_data['users_like'] = json_decode($items[$delta]->clicked_by);
      $initial_data['users_ip'] = json_decode($items[$delta]->ip_address);
    }
//    \Drupal::logger('for_intial_data')->warning('<pre><code>' . print_r($initial_data, TRUE) . '</code></pre>');
    $already_clicked = 'FALSE';
    $like_img = $base_url .'/'. drupal_get_path('module', 'crowdsourcing') . '/assests/images/no_like.png';
    if(!empty($initial_data) && (isset($initial_data['users_like']) || isset($initial_data['users_ip']))){
      $user = \Drupal::currentUser()->id();
      if($user == 0){
        $ip_address = \Drupal::request()->getClientIp();
        $existing_ip_address = (array) $initial_data['users_ip'];
        $already_clicked = in_array($ip_address, array_values($existing_ip_address));
        if($already_clicked){
          $already_clicked = 'TRUE';
        }
      }else{
        $already_clicked = in_array($user, array_keys((array) $initial_data['users_like']));
      }
      if($already_clicked){
        $like_img = $base_url .'/'. drupal_get_path('module', 'crowdsourcing') . '/assests/images/liked.png';
        $already_clicked = 'TRUE';
      }
    }
    $data = base64_encode(json_encode($initial_data));

    $like_url = Url::fromRoute(
      'crowdsourcing.manager', ['clicked' => 'like', 'data' => $data]
    )->toString();
    $dislike_url = Url::fromRoute(
      'crowdsourcing.manager', ['clicked' => 'dislike', 'data' => $data]
    )->toString();

    // If user is ananomous, the append the destination back url.
    $user = $this->currentUser->id();
    $destination = '';

    $elements[] = [
      '#theme' => 'like_dislike',
      '#likes' => $initial_data['likes'],
      '#dislikes' => $initial_data['dislikes'],
      '#like_url' => $like_url . $destination,
      '#dislike_url' => $dislike_url . $destination,
      '#already_clicked' => $already_clicked,
      '#like_img' => $like_img,
      '#liked_class' => 'liked-'.$entity->id(),
      '#like_count_class' => 'like_count-'.$entity->id()
    ];

    $elements['#attached']['library'][] = 'core/drupal.ajax';
    $elements['#attached']['library'][] = 'crowdsourcing/crowdsourcing';

    // Set the cache for the element.
    $elements['#cache']['max-age'] = 0;
    return $elements;
  }
}
