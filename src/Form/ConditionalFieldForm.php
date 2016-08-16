<?php

namespace Drupal\conditional_fields\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Form controller for Conditional field edit forms.
 *
 * @ingroup conditional_fields
 */
class ConditionalFieldForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_type = 'node';
    module_load_include('inc', 'conditional_fields', 'conditional_fields.conditions');

    $form = parent::buildForm($form, $form_state);

    $entity_types_defs = NodeType::loadMultiple();
    $entity_types_options = [];
    foreach ($entity_types_defs as $entity_types_def) {
      $entity_types_options[$entity_types_def->id()] = $entity_types_def->label();
    }

    $form['field_bundle_name'] = [
      '#title' => $this->t('Bundle name'),
      '#type' => 'select',
      '#options' => $entity_types_options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::conditionalFieldsCallback',
        'wrapper' => 'conditional-fields-wrapper',
      ],
    ];

    $form['conditional_fields_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'conditional-fields-wrapper'],
    ];
    if ($bundle_name = $form_state->getValue('field_bundle_name')) {
      $form['conditional_fields_wrapper']['table'] = $this->buildTable($form, $form_state, $entity_type, $bundle_name);
    }

    return $form;
  }

  /**
   * Builds table with conditional fields.
   */
  protected function buildTable(array $form, FormStateInterface $form_state, $entity_type, $bundle_name = NULL) {
    $form['table'] = array(
      '#type' => 'table',
      '#entity_type' => $entity_type,
      '#bundle_name' => $bundle_name,
      '#header' => array(
        t('Dependent'),
        t('Dependees'),
        array('data' => t('Description'), 'colspan' => 2),
        // array('data' => t('Operations'), 'colspan' => 2),
      ),
      '#attributes' => array(
        'class' => array('conditional-fields-overview'),
      ),
      'dependencies' => array(),
    );

    // Build list of available fields.
    $fields = array();
    $instances = \Drupal::entityManager()
      ->getFieldDefinitions($entity_type, $bundle_name);
    foreach ($instances as $field) {
      $fields[$field->getName()] = $field->getLabel() . ' (' . $field->getName() . ')';
    }

    asort($fields);

    // Build list of states.
    $states = conditional_fields_states();

    // Build list of conditions.
    $conditions = [];
    foreach (conditional_fields_conditions() as $condition => $label) {
      $conditions[$condition] = $condition == 'value' ? t('has value...') : t('is !label', array('!label' => $label));
    }

    // Add new dependency row.
    $form['table']['add_new_dependency'] = array(
      'dependent' => array(
        '#type' => 'select',
        '#title' => t('Dependent'),
        '#title_display' => 'invisible',
        '#description' => t('Dependent'),
        '#options' => $fields,
        '#prefix' => '<div class="add-new-placeholder">' . t('Add new dependency') . '</div>',
      ),
      'dependee' => array(
        '#type' => 'select',
        '#title' => t('Dependee'),
        '#title_display' => 'invisible',
        '#description' => t('Dependee'),
        '#options' => $fields,
        '#prefix' => '<div class="add-new-placeholder">&nbsp;</div>',
      ),
      'state' => array(
        '#type' => 'select',
        '#title' => t('State'),
        '#title_display' => 'invisible',
        '#options' => $states,
        '#default_value' => 'visible',
        '#prefix' => t('The dependent field is') . '&nbsp;<span class="description-select">',
        '#suffix' => '</span>&nbsp;' . t('when the dependee'),
      ),
      'condition' => array(
        '#type' => 'select',
        '#title' => t('Condition'),
        '#title_display' => 'invisible',
        '#options' => $conditions,
        '#default_value' => 'value',
        '#prefix' => '&nbsp;<span class="description-select">',
        '#suffix' => '</span>',
      ),
      /*'actions' => array(
        'submit' => array(
          '#type' => 'submit',
          '#value' => t('Add dependency'),
        ),
      ),*/
    );
    return $form['table'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Conditional field.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Conditional field.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.conditional_field.canonical', ['conditional_field' => $entity->id()]);
  }

  /**
   * Implements callback for Ajax event on entity type selection.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Fields section of the form.
   */
  public function conditionalFieldsCallback(array &$form, FormStateInterface $form_state) {
    return $form['conditional_fields_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getValue('table');
    if (!is_array($table) || !array_key_exists('add_new_dependency', $table) || !is_array($table['add_new_dependency'])) {
      return parent::validateForm($form, $form_state);
    }
    $conditional_values = $table['add_new_dependency'];
    // Check dependency.
    if (array_key_exists('dependee', $conditional_values) &&
      array_key_exists('dependent', $conditional_values) &&
      $conditional_values['dependee'] == $conditional_values['dependent']
    ) {
      $form_state->setErrorByName('dependee', $this->t('You should select two different fields.'));
      $form_state->setErrorByName('dependent', $this->t('You should select two different fields.'));
    }

    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getValue('table');
    if (!is_array($table) || !array_key_exists('add_new_dependency', $table) || !is_array($table['add_new_dependency'])) {
      parent::submitForm($form, $form_state);
    }
    $conditional_values = $table['add_new_dependency'];
    // Copy values from table for submit.
    $options = [];
    foreach ($conditional_values as $key => $value) {
      if (in_array($key, ['dependee', 'dependent'])) {
        $form_state->setValue($key, $value);
      }
      $options[$key] = $value;
    }
    $options += conditional_fields_dependency_default_options();
    $form_state->setValue('options', $options);
    parent::submitForm($form, $form_state);
  }

}