<?php

/**
 * @file
 * Contains \Drupal\conditional_fields\Entity\ConditionalField.
 */

namespace Drupal\conditional_fields\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\conditional_fields\ConditionalFieldInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Conditional field entity.
 *
 * @ingroup conditional_fields
 *
 * @ContentEntityType(
 *   id = "conditional_field",
 *   label = @Translation("Conditional field"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\conditional_fields\ConditionalFieldListBuilder",
 *     "views_data" = "Drupal\conditional_fields\Entity\ConditionalFieldViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\conditional_fields\Form\ConditionalFieldForm",
 *       "add" = "Drupal\conditional_fields\Form\ConditionalFieldForm",
 *       "edit" = "Drupal\conditional_fields\Form\ConditionalFieldEditForm",
 *       "delete" = "Drupal\conditional_fields\Form\ConditionalFieldDeleteForm",
 *     },
 *     "access" = "Drupal\conditional_fields\ConditionalFieldAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\conditional_fields\ConditionalFieldHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "conditional_field",
 *   admin_permission = "administer conditional field entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/fields/conditional_field/{conditional_field}",
 *     "add-form" = "/admin/structure/fields/conditional_field/add",
 *     "edit-form" = "/admin/structure/fields/conditional_field/{conditional_field}/edit",
 *     "delete-form" = "/admin/structure/fields/conditional_field/{conditional_field}/delete",
 *     "collection" = "/admin/structure/fields/conditional_field",
 *   },
 *   field_ui_base_route = "conditional_field.settings"
 * )
 */
class ConditionalField extends ContentEntityBase implements ConditionalFieldInterface {
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDependee() {
    return $this->get('dependee')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDependee($dependee) {
    $this->set('dependee', $dependee);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependent() {
    return $this->get('dependent')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDependent($dependent) {
    $this->set('dependent', $dependent);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Conditional field entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Conditional field entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Conditional field entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['dependee'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dependee field'))
      ->setDescription(t('The id of the dependee field instance.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['dependent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dependent field'))
      ->setDescription(t('The id of the dependee field instance.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['options'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Options'))
      ->setDescription(t('Serialized data containing the options for the dependency.'));



    return $fields;
  }

}