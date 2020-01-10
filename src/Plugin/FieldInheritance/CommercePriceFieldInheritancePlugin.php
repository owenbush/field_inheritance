<?php

namespace Drupal\field_inheritance\Plugin\FieldInheritance;

use Drupal\field_inheritance\FieldInheritancePluginInterface;

/**
 * Address Field Inheritance plugin.
 *
 * @FieldInheritance(
 *   id = "commerce_price_inheritance",
 *   name = @Translation("Commerce Price Field Inheritance"),
 *   types = {
 *     "commerce_price"
 *   }
 * )
 */
class CommercePriceFieldInheritancePlugin extends FieldInheritancePluginBase implements FieldInheritancePluginInterface {
}
