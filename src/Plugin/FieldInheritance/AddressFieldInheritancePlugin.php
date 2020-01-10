<?php

namespace Drupal\field_inheritance\Plugin\FieldInheritance;

use Drupal\field_inheritance\FieldInheritancePluginInterface;

/**
 * Address Field Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "address_inheritance",
 *   name = @Translation("Address Field Inheritance"),
 *   types = {
 *     "address"
 *   }
 * )
 */
class AddressFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
