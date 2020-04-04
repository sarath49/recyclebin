<?php

/**
 * @file
 * Contains \Drupal\trash\Routing\RouteSubscriber.
 */

namespace Drupal\recyclebin\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Multiversion UI routes.
 */
class RouteSubscriber extends RouteSubscriberBase {
  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
      //kint($collection); exit;
      if ($route = $collection->get("entity.node.delete_form")) {
        if (!empty($route)) {
            $defaults = $route->getDefaults();
            unset($defaults['_entity_form']);
            $defaults['_controller'] = '\Drupal\recyclebin\Controller\RecycleBinController::entityDelete';
            $route->setDefaults($defaults);
            //TODO: Get the right permission
            $route->setRequirements(['_permission' => 'access unpublished content']);
        }
      }
  }

}