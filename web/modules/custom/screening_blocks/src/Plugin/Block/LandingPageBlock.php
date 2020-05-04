<?php

namespace Drupal\screening_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Landing Page' Block.
 *
 * @Block(
 *   id = "screening_landing_page_block",
 *   admin_label = @Translation("Screening Tools Landing Page Block"),
 *   category = @Translation("Screening Tools"),
 * )
 */
class LandingPageBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
      ob_start();
      global $base_url;
      //echo "<pre>".print_r($_SERVER,true)."</pre>";
      if(!isset($_GET['form_id'])){
        $entities = \Drupal::entityTypeManager()->getStorage('webform')->loadMultiple(NULL);
        //echo "<pre>".print_r($entities,true)."</pre>";

        $nids = \Drupal::entityQuery('node')->condition('type','webform')->execute();
        $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);
        //echo "<pre>".print_r($nodes,true)."</pre>"; die();

        $mvalues = array();
        foreach($nodes as $key => $node){
          $modified = $node->get('changed')->value;
          $mvalues[$key] = $modified;
        }

        asort($mvalues); // sort according to modified.
        $keys = array_keys($mvalues);

        foreach($keys as $key){
          $node = $nodes[$key];
          //$node = $node->toArray();
          //echo "<pre>".print_r($node,true)."</pre>";
          $title = $node->get('title')->value;
          $modified = $node->get('changed')->value;
          $entity_id = $node->get('webform')->target_id;
          $entity = $entities[$entity_id];
          //echo "<pre>".print_r(compact('title','modified','webform'),true)."</pre>"; die();
          if($entity_id){
            //$title = $entity->get('title');
            if(!(strpos($title,'CPPRN') !== FALSE)) {
              //echo $title."<br>";
              $id = $entity_id;
              $id = str_replace("_","-",$id);
              /*$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REDIRECT_URL'];
              $ipiden = md5($_SERVER['REMOTE_ADDR']);
              $query = array();
              $ref = '';
              if(isset($_GET['ref'])){
                $ref = $_GET['ref'];
              }
              $form_id = $id;
              $_SESSION['ref'] = $ref;
              $_SESSION['ipiden'] = $ipiden;
              $query = compact('form_id');
              $url = Url::fromUri($url,compact('query'));*/

              //$url = $base_url.'/form/'.$id;
              $settings = $entity->get('settings');
              //echo "<pre>".print_r($settings,true)."</pre>";
              $submit_path = $settings['page_submit_path'];
              //echo "submit_path: ".$submit_path."<br>";
              $url = $base_url.$submit_path;
              $ipiden = md5($_SERVER['REMOTE_ADDR']);
              $query = array();
              $ref = '';
              if(isset($_GET['ref'])){
                $ref = $_GET['ref'];
              } else {
                if(isset($_SERVER['HTTP_REFERER'])){
                  $ref = substr(htmlspecialchars(strip_tags($_SERVER['HTTP_REFERER']), ENT_COMPAT, 'UTF-8'),0,250);
                  } 
                  else {
                  $ref = 'n/a';
                }
              }
              $show = 1;
            //  echo $url; //die();
              $query = compact('ref','ipiden','show');
              $url = Url::fromUri($url,compact('query'));
               
              $link = Link::fromTextAndUrl(t($title),$url)->toString();
             // echo $link."<br>";
              echo
              '<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4 views-row">
                <div class="btn-blue">'.$link.'</div>
              </div>';
            }
          }
        }
      }
            /*  echo
              '<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4 views-row">
              <div class="btn-blue"><a href="https://www.surveymonkey.com/r/KS9VPNR" target="_blank">Smoking and Mental Health Survey</a></div>
              </div>';
            */
      $output = ob_get_contents();
      ob_end_clean();

    return array(
      '#markup' => $output,
    );

  }

}