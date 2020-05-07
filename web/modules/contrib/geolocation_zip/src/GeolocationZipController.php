<?php
/**
 * @file
 * Contains \Drupal\geolocation_zip\GeolocationZipController.
 */

namespace Drupal\geolocation_zip;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Config;
use Drupal\geolocation\Plugin\geolocation\Geocoder\GoogleGeocodingAPI;

class GeolocationZipController extends ControllerBase {
  public function content($zip,$distance) {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);

    //echo "<pre>".print_r($_REQUEST,true)."</pre>"; die();
    //echo "zip: ".$zip."<br>"; //die();
    //echo "distance:".$distance."<br>"; die();
    //return new Response($zip);

    $view_id = 'treatment_services';
    $display = 'page';

    $view = \Drupal\views\Views::getView($view_id);
    $view->setDisplay($display);

    //$args = ['37.7749295,-122.41941550000001<=5000miles'];
    $args = $this->geocode($zip,$distance);
    $view->setArguments($args);
    $content = $view->buildRenderable();
    return($content);
  }

  public function geocode($address,$distance){
    $result = $this->geocode2($address);
    //echo "<pre>".print_r($result,true)."</pre>"; //die();
    //$args = ['37.7749295,-122.41941550000001<=5000miles'];
    $lat = $result['location']['lat'];
    $lng = $result['location']['lng'];
    //$args = [$lat.','.$lng.'<=5000miles'];
    $args = [$lat.','.$lng.'<='.$distance];
    return($args);
  }

  public function geocode2($address) {
    if (empty($address)) {
      return FALSE;
    }
    $request_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address;
    //$request_url .= '&key=AIzaSyBGosteHy-rau8K81IauvzrzI7yhlSu-2Y';

    if (!empty(\Drupal::config('geolocation.settings')->get('google_map_api_server_key'))) {
      $request_url .= '&key=' . \Drupal::config('geolocation.settings')->get('google_map_api_key');
    }
    elseif (!empty(\Drupal::config('geolocation.settings')->get('google_map_api_key'))) {
      $request_url .= '&key=' . \Drupal::config('geolocation.settings')->get('google_map_api_key');
    }
    /*if (!empty($this->configuration['components'])) {
      $request_url .= '&components=';
      foreach ($this->configuration['components'] as $component_id => $component_value) {
        $request_url .= $component_id . ':' . $component_value . '|';
      }
    }
    if (!empty($this->geolocationSettings->get('google_map_custom_url_parameters')['language'])) {
      $request_url .= '&language=' . $this->geolocationSettings->get('google_map_custom_url_parameters')['language'];
    }*/

    try {
      //echo $request_url."<br>";
      $result = Json::decode(\Drupal::httpClient()->request('GET', $request_url)->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('geolocation', $e);
      return FALSE;
    }

    if (
      $result['status'] != 'OK'
      || empty($result['results'][0]['geometry'])
    ) {
      return FALSE;
    }

    return [
      'location' => [
        'lat' => $result['results'][0]['geometry']['location']['lat'],
        'lng' => $result['results'][0]['geometry']['location']['lng'],
      ],
      // TODO: Add viewport or build it if missing.
      'boundary' => [
        'lat_north_east' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lat'] + 0.005 : $result['results'][0]['geometry']['viewport']['northeast']['lat'],
        'lng_north_east' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lng'] + 0.005 : $result['results'][0]['geometry']['viewport']['northeast']['lng'],
        'lat_south_west' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lat'] - 0.005 : $result['results'][0]['geometry']['viewport']['southwest']['lat'],
        'lng_south_west' => empty($result['results'][0]['geometry']['viewport']) ? $result['results'][0]['geometry']['location']['lng'] - 0.005 : $result['results'][0]['geometry']['viewport']['southwest']['lng'],
      ],
      'address' => empty($result['results'][0]['formatted_address']) ? '' : $result['results'][0]['formatted_address'],
    ];
  }
}