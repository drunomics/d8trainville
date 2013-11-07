<?php

/**
 * @file
 * Contains \Drupal\config_translation\Form\ConfigTranslationEditForm.
 */

namespace Drupal\config_translation\Form;

use Drupal\config_translation\ConfigMapperInterface;
use Drupal\Core\Language\Language;

/**
 * Defines a form controller for editing configuration translations.
 */
class ConfigTranslationEditForm extends ConfigTranslationFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_translation_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, ConfigMapperInterface $mapper = NULL, Language $language = NULL) {
    $form = parent::buildForm($form, $form_state, $mapper, $language);
    $form['#title'] = $this->t('Edit @language translation for %label', array(
      '%label' => $this->mapper->getTitle(),
      '@language' => $this->language->name,
    ));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);
    drupal_set_message($this->t('Successfully updated @language translation.', array('@language' => $this->language->name)));
  }
}
