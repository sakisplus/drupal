<?php

/**
 * @file
 * Contains \Drupal\shortcut\Entity\Shortcut.
 */

namespace Drupal\shortcut\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\link\LinkItemInterface;
use Drupal\shortcut\ShortcutInterface;

/**
 * Defines the shortcut entity class.
 *
 * @property \Drupal\link\LinkItemInterface link
 *
 * @ContentEntityType(
 *   id = "shortcut",
 *   label = @Translation("Shortcut link"),
 *   handlers = {
 *     "access" = "Drupal\shortcut\ShortcutAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\shortcut\ShortcutForm",
 *       "add" = "Drupal\shortcut\ShortcutForm",
 *       "edit" = "Drupal\shortcut\ShortcutForm",
 *       "delete" = "Drupal\shortcut\Form\ShortcutDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "shortcut",
 *   data_table = "shortcut_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "shortcut_set",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/user-interface/shortcut/link/{shortcut}",
 *     "delete-form" = "/admin/config/user-interface/shortcut/link/{shortcut}/delete",
 *     "edit-form" = "/admin/config/user-interface/shortcut/link/{shortcut}",
 *   },
 *   list_cache_tags = { "config:shortcut_set_list" },
 *   bundle_entity_type = "shortcut_set"
 * )
 */
class Shortcut extends ContentEntityBase implements ShortcutInterface {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($link_title) {
    $this->set('title', $link_title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return $this->link->first()->getUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Entity::postSave() calls Entity::invalidateTagsOnSave(), which only
    // handles the regular cases. The Shortcut entity has one special case: a
    // newly created shortcut is *also* added to a shortcut set, so we must
    // invalidate the associated shortcut set's cache tag.
    if (!$update) {
      Cache::invalidateTags($this->getCacheTags());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the shortcut.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the shortcut.'))
      ->setReadOnly(TRUE);

    $fields['shortcut_set'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Shortcut set'))
      ->setDescription(t('The bundle of the shortcut.'))
      ->setSetting('target_type', 'shortcut_set')
      ->setRequired(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the shortcut.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -10,
        'settings' => array(
          'size' => 40,
        ),
      ));

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Weight among shortcuts in the same shortcut set.'));

    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Path'))
      ->setDescription(t('The location this shortcut points to.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'link_type' => LinkItemInterface::LINK_INTERNAL,
        'title' => DRUPAL_DISABLED,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'link_default',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The language code of the shortcut.'))
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 2,
      ));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->shortcut_set->entity->getCacheTags();
  }

}