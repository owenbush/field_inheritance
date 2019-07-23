<?php

namespace Drupal\field_inheritance\Plugin\FieldInheritance;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field_inheritance\FieldInheritancePluginInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Abstract class FieldInheritancePluginBase.
 */
abstract class FieldInheritancePluginBase extends PluginBase implements FieldInheritancePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The method used to inherit.
   *
   * @var string
   */
  protected $method;

  /**
   * The source field used to inherit.
   *
   * @var string
   */
  protected $sourceField;

  /**
   * The entity field used to inherit.
   *
   * @var string
   */
  protected $destinationField;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current language code.
   *
   * @var string
   */
  protected $langCode;

  /**
   * Constructs a FieldInheritancePluginBase object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entity = $configuration['entity'];
    $this->method = $configuration['method'];
    $this->sourceField = $configuration['source field'];
    if (!empty($configuration['destination field'])) {
      $this->destinationField = $configuration['destination field'];
    }
    $this->languageManager = $language_manager;
    $this->langCode = $this->languageManager->getCurrentLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager')
    );
  }

  /**
   * Get the configuration method.
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * Get the configuration source entity type.
   */
  public function getSourceEntityType() {
    return $this->sourceEntityType;
  }

  /**
   * Get the configuration source entity bundle.
   */
  public function getSourceEntityBundle() {
    return $this->sourceEntityBundle;
  }

  /**
   * Get the configuration source field.
   */
  public function getSourceField() {
    return $this->sourceField;
  }

  /**
   * Get the configuration destination entity type.
   */
  public function getDestinationEntityType() {
    return $this->destinationEntityType;
  }

  /**
   * Get the configuration destination entity bundle.
   */
  public function getDestinationEntityBundle() {
    return $this->destinationEntityBundle;
  }

  /**
   * Get the configuration destination field.
   */
  public function getDestinationField() {
    return $this->destinationField;
  }

  /**
   * {@inheritdoc}
   */
  public function computeValue() {
    $this->validateArguments();
    $method = $this->getMethod();

    $value = '';
    switch ($method) {
      case 'inherit':
        $value = $this->inheritData();
        break;

      case 'prepend':
        $value = $this->prependData();
        break;

      case 'append':
        $value = $this->appendData();
        break;

      case 'fallback':
        $value = $this->fallbackData();
        break;
    }
    return $value;
  }

  /**
   * Retrieve inherited data.
   *
   * @return string
   *   The inherited data.
   */
  protected function inheritData() {
    $source_entity = $this->getSourceEntity();
    if ($source_entity === FALSE) {
      return [];
    }
    return $source_entity->{$this->getSourceField()}->getValue() ?? '';
  }

  /**
   * Retrieve prepended data.
   *
   * @return string
   *   The prepended data.
   */
  protected function prependData() {
    $source_entity = $this->getSourceEntity();
    $destination_entity = $this->getDestinationEntity();
    $values = [];

    if ($source_entity === FALSE) {
      return $values;
    }

    if (!empty($destination_entity->{$this->getDestinationField()}->getValue())) {
      $values = array_merge($values, $destination_entity->{$this->getDestinationField()}->getValue());
    }
    if (!empty($source_entity->{$this->getSourceField()}->getValue())) {
      $values = array_merge($values, $source_entity->{$this->getSourceField()}->getValue());
    }
    return $values;
  }

  /**
   * Retrieve appended data.
   *
   * @return string
   *   The appended data.
   */
  protected function appendData() {
    $source_entity = $this->getSourceEntity();
    $destination_entity = $this->getDestinationEntity();
    $values = [];

    if ($source_entity === FALSE) {
      return $values;
    }

    if (!empty($source_entity->{$this->getSourceField()}->getValue())) {
      $values = array_merge($values, $source_entity->{$this->getSourceField()}->getValue());
    }
    if (!empty($destination_entity->{$this->getDestinationField()}->getValue())) {
      $values = array_merge($values, $destination_entity->{$this->getDestinationField()}->getValue());
    }
    return $values;
  }

  /**
   * Retrieve fallback data.
   *
   * @return string
   *   The fallback data.
   */
  protected function fallbackData() {
    $source_entity = $this->getSourceEntity();
    $destination_entity = $this->getDestinationEntity();
    $values = [];

    if ($source_entity === FALSE) {
      return $values;
    }

    if (!empty($destination_entity->{$this->getDestinationField()}->getValue())) {
      $values = $destination_entity->{$this->getDestinationField()}->getValue();
    }
    elseif (!empty($source_entity->{$this->getSourceField()}->getValue())) {
      $values = $source_entity->{$this->getSourceField()}->getValue();
    }
    return $values;
  }

  /**
   * Validate the configuration arguments of the plugin.
   */
  protected function validateArguments() {
    if (empty($this->getMethod())) {
      throw new \InvalidArgumentException("The definition's 'method' key must be set to inherit data.");
    }

    if (empty($this->getSourceField())) {
      throw new \InvalidArgumentException("The definition's 'source field' key must be set to inherit data.");
    }

    $method = $this->getMethod();
    $destination_field_methods = [
      'prepend',
      'append',
      'fallback',
    ];

    if (array_search($method, $destination_field_methods)) {
      if (empty($this->getDestinationField())) {
        throw new \InvalidArgumentException("The definition's 'destination field' key must be set to prepend, append, or fallback to series data.");
      }
    }

    return TRUE;
  }

  /**
   * Get the translated source entity.
   *
   * @return Drupal\Core\Entity\EntityInterface|bool
   *   The translated source entity, or FALSE.
   */
  protected function getSourceEntity() {
    $entity = $this->entity;
    if (empty($entity)) {
      return FALSE;
    }
    if ($entity->hasTranslation($this->langCode)) {
      return $entity->getTranslation($this->langCode);
    }
    return $entity;
  }

  /**
   * Get the translated destination entity.
   *
   * @return Drupal\Core\Entity\EntityInterface
   *   The translated destination entity.
   */
  protected function getDestinationEntity() {
    if ($this->entity->hasTranslation($this->langCode)) {
      return $this->entity->getTranslation($this->langCode);
    }
    return $this->entity;
  }

}
