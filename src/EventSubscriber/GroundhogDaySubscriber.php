<?php

namespace Drupal\groundhog_day\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class GroundhogDaySubscriber implements EventSubscriberInterface {

  protected $timestamp;

  public function __construct(array $paramters) {
    $this->timestamp = strtotime($paramters['datetime']);
  }

  public static function getSubscribedEvents() {
    return[KernelEvents::REQUEST => ['alterRequestTime']];
  }

  public function alterRequestTime(GetResponseEvent $event) {
    $event->getRequest()->server->set('REQUEST_TIME', $this->timestamp);
    $event->getRequest()->server->set('REQUEST_TIME_FLOAT', (float) $this->timestamp);
  }

}
