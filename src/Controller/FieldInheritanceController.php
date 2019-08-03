<?php

namespace Drupal\field_inheritance\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The FieldInheritanceController class.
 */
class FieldInheritanceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $formBuilder;

  /**
   * Constructs a FieldInheritanceController object.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(EntityFormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder')
    );
  }

  /**
   * Gets the creation form in a modal.
   * 
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_bundle
   *   The entity bundle.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns an ajax response.
   */
  public function ajaxCreationForm($entity_type = NULL , $entity_bundle = NULL) {
    $inheritance_entity = \Drupal::entityTypeManager()->getStorage('field_inheritance')->create();
    $inheritance_entity->setDestinationEntityType($entity_type);
    $inheritance_entity->setDestinationEntityBundle($entity_bundle);

    $response = new AjaxResponse();
    $modal_form = $this->formBuilder->getForm($inheritance_entity, 'add');
    $response->addCommand(new OpenModalDialogCommand('Add Field Inheritance', $modal_form, ['width' => '800']));
    return $response;
  }

}
