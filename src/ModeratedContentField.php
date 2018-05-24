<?php

namespace Drupal\groundhog_day;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Class ModeratedContentField
 *
 * A custom computed field attached to the moderated content, so it's properly
 * exported with a reference to the target entity.
 */
class ModeratedContentField extends FieldItemList {
  use ComputedItemListTrait;

  protected function computeValue() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $host */
    $host = $this->getParent()->getValue();
    $storage = $entityTypeManager->getStorage($host->content_entity_type_id->value);
    $this->list[0] = $this->createItem(0, $storage->loadRevision($host->content_entity_revision_id->value));
  }

}
