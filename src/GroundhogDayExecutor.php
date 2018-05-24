<?php

namespace Drupal\groundhog_day;


use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\default_content\ExporterInterface;
use Drupal\default_content\ImporterInterface;
use Drupal\Driver\Exception\Exception;

class GroundhogDayExecutor {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\default_content\ExporterInterface
   */
  protected $exporter;

  /**
   * @var \Drupal\default_content\ImporterInterface
   */
  protected $importer;

  /**
   * The name of the test content module.
   *
   * @var string $module
   */
  protected $module;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    EntityTypeManagerInterface $entityTypeManager,
    ExporterInterface $exporter,
    ImporterInterface $importer,
    array $parameters
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->exporter = $exporter;
    $this->importer = $importer;
    $this->module = $parameters['module'];
  }

  public function update() {
    if(!$this->moduleHandler->moduleExists($this->module)) {
      throw new \Exception("The {$this->module} module could not be found. Make sure exists and is enabled.");
      return;
    }
    $path = drupal_get_path('module', $this->module) . '/content';
    if (file_exists($path)) {
      file_unmanaged_delete_recursive($path);
    }
    mkdir($path, 0755, TRUE);

    foreach ($this->entityTypeManager->getDefinitions() as $entityType) {
      if ($entityType instanceof ContentEntityTypeInterface) {
        $storage = $this->entityTypeManager->getStorage($entityType->id());
        foreach ($storage->getQuery()->execute() as $entityId) {
          if ($entityType->id() === 'user' && in_array((int) $entityId, [0, 1])) {
            continue;
          }
          $entity = $storage->load($entityId);
          $file = $path . '/' . $entityType->id() . '/' . $entity->uuid() . '.json';

          if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0755, TRUE);
          }

          $export = $this->exporter->exportContent($entityType->id(), $entityId);
          file_put_contents($file, $export);
        }
      }
    }
  }

  public function reset() {
    if(!$this->moduleHandler->moduleExists($this->module)) {
      throw new \Exception("The {$this->module} module could not be found. Make sure exists and is enabled.");
    }
    foreach ($this->entityTypeManager->getDefinitions() as $entityType) {
      if ($entityType instanceof ContentEntityTypeInterface) {
        $storage = $this->entityTypeManager->getStorage($entityType->id());
        $entityIds = $storage->getQuery()->execute();
        if ($entityType->id() === 'user') {
          $entityIds = array_filter($entityIds, function ($entityId) {
            return !in_array((int) $entityId, [0, 1]);
          });
        }
        $storage->delete($storage->loadMultiple($entityIds));
      }
    }
    $this->importer->importContent($this->module);
  }

}
