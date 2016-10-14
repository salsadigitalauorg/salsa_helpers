<?php
/**
 * @file Provides helper utilities.
 */

namespace Drupal\salsa_helpers;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\taxonomy\Entity\Term;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
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
   * @return
   *   A 16 byte integer represented as a hex string formatted with 4 hyphens.
   */
  public function generateUuid() {
    /** @var UuidInterface $uuid_service */
    $uuid_service = \Drupal::service('uuid');
    $uuid = $uuid_service->generate();

    return $uuid;
  }

  /**
   * Install modules.
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
   * @see ModuleInstallerInterface::install().
   */
  public function installModules(array $module_names, $enable_dependencies = TRUE) {
    /** @var ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    return $module_installer->install($module_names, $enable_dependencies);
  }

  /**
   * Create an empty block with a predefined UUID.
   *
   * @param string $type
   * @param string $uuid
   * @param string $title
   *
   * @return \Drupal\block_content\Entity\BlockContent
   *
   * @see BlockContent::create().
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
   *    Menu title
   * @param string $link
   *    Menu link. Leave empty for homepage.
   * @param string $parent_id
   *    Parent ID of the new menu item.
   * @param int $weight
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *
   * @see MenuLinkContent::create().
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
   * @param string $vocabulary_machine_name
   *    Machine of the vocabulary.
   * @param $term_name
   *    Term name.
   * @param int $parent_id
   *    ID of the parent term.
   * @param int $weight
   * @param string $term_machine_name
   *    Term machine name (require Taxonomy Machine Name module).
   *
   * @return \Drupal\taxonomy\Entity\Term
   */
  public function createTaxonomyTerm($vocabulary_machine_name, $term_name, $parent_id = 0, $weight = 0, $term_machine_name = NULL) {
    if (empty($term_machine_name)
      && \Drupal::moduleHandler()->moduleExists('taxonomy_machine_name')
    ) {
      $term_machine_name = taxonomy_machine_name_clean_name($term_name);
      $term_machine_name = preg_replace('/([\_]{2,})/', '_', $term_machine_name);
    }

    $term = Term::create([
      'name' => $term_name,
      'vid' => $vocabulary_machine_name,
      'weight' => $weight,
      'parent' => [$parent_id],
      'machine_name' => $term_machine_name,
    ]);
    $term->save();

    return $term;
  }
}
