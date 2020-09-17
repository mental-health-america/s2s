<?php

namespace Drupal\current_ip;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks access to a entity revision.
 */
class GetCurrentIp {
  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a GetCurrentIp object.
   *
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request object.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * Function to get Current public IP.
   */
  public function publicIp() {
    $server = $this->requestStack->getCurrentRequest()->server;
    if (!empty($server->get('HTTP_CLIENT_IP'))) {
      $ip = $server->get('HTTP_CLIENT_IP');
    }
    elseif (!empty($server->get('HTTP_X_FORWARDED_FOR'))) {
      $ip = $server->get('HTTP_X_FORWARDED_FOR');
    }
    else {
      $ip = $server->get('REMOTE_ADDR');
    }
    // Remove duplicate ip result.
    $ip = explode(',', $ip);
    if (count($ip) > 1) {
      $ip = $ip['0'];
    }
    return $ip;
  }

}
