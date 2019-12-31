<?php

/**
 * @file
 * Views functionality for the field_inheritance module.
 */

/**
 * Implements hook_views_data_alter().
 */
function field_inheritance_views_data_alter(array &$data) {
  // Create a views field for each of the inherited fields.
  $inherited_fields = \Drupal::entityTypeManager()->getStorage('field_inheritance')->loadMultiple();
  $set = FALSE;
  if (!empty($inherited_fields)) {
    foreach ($inherited_fields as $field) {
      $destination_entity = $field->destinationEntityType();
      $entity_def = \Drupal::entityTypeManager()->getDefinition($destination_entity);
      $data_table = $entity_def->getDataTable();

      $data[$data_table][$field->id()] = [
        'title' => $field->label(),
        'field' => [
          'title' => $field->label(),
          'help' => t('The inherited @field field used on: @bundle.', [
            '@field' => $field->label(),
            '@bundle' => $field->destinationEntityBundle(),
          ]),
          'id' => 'inherited_field',
          'field_name' => $field->id(),
          'entity field' => $field->id(),
          // @see: https://www.drupal.org/project/drupal/issues/2981047.
          'bundle' => $field->destinationEntityBundle(),
        ],
      ];
    }
  }
}
