<?php

namespace Drupal\groundhog_day\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\default_content\Event\DefaultContentEvents;
use Drupal\default_content\Event\ImportEvent;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class GroundhogDaySubscriber implements EventSubscriberInterface {

  protected $timestamp;

  protected $module;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  public function __construct(array $paramters, ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
    $this->timestamp = strtotime($paramters['datetime']);
    $this->module = $paramters['module'];
  }

  public static function getSubscribedEvents() {
    return[
      KernelEvents::REQUEST => ['alterRequestTime'],
      DefaultContentEvents::IMPORT => ['alterUsers'],
    ];
  }

  public function alterRequestTime(GetResponseEvent $event) {
    $event->getRequest()->server->set('REQUEST_TIME', $this->timestamp);
    $event->getRequest()->server->set('REQUEST_TIME_FLOAT', (float) $this->timestamp);
  }

  /**
   * Set passwords to be equal to user names.
   *
   * @param \Drupal\default_content\Event\ImportEvent $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function alterUsers(ImportEvent $event) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $factory */
    $config = $this->configFactory->getEditable('user.settings');
    $notify = $config->get('notify.status_activated');
    $config->set('notify.status_activated', FALSE);
    $config->save(TRUE);
    foreach ($event->getImportedEntities() as $entity) {
      if ($entity instanceof User) {
        $entity->setPassword($entity->getAccountName());
        $entity->set('status', TRUE);
        if ($role = Role::load($entity->label())) {
          $entity->addRole($role->id());
        }
        $entity->save();
      }
    }
    $config->set('notify.status_activated', $notify);
    $config->save(TRUE);
  }

}
