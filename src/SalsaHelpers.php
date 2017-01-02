<?php

namespace Drupal\salsa_helpers;

/**
 * @file
 * Provides helper utilities.
 */

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;

/**
 * Class SalsaHelpers.
 *
 * @package Drupal\salsa_helpers
 */
class SalsaHelpers {
  /**
   * The entity manager.
   *
   * @var EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The config factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The route match object for the current page.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * SalsaHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity Manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route Match.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   Menu Link Manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, RouteMatchInterface $route_match, MenuLinkManagerInterface $menu_link_manager) {
    $this->entityManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->routeMatch = $route_match;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * Generates a Universally Unique IDentifier (UUID).
   *
   * @return string
   *   A 16 byte integer represented as a hex string formatted with 4 hyphens.
   */
  public function generateUuid() {
    /** @var \Drupal\Component\Uuid\UuidInterface $uuid_service */
    $uuid_service = \Drupal::service('uuid');
    $uuid = $uuid_service->generate();

    return $uuid;
  }

  /**
   * Install modules.
   *
   * This helper should be called in hook_update().
   *
   * @param string[] $module_names
   *   An array of module names.
   * @param bool $enable_dependencies
   *   (optional) If TRUE, dependencies will automatically be installed in the
   *   correct order. This incurs a significant performance cost, so use FALSE
   *   if you know $module_list is already complete.
   *
   * @return bool
   *   TRUE if the modules were successfully installed.
   *
   * @throws \Drupal\Core\Extension\MissingDependencyException
   *   Thrown when a requested module, or a dependency of one, can not be found.
   *
   * @see ModuleInstallerInterface::install()
   */
  public function installModules(array $module_names, $enable_dependencies = TRUE) {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    return $module_installer->install($module_names, $enable_dependencies);
  }

  /**
   * Create an empty block with a predefined UUID.
   *
   * @param string $type
   *   Block type.
   * @param string $uuid
   *   Block UUID.
   * @param string $title
   *   Block title.
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *   Block object.
   *
   * @see BlockContent::create()
   */
  public function createEmptyBlockContent($type, $uuid, $title) {
    $block = BlockContent::create([
      'type' => $type,
      'uuid' => $uuid,
      'info' => $title,
    ]);
    $block->save();

    return $block;
  }

  /**
   * Create a menu link.
   *
   * @param string $menu_name
   *    Machine name of a menu, eg. main.
   * @param string $title
   *    Menu title.
   * @param string $link
   *    Menu link. Leave empty for homepage.
   * @param string $parent_id
   *    Parent ID of the new menu item.
   * @param int $weight
   *    Menu link weight.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   Menu link content object.
   *
   * @see MenuLinkContent::create()
   */
  public function createMenuLink($menu_name, $title, $link = NULL, $parent_id = NULL, $weight = 0) {
    $menu_link = MenuLinkContent::create([
      'title' => $title,
      'link' => ['uri' => empty($link) ? 'internal:/' : $link],
      'menu_name' => $menu_name,
      'parent' => $parent_id,
      'weight' => $weight,
    ]);
    $menu_link->save();

    return $menu_link;
  }

  /**
   * Create a taxonomy term.
   *
   * @param string $vocabulary_machine_name
   *    Machine of the vocabulary.
   * @param string $term_name
   *    Term name.
   * @param int $parent_id
   *    ID of the parent term.
   * @param int $weight
   *    Term weight.
   * @param string $term_machine_name
   *    Term machine name (require Taxonomy Machine Name module).
   * @param array $extra
   *    Extra data.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *    Term object.
   */
  public function createTaxonomyTerm($vocabulary_machine_name, $term_name, $parent_id = 0, $weight = 0, $term_machine_name = NULL, $extra = NULL) {
    if (empty($term_machine_name)
      && \Drupal::moduleHandler()->moduleExists('taxonomy_machine_name')
    ) {
      $term_machine_name = taxonomy_machine_name_clean_name($term_name);
      $term_machine_name = preg_replace('/([\_]{2,})/', '_', $term_machine_name);
    }

    $term_data = [
      'name' => $term_name,
      'vid' => $vocabulary_machine_name,
      'weight' => $weight,
      'parent' => [$parent_id],
      'machine_name' => $term_machine_name,
    ];
    if (is_array($extra)) {
      $term_data += $extra;
    }
    $term = Term::create($term_data);
    $term->save();

    return $term;
  }

  /**
   * Remove a content type.
   *
   * @param string $machine_name
   *    Machine name of the content type to remove.
   */
  public function removeContentType($machine_name) {
    // Delete all content for this content type.
    $query = \Drupal::entityQuery('node')
      ->condition('type', $machine_name);
    $nids = $query->execute();
    foreach ($nids as $nid) {
      Node::load($nid)->delete();
    }
    // Delete the content type.
    $content_type = \Drupal::entityManager()->getStorage('node_type')->load($machine_name);
    $content_type->delete();
  }

}
