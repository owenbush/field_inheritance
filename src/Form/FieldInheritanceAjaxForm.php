<?php

namespace Drupal\field_inheritance\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\field_inheritance\FieldInheritancePluginManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\AppendCommand;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Render\Renderer;

/**
 * Class FieldInheritanceAjaxForm.
 */
class FieldInheritanceAjaxForm extends FieldInheritanceForm {

  /**
   * The entity form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilder;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Construct an FieldInheritanceForm.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\field_inheritance\FieldInheritancePluginManager $field_inheritance
   *   The field inheritance plugin manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityFormBuilder $entity_form_builder
   *   The entity form builder service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   */
  public function __construct(Messenger $messenger, EntityFieldManager $entity_field_manager, EntityTypeManager $entity_type_manager, EntityTypeBundleInfo $entity_type_bundle_info, FieldInheritancePluginManager $field_inheritance, RequestStack $request_stack, EntityFormBuilder $entity_form_builder, Renderer $renderer) {
    parent::__construct($messenger, $entity_field_manager, $entity_type_manager, $entity_type_bundle_info, $field_inheritance, $request_stack);
    $this->entityFormBuilder = $entity_form_builder;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.field_inheritance'),
      $container->get('request_stack'),
      $container->get('entity.form_builder'),
      $container->get('renderer')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxSubmit',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $form_values = $form_state->getValues();
    $entity = $this->entityTypeManager->getStorage($form_values['destination_entity_type'])->create(['type' => $form_values['destination_entity_bundle']]);
    $form = $this->entityFormBuilder->getForm($entity, 'default');
    $field = $form['field_inheritance']['fields']['field_inheritance_' . $form_values['id']];
    $response->addCommand(new AppendCommand('#field-inheritance-ajax-container', $field));

    $message = [
      '#theme' => 'status_messages',
      '#message_list' => $this->messenger->all(),
    ];

    $messages = $this->renderer->render($message);
    $response->addCommand(new HtmlCommand('#field-inheritance-ajax-message', $messages));
    return $response;
  }

}
