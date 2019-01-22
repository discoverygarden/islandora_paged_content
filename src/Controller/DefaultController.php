<?php

namespace Drupal\islandora_paged_content\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;

use AbstractObject;

/**
 * Class DefaultController.
 *
 * @package Drupal\islandora_paged_content\Controller
 */
class DefaultController extends ControllerBase {

  /**
   * Access callback for managing a page.
   */
  public function managePageAccess($models, $object, AccountInterface $account) {
    $object = islandora_object_load($object);
    return AccessResult::allowedIf(islandora_paged_content_manage_page_access_callback($object, $models, $account));
  }

  /**
   * Callback for managing a page.
   */
  public function managePage(AbstractObject $object) {
    module_load_include('inc', 'islandora_paged_content', 'includes/manage_page');
    return islandora_paged_content_manage_page_menu($object);
  }

  /**
   * Access callback for managing pages.
   */
  public function managePagesAccess($models, $object, AccountInterface $account) {
    $object = islandora_object_load($object);
    return AccessResult::allowedIf(islandora_paged_content_manage_pages_access_callback($object, $models, $account));
  }

  /**
   * Callback for managing pages.
   */
  public function managePages(AbstractObject $object) {
    module_load_include('inc', 'islandora_paged_content', 'includes/manage_pages');
    return islandora_paged_content_manage_pages_menu($object);
  }

}
