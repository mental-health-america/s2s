<?php



namespace Drupal\screening_blocks\Plugin\Block;



use Drupal\Core\Url;

use Drupal\Core\Link;

use Drupal\Core\Access\AccessResult;

use Drupal\Core\Block\BlockBase;

use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Session\AccountInterface;



/**

 * Provides a 'Webform' Block.

 *

 * @Block(

 *   id = "screening_webform_block",

 *   admin_label = @Translation("Screening Tools Webform Block"),

 *   category = @Translation("Screening Tools"),

 *   visibility = 0,

 * )

 */

class WebformBlock extends BlockBase {



  /**

   * {@inheritdoc}

   */

  public function build() { 

      ob_start();

      global $base_url,$_SERVER,$_GET;

      $current_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REDIRECT_URL'];

        //echo $current_url; exit();

      $redirect = false;

      /*if(empty($_POST)){

        if(isset($_GET['show']) && isset($_GET['ref']) && isset($_GET['ipiden'])){

          // load normal.

        } else

        if(!isset($_GET['show'])){

          $referral = '';

          if(isset($_GET['ref'])){

              $referral = substr(htmlspecialchars(strip_tags($_GET['ref']), ENT_COMPAT, 'UTF-8'),0,250);
              
          } else {

              if(isset($_SERVER['HTTP_REFERER'])){

                $referral = substr(htmlspecialchars(strip_tags($_SERVER['HTTP_REFERER']), ENT_COMPAT, 'UTF-8'),0,250);
                
              } else {

                $referral = 'n/a';

              }

          }


          // get ipiden.

          if(isset($_GET['ipiden'])){

              $ipiden = $_GET['ipiden'];

          } else {

              $ipiden = md5($_SERVER['REMOTE_ADDR']);

          }
          //echo $ipiden; exit();


          $show = 1;

          $ref = $referral;

          $params = compact('show','ref','ipiden');

          //echo "<pre>".print_r($params,true)."</pre>";exit;

          $url = $current_url."?".http_build_query($params);

         // echo $url; die();

          //echo "<script>jQuery('body').css('display','none');</script>";

          echo "<script>window.location = '".$url."';</script>"; die();

          //$redirect = true;

        }

      }*/

      $output = ob_get_contents();

      ob_end_clean();



    return array(

      '#markup' => $output,

    );

  }



  /**

   * {@inheritdoc}

   */

  protected function blockAccess(AccountInterface $account) {

    //return AccessResult::allowedIfHasPermission($account, 'access content');

    error_reporting(E_ALL);

    ini_set("display_errors", 1);

    global $base_url;

    $tmp = $_SERVER['REDIRECT_URL'];

    $tmp2 = str_replace("http://".$_SERVER['HTTP_HOST'],"",$base_url);

    //echo $tmp."<br>";

    //echo $base_url."<br>";

    $tmp = str_replace($tmp2,"",$tmp);

    $current_path = $tmp;

    //echo $current_path."<br>";

    $args = explode('/', $current_path);

    //echo "<pre>".print_r($args,true)."</pre>"; die();

    $result = false;

    if(isset($args[1])){

      if($args[1] == 'screening-tools'){

        if(isset($args[2])){

          if(count($args) == 3){

            $result = true;

          }

        }

      }

    }

    //echo "result:".$result."<br>"; die();

    //return($result);

    if($result){

      return(AccessResult::allowed());

    } else {

      return(AccessResult::forbidden());   

    }

  }

}