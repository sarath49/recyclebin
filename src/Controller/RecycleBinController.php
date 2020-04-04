<?php

/**
 * @file
 * Contains \Drupal\recyclebin\Controller\RecycleBinController.
 */

namespace Drupal\recyclebin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Database\Connection;

class RecycleBinController extends ControllerBase {
  
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  public function __construct(RouteMatchInterface $route_match, 
    EntityTypeManager $entity_type_manger, 
    Messenger $messenger, Connection $connection) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manger;
    $this->messenger = $messenger;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('database')
    );
  }

  public function entityDelete() {
    $parameters = $this->routeMatch->getParameters();
    foreach ($parameters as $entity_type_id => $entity_id) {
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
      if (empty($entity)) {
        $this->messenger->addError($this->t('Unable to load entity.'));
        return $this->redirect('<front>');
      }
      $this->messenger->addMessage($this->t('The @entity %label has been moved to the recycle bin.', ['@entity' => $entity->getEntityType()->get('label'), '%label' => $entity->label()]));
      \Drupal::state()->set('node.recyclebin.' . $entity->id(), $entity->id());
      $query = $this->connection->update('node_field_data')
        ->condition('nid', $entity->id())
        ->expression('status', 2)
        ->execute();
        $result = $this->connection->insert('node_access')
            ->fields([
                'nid' => $entity->id(),
            ])
            ->execute();
    }
    return $this->redirect($this->getRedirectUrl($entity)->getRouteName(),$this->getRedirectUrl($entity)->getRouteParameters());
  }

  protected function getRedirectUrl($entity) {
    if ($entity->hasLinkTemplate('collection')) {
      // If available, return the collection URL.
      return $entity->urlInfo('collection');
    }
    else {
      // Otherwise fall back to the front page.
      return Url::fromRoute('<front>');
    }
  }
}