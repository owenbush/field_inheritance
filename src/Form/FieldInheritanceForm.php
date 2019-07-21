<?php

namespace Drupal\field_inheritance\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\field_inheritance\FieldInheritancePluginManager;

/**
 * Class FieldInheritanceForm.
 */
class FieldInheritanceForm extends EntityForm {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The field inheritance plugin manager.
   *
   * @var \Drupal\field_inheritance\FieldInheritancePluginManager
   */
  protected $fieldInheritance;

  /**
   * Construct an FieldInheritanceForm.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\field_inheritance\FieldInheritancePluginManager $field_inheritance
   *   The field inheritance plugin manager.
   */
  public function __construct(Messenger $messenger, EntityFieldManager $entity_field_manager, EntityTypeManager $entity_type_manager, FieldInheritancePluginManager $field_inheritance) {
    $this->messenger = $messenger;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldInheritance = $field_inheritance;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.field_inheritance')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $field_inheritance = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $field_inheritance->label(),
      '#description' => $this->t("Label for the Field inheritance."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $field_inheritance->id(),
      '#machine_name' => [
        'exists' => '\Drupal\field_inheritance\Entity\FieldInheritance::load',
      ],
      '#disabled' => !$field_inheritance->isNew(),
    ];

    $help = [
      $this->t('<b>Inherit</b> - Pull field data directly from the source.'),
      $this->t('<b>Prepend</b> - Place destination data above source data.'),
      $this->t('<b>Append</b> - Place destination data below source data.'),
      $this->t('<b>Fallback</b> - Show destination data, if set, otherwise show source data.'),
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Inheritance Strategy'),
      '#description' => $this->t('Select the method/strategy used to inherit data.'),
      '#options' => [
        'inherit' => $this->t('Inherit'),
        'prepend' => $this->t('Prepend'),
        'append' => $this->t('Append'),
        'fallback' => $this->t('Fallback'),
      ],
      '#required' => TRUE,
      '#default_value' => $field_inheritance->type() ?: 'inherit',
    ];
    $form['information'] = [
      '#type' => 'markup',
      '#prefix' => '<p>',
      '#markup' => implode('</p><p>', $help),
      '#suffix' => '</p>',
    ];

    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_keys($entity_types);
    $entity_types = array_combine($entity_types, $entity_types);

    $entity_bundles = $this->entityTypeManager->getDefinitions();
    $entity_bundles = array_keys($entity_bundles);
    $entity_bundles = array_combine($entity_bundles, $entity_bundles);

    $source_fields = [];
    //$source_fields = array_keys($this->entityFieldManager->getFieldDefinitions('eventseries', 'eventseries'));
    //$source_fields = array_combine($source_fields, $source_fields);

    $destination_fields = [];
    //$destination_fields = array_keys($this->entityFieldManager->getFieldDefinitions('eventinstance', 'eventinstance'));
    //$destination_fields = array_combine($destination_fields, $destination_fields);

    $form['source'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Source Configuration'),
    ];

    $form['source']['source_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Source Entity Type'),
      '#description' => $this->t('Select the source entity type from which to inherit data.'),
      '#options' => $entity_types,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->sourceEntityType(),
      '#ajax' => [],
    ];

    $form['source']['source_entity_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Source Entity Bundle'),
      '#description' => $this->t('Select the source entity bundle from which to inherit data.'),
      '#options' => $entity_bundles,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->sourceEntityBundle(),
      '#ajax' => [],
      '#states' => [
        'visible' => [
          'select[name="source_entity_type"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['source']['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Source Field'),
      '#description' => $this->t('Select the field on the source entity from which to inherit data.'),
      '#options' => $source_fields,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->sourceField(),
      '#states' => [
        'visible' => [
          'select[name="source_entity_type"]' => ['!value' => ''],
          'select[name="source_entity_bundle"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['destination'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Destination Configuration'),
    ];

    $form['destination']['destination_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination Entity Type'),
      '#description' => $this->t('Select the destination entity type to which to inherit data.'),
      '#options' => $entity_types,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->destinationEntityType(),
      '#ajax' => [],
    ];

    $form['destination']['destination_entity_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination Entity Bundle'),
      '#description' => $this->t('Select the destination entity bundle to which to inherit data.'),
      '#options' => $entity_bundles,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->destinationEntityBundle(),
      '#ajax' => [],
      '#states' => [
        'visible' => [
          'select[name="destination_entity_type"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['destination']['destination_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination Field'),
      '#description' => $this->t('(Optionally) Select the field on the destination entity to use during inheritance.'),
      '#options' => $destination_fields,
      '#states' => [
        'visible' => [
          'select[name="type"]' => ['!value' => 'inherit'],
          'select[name="destination_entity_type"]' => ['!value' => ''],
          'select[name="destination_entity_bundle"]' => ['!value' => ''],
        ],
        'required' => [
          'select[name="type"]' => ['!value' => 'inherit'],
          'select[name="destination_entity_type"]' => ['!value' => ''],
          'select[name="destination_entity_bundle"]' => ['!value' => ''],
        ],
      ],
      '#default_value' => $field_inheritance->destinationField(),
    ];

    $plugins = array_keys($this->fieldInheritance->getDefinitions());
    $plugins = array_combine($plugins, $plugins);

    $form['plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Inheritance Plugin'),
      '#description' => $this->t('Select the plugin used to perform the inheritance.'),
      '#options' => $plugins,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->plugin(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();

    if (!empty($values['source_entity_type'])
      && !empty($values['destination_entity_type'])
      && !empty($values['source_entity_bundle'])
      && !empty($values['destination_entity_bundle'])) {
      if (!empty($values['source_field']) && !empty($values['destination_field'])) {
        $source_definitions = $this->entityFieldManager->getFieldDefinitions($values['source_entity_type'], $values['source_entity_bundle']);
        $destination_definitions = $this->entityFieldManager->getFieldDefinitions($values['destination_entity_type'], $values['destination_entity_bundle']);

        if ($source_definitions[$values['source_field']]->getType() !== $destination_definitions[$values['destination_field']]->getType()) {
          $message = $this->t('Source and destination field definition types must be the same to inherit data. Source - @source_name type: @source_type. Destination - @destination_name type: @destination_type', [
            '@source_name' => $values['source_field'],
            '@source_type' => $source_definitions[$values['source_field']]->getType(),
            '@destination_name' => $values['destination_field'],
            '@destination_type' => $destination_definitions[$values['destination_field']]->getType(),
          ]);
          $form_state->setErrorByName('source_field', $message);
          $form_state->setErrorByName('destination_field', $message);
        }

        $plugin_definition = $this->fieldInheritance->getDefinition($values['plugin']);
        $field_types = $plugin_definition['types'];

        if (!in_array($source_definitions[$values['source_field']]->getType(), $field_types)) {
          $message = $this->t('The selected plugin @plugin does not support @source_type fields. The supported field types are: @field_types', [
            '@plugin' => $values['plugin'],
            '@source_type' => $source_definitions[$values['source_field']]->getType(),
            '@field_types' => implode(',', $field_types),
          ]);
          $form_state->setErrorByName('source_field', $message);
          $form_state->setErrorByName('plugin', $message);
        }

        if ($values['source_entity_type'] == $values['destination_entity_type'] && $values['source_entity_bundle'] == $values['destination_entity_bundle']) {
          $message = $this->t('You cannot inherit if the source and destination entities and bundles are the same.');
          $form_state->setErrorByName('source_entity_bundle', $message);
          $form_state->setErrorByName('destination_entity_bundle', $message);
        }

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $field_inheritance = $this->entity;
    $status = $field_inheritance->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label field inheritance.', [
          '%label' => $field_inheritance->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label field inheritance.', [
          '%label' => $field_inheritance->label(),
        ]));
    }
    $this->entityFieldManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($field_inheritance->toUrl('collection'));
  }

}
