<?php

/**
 * @file
 * Contain Drush utility commands.
 */

/**
 * Implements hook_drush_command().
 */
function salsa_helpers_drush_command() {
  $items['field-data'] = array(
    'description' => dt('Dump Field data'),
    'aliases' => array('fdat'),
    'arguments' => array(
      'name' => dt('Field machine name.'),
    ),
    'options' => array(
      'full' => dt('Dump full field data, default is FALSE.'),
    ),
  );

  $items['field-instance'] = array(
    'description' => dt('Dump Field instance'),
    'aliases' => array('fins'),
    'arguments' => array(
      'name' => dt('Field machine name.'),
      'entity' => dt('Entity type.'),
      'bundle' => dt('Entity bundle'),
    ),
    'options' => array(
      'full' => dt('Dump full field instance, default is FALSE.'),
    ),
  );

  $items['hook-update'] = array(
    'description' => dt('Invoke a hook update.'),
    'aliases' => array('hu'),
    'arguments' => array(
      'module' => dt('Module name.'),
      'number' => dt('Hook update number.'),
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
  );

  return $items;
}

/**
 * Dump Field data.
 *
 * @param string $name
 *   Field machine name.
 */
function drush_salsa_helpers_field_data($name) {
  if ($field = field_info_field($name)) {
    $full = drush_get_option('full', FALSE);
    if (!$full) {
      unset($field['translatable']);
      unset($field['entity_types']);
      unset($field['storage']);
      unset($field['foreign keys']);
      unset($field['indexes']);
      unset($field['columns']);
      unset($field['bundles']);
      unset($field['id']);
      unset($field['active']);
      unset($field['locked']);
      unset($field['deleted']);
    }
    $export = var_export($field, TRUE);
    $export = str_replace('array (', 'array(', $export);
    drush_print($export);
  }
  else {
    drush_log(dt('Field !name does not exist.', ['!name' => $name]), 'error');
  }
}

/**
 * Dump Field instance.
 *
 * @param string $name
 *   Field machine name.
 * @param string $entity
 *   Entity type.
 * @param string $bundle
 *   Entity bundle.
 */
function drush_salsa_helpers_field_instance($name, $entity, $bundle) {
  if ($instance = field_info_instance($entity, $name, $bundle)) {
    $full = drush_get_option('full', FALSE);
    if (!$full) {
      unset($instance['field_id']);
      unset($instance['id']);
      unset($instance['deleted']);
    }
    $export = var_export($instance, TRUE);
    $export = str_replace('array (', 'array(', $export);
    drush_print($export);
  }
  else {
    drush_log(dt('Field !name does not exist in !entity::!bundle.', [
      '!name' => $name,
      '!entity' => $entity,
      '!bundle' => $bundle,
    ]), 'error');
  }
}

/**
 * Invoke a hook update.
 *
 * @param string $module
 *   Module name.
 * @param int $number
 *   Hook update number.
 */
function drush_salsa_helpers_hook_update($module, $number) {
  drush_include_engine('drupal', 'update');
  module_load_install($module);
  $context = [];
  drush_update_do_one($module, $number, [], $context);
  if (!empty($context['results']['#abort'][0])) {
    drush_log(dt('Error: !function', ['!function' => $context['results']['#abort'][0]]), 'error');
  }
  else {
    drush_log($context['message'], 'ok');
  }
}
