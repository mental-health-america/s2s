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
 * Class LikeDislikeController.
 *
 * @package Drupal\crowdsourcing\Controller
 */
class LikeDislikeController extends ControllerBase {

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
    $response->addCommand(new HtmlCommand('#like_dislike_status', ''));
    // Decode the url data
    $decode_data = json_decode(base64_decode($data));

    // Load the entity content.
    $entity_data = $this->entityTypeManager
      ->getStorage($decode_data->entity_type)
      ->load($decode_data->entity_id);

    $field_name = $decode_data->field_name;
    $url_encoded_data = $decode_data;
    $url_encoded_data->likes = $entity_data->$field_name->likes;
    $url_encoded_data->dislikes =  $entity_data->$field_name->dislikes;

   $url_encoded_data = base64_encode(json_encode($url_encoded_data));
    if($clicked == 'like'){
      $dislike_url = Url::fromRoute(
        'crowdsourcing.manager', ['clicked' => 'dislike', 'data' => $url_encoded_data]
      )->toString();
    }else{
      $like_url = Url::fromRoute(
        'crowdsourcing.manager', ['clicked' => 'like', 'data' => $url_encoded_data]
      )->toString();
    }
    // Get the users who already clicked on this particular content.
    $users = json_decode($entity_data->$field_name->clicked_by);
    $users_ip_address = [];
    $users_ip_address = json_decode($entity_data->$field_name->ip_address);
    if ($users == NULL) {
      $users = new \stdClass();
      $users->default = 'defaults';
    }
    $ip_address = \Drupal::request()->getClientIp();
    $user = $this->currentUser->id();
    $like_img = $base_url .'/'. drupal_get_path('module', 'crowdsourcing') . '/assests/images/liked.png';
    $no_like_img = $base_url .'/'. drupal_get_path('module', 'crowdsourcing') . '/assests/images/no_like.png';
     // Update content, based on like/dislike.
     $already_clicked = in_array($user, array_keys((array) $users));
     $array_users = json_decode(json_encode($users), True);
     $already_clicked_status = $array_users[$user];
    // If user is ananomous, ask him to register/login.
    if ($user == 0) {
        $existing_ip_address = [];
        $existing_ip_address = (array) $users_ip_address;
        $already_clicked_ip = in_array($ip_address, array_values($existing_ip_address));
        if ($clicked == 'like') {
          if(!$already_clicked_ip){
            $entity_data->$field_name->likes++;
            $existing_ip_address = json_decode(json_encode($existing_ip_address), True);
            array_push($existing_ip_address, $ip_address);
            \Drupal::logger('push')->warning('<pre><code>' . print_r($existing_ip_address, TRUE) . '</code></pre>');
            $users_ip_address = $existing_ip_address;
            $response->addCommand(
              new HtmlCommand('.like_count-'.$decode_data->entity_id, "{$entity_data->$field_name->likes}")
            );
            $response->addCommand(
              new ReplaceCommand('.liked-'.$decode_data->entity_id, '<a class="use-ajax liked-'.$decode_data->entity_id.'" href='.$dislike_url.'><img src="'.$like_img.'" alt="Already Liked" typeof="Image" class="img-responsive" /></a>')
            );
          }
        }elseif ($clicked == 'dislike') {
          if (($already_clicked && $clicked != $already_clicked_status) || (!$already_clicked && !isset($already_clicked_status))) {
            if($already_clicked && $clicked != $already_clicked_status){
              $entity_data->$field_name->likes--;
            }
            if (($ipkey = array_search($ip_address, $existing_ip_address)) !== false) {
              \Drupal::logger('ipkey')->warning('<pre><code>' . print_r($ipkey, TRUE) . '</code></pre>');
              \Drupal::logger('before_unset')->warning('<pre><code>' . print_r($existing_ip_address, TRUE) . '</code></pre>');
              $arr_ip = json_decode(json_encode($existing_ip_address), True);
              unset($arr_ip[$ipkey]);
              \Drupal::logger('after_unset')->warning('<pre><code>' . print_r($arr_ip, TRUE) . '</code></pre>');

              $users_ip_address = array_values($arr_ip);
              \Drupal::logger('users_ip_address')->warning('<pre><code>' . print_r($users_ip_address, TRUE) . '</code></pre>');
            }
          }
          else {
            return $this->like_dislike_status($response);
          }
          $response->addCommand(
            new HtmlCommand('.like_count-'.$decode_data->entity_id, "{$entity_data->$field_name->likes}")
          );
          $response->addCommand(
            new ReplaceCommand('.liked-'.$decode_data->entity_id, '<a class="use-ajax liked-'.$decode_data->entity_id.'" href='.$like_url.'><img src="'.$no_like_img.'" alt="No Like" typeof="Image" class="img-responsive" /></a>')
          );
        }
        $entity_data->$field_name->ip_address = json_encode((object)$users_ip_address);
        $entity_data->save();
        return $response;
    }else{
        if ($clicked == 'like') {
            if (($already_clicked && $clicked != $already_clicked_status) || (!$already_clicked && !isset($already_clicked_status))) {
                if ($already_clicked && $clicked != $already_clicked_status) {
                    $entity_data->$field_name->dislikes--;
                }
                $entity_data->$field_name->likes++;
                $users->$user = 'like';
            //$users_ip_address = $ip_address;
            } else {
                return $this->like_dislike_status($response);
            }
            $response->addCommand(
                new HtmlCommand('.like_count-'.$decode_data->entity_id, "{$entity_data->$field_name->likes}")
            );
            $response->addCommand(
                new ReplaceCommand('.liked-'.$decode_data->entity_id, '<a class="use-ajax liked-'.$decode_data->entity_id.'" href='.$dislike_url.'><img src="'.$like_img.'" alt="Already Liked" typeof="Image" class="img-responsive" /></a>')
            );
        } elseif ($clicked == 'dislike') {
            if (($already_clicked && $clicked != $already_clicked_status) || (!$already_clicked && !isset($already_clicked_status))) {
                if ($already_clicked && $clicked != $already_clicked_status) {
                    $entity_data->$field_name->likes--;
                }
                //$entity_data->$field_name->dislikes++;
                unset($users->$user);
            //$users->$user = "dislike";
        //$users_ip_address = $ip_address;
            } else {
                return $this->like_dislike_status($response);
            }
            $response->addCommand(
                new HtmlCommand('.like_count-'.$decode_data->entity_id, "{$entity_data->$field_name->likes}")
            );
            $response->addCommand(
                new ReplaceCommand('.liked-'.$decode_data->entity_id, '<a class="use-ajax liked-'.$decode_data->entity_id.'" href='.$like_url.'><img src="'.$no_like_img.'" alt="No Like" typeof="Image" class="img-responsive" /></a>')
            );
        }
        $entity_data->$field_name->clicked_by = json_encode($users);
        $entity_data->save();
        return $response;
    }
  }

  /**
   * Get the login and Registration options for ananomous user.
   *
   * @return mixed
   */
  protected function like_dislike_login_register() {
    $options = array(
      'attributes' => array(
        'class' => array(
          'use-ajax',
          'login-popup-form',
        ),
        'data-dialog-type' => 'modal',
      ),
    );
    $user_register = Url::fromRoute('user.register')->setOptions($options);
    $user_login = Url::fromRoute('user.login')->setOptions($options);
    $register = Link::fromTextAndUrl(t('Register'), $user_register)->toString();
    $login = Link::fromTextAndUrl(t('Log in'), $user_login)->toString();
    $content = array(
      'content' => array(
        '#markup' => "Only logged-in users are allowed to like/dislike. Visit ".$register ." | " . $login,
      ),
    );
    return $this->renderer->render($content);
  }

  /**
   * Respond with the status, if user already liked/disliked.
   *
   * @param AjaxResponse $response
   * @return AjaxResponse
   */
  protected function like_dislike_status(AjaxResponse $response) {
    return $response->addCommand(
      new HtmlCommand('#like_dislike_status', 'Already liked/disliked..!')
    );
  }

}
