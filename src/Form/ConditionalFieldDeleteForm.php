<?php

namespace Drupal\conditional_fields\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

/**
 * Class ConditionalFieldDeleteForm.
 *
 * @package Drupal\conditional_fields\Form
 */
class ConditionalFieldDeleteForm extends ConfirmFormBase {

  private $entity_type;
  private $bundle;
  private $field_name;
  private $uuid;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the %field_name condition?', [
      '%field_name' => $this->field_name,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('conditional_fields');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'conditional_field_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->entity_type) || empty($this->bundle) || empty($this->field_name) || empty($this->uuid)) {
      return;
    }
    $entity = entity_get_form_display($this->entity_type, $this->bundle, 'default');
    $field = $entity->getComponent($this->field_name);
    unset($field['third_party_settings']['conditional_fields'][$this->uuid]);
    $entity->setComponent($this->field_name, $field);
    $entity->save();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL, $bundle = NULL, $field_name = NULL, $uuid = NULL) {
    $this->entity_type = $entity_type;
    $this->bundle = $bundle;
    $this->field_name = $field_name;
    $this->uuid = $uuid;

    return parent::buildForm($form, $form_state);
  }

}