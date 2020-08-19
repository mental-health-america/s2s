<?php

namespace Drupal\crowdsourcing\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Ajax\ReplaceCommand;
use \Drupal\Core\Ajax\AfterCommand;

/**
 * Class ReportController.
 *
 * @package Drupal\crowdsourcing\Controller
 */
class ReportController extends ControllerBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an LinkClickCountController object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface
   *   The renderer.
   */
  public function __construct(RequestStack $request, EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, RendererInterface $renderer) {
    $this->requestStack = $request;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $account;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * Like or Dislike handler.
   *
   * @param string $clicked
   *   Status of the click link.
   * @param string $data
   *   Data passed from the formatter.
   *
   * @return AjaxResponse|string
   *   Response count for the like/dislike.
   */
  public function handler($clicked, $data) {
    global $base_url;
    $return = '';
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#report_abuse_status', ''));
    // Decode the url data
    $decode_data = json_decode(base64_decode($data));
     
    // Load the entity content.
    $entity_data = $this->entityTypeManager
      ->getStorage($decode_data->entity_type)
      ->load($decode_data->entity_id);
    $field_name = $decode_data->field_name;
    
    // Get the users who already clicked on this particular content.
    $users = json_decode($entity_data->$field_name->clicked_by);
    $users_ip_address = json_decode($entity_data->$field_name->ip_address);
    if ($users == NULL) {
      $users = new \stdClass();
      $users->default = 'defaults';
    }
    $ip_address = \Drupal::request()->getClientIp();
    $user = $this->currentUser->id();
    $like_img = $base_url .'/'. drupal_get_path('module', 'crowdsourcing') . '/assests/images/abused.png';
    // If user is ananomous, ask him to register/login.
    if ($user == 0) {
        $existing_ip_address = (array) $users_ip_address;
        $already_clicked_ip = in_array($ip_address, array_values($existing_ip_address));
        if ($clicked == 'report') {
          if(!$already_clicked_ip){
            $entity_data->$field_name->report++;
            $users_ip_address = $ip_address;
            $response->addCommand(
              new ReplaceCommand('.reported-'.$decode_data->entity_id, '<img src="'.$like_img.'" alt="Already reported" typeof="Image" class="img-responsive" />')
            );
            $entity_data->$field_name->ip_address = json_encode($users_ip_address);
            $entity_data->save();
            return $response;
          }
        }
    }
    $already_clicked = in_array($user, array_keys((array) $users));
    $array_users = json_decode(json_encode($users), True);
    $already_clicked_status = $array_users[$user];
    if ($clicked == 'report') {
      if (($already_clicked && $clicked != $already_clicked_status) || (!$already_clicked && !isset($already_clicked_status))) {
        if($already_clicked && $clicked != $already_clicked_status){
          $entity_data->$field_name->unreport--;
        }
        $entity_data->$field_name->report++;
        $users->$user = 'report';
      }
      else {
        return $this->like_dislike_status($response);
      }
      $response->addCommand(
        new ReplaceCommand('.reported-'.$decode_data->entity_id, '<img src="'.$like_img.'" alt="Already reported" typeof="Image" class="img-responsive" />')
      );
    }
    elseif ($clicked == 'unreport') {
      if (($already_clicked && $clicked != $already_clicked_status) || (!$already_clicked && !isset($already_clicked_status))) {
        if($already_clicked && $clicked != $already_clicked_status){
          $entity_data->$field_name->report--;
        }
        $entity_data->$field_name->unreport++;
        $users->$user = "unreport";
      }
      else {
        return $this->like_dislike_status($response);
      }
      $response->addCommand(
        new ReplaceCommand('.reported-'.$decode_data->entity_id, '<img src="'.$like_img.'" alt="Already reported" typeof="Image" class="img-responsive" />')
      );
    }
    $entity_data->$field_name->clicked_by = json_encode($users);
    $entity_data->save();
    return $response;
  }

  /**
   * Respond with the status, if user already liked/disliked.
   *
   * @param AjaxResponse $response
   * @return AjaxResponse
   */
  protected function like_dislike_status(AjaxResponse $response) {
    return $response->addCommand(
      new HtmlCommand('#report_abuse_status', 'Already liked/disliked..!')
    );
  }

}
