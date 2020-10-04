<?php

namespace Drupal\crowdsourcing\Plugin\Field\FieldFormatter;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use \Drupal\Core\Ajax\CssCommand;

/**
 * Plugin implementation of the 'report_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "report_abuse_formatter",
 *   label = @Translation("Report Abuse"),
 *   field_types = {
 *     "report_abuse"
 *   }
 * )
 */
class ReportFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

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

    foreach ($items as $delta => $item) {

      $initial_data['report'] = $items[$delta]->report;
      $initial_data['unreport'] = $items[$delta]->unreport;
      $initial_data['users_report'] = json_decode($items[$delta]->clicked_by);
      $initial_data['users_ip'] = json_decode($items[$delta]->ip_address);
    }
    $already_clicked = 'FALSE';
    $report_img = $base_url .'/'. drupal_get_path('module', 'crowdsourcing') . '/assests/images/want-abuse.png';
    if(!empty($initial_data) && (isset($initial_data['users_report']) || isset($initial_data['users_ip']))){
      $user = \Drupal::currentUser()->id();
      if($user == 0){
        $ip_address = \Drupal::request()->getClientIp();
        $existing_ip_address = (array) $initial_data['users_ip'];
        $already_clicked = in_array($ip_address, array_values($existing_ip_address));
        if($already_clicked){
          $already_clicked = 'TRUE';
        }
      }else{
        $already_clicked = in_array($user, array_keys((array) $initial_data['users_report']));
      }
      if($already_clicked){
        // $response = new AjaxResponse();
        //   return $response->addCommand(new CssCommand('.js-ajax-comments-id-'.$entity->id(), ['display' => 'none']));
        $report_img = $base_url .'/'. drupal_get_path('module', 'crowdsourcing') . '/assests/images/abused.png';
        $already_clicked = 'TRUE';
      }
    }
    \Drupal::logger('base_img')->warning('<pre><code>' . print_r($report_img, TRUE) . '</code></pre>');
    $data = base64_encode(json_encode($initial_data));

    $report_url = Url::fromRoute(
      'crowdsourcing.report', ['clicked' => 'report', 'data' => $data]
    )->toString();
    $unreport_url = Url::fromRoute(
      'crowdsourcing.report', ['clicked' => 'unreport', 'data' => $data]
    )->toString();

    // If user is ananomous, the append the destination back url.
    $user = $this->currentUser->id();
    $destination = '';

    $elements[] = [
      '#theme' => 'report_abuse',
      '#report' => $initial_data['report'],
      '#unreport' => $initial_data['unreport'],
      '#report_url' => $report_url . $destination,
      '#unreport_url' => $unreport_url . $destination,
      '#already_clicked' => $already_clicked,
      '#report_img' => $report_img,
      '#reported_class' => 'reported-'.$entity->id(),
      '#comment_id' => $entity->id()
    ];

    $elements['#attached']['library'][] = 'core/drupal.ajax';
    $elements['#attached']['library'][] = 'crowdsourcing/crowdsourcing';

    // Set the cache for the element.
    $elements['#cache']['max-age'] = 0;
    return $elements;
  }

}
