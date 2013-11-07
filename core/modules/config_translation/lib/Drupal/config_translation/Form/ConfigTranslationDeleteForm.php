<?php

/**
 * @file
 * Contains \Drupal\config_translation\Form\ConfigTranslationDeleteForm.
 */

namespace Drupal\config_translation\Form;

use Drupal\config_translation\ConfigMapperInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Language\Language;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a form to delete configuration translation.
 */
class ConfigTranslationDeleteForm extends ConfirmFormBase {

  /**
   * The configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface $config_storage
   */
  protected $configStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The configuration translation to be deleted.
   *
   * @var \Drupal\config_translation\ConfigMapperInterface
   */
  protected $mapper;

  /**
   * The language of configuration translation.
   *
   * @var \Drupal\Core\Language\Language
   */
  protected $language;

  /**
   * Constructs a ConfigTranslationDeleteForm.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The configuration storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(StorageInterface $config_storage, ModuleHandlerInterface $module_handler) {
    $this->configStorage = $config_storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @language translation of %label?', array('%label' => $this->mapper->getTitle(), '@language' => $this->language->name));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => $this->mapper->getRouteName(),
      'route_parameters' => $this->mapper->getRouteParameters(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'config_translation_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, ConfigMapperInterface $mapper = NULL, Language $language = NULL) {
    $this->mapper = $mapper;
    $this->language = $language;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    foreach ($this->mapper->getConfigNames() as $name) {
      $this->configStorage->delete('locale.config.' . $this->language->id . '.' . $name);
    }

    // Flush all persistent caches.
    $this->moduleHandler->invokeAll('cache_flush');
    foreach (Cache::getBins() as $service_id => $cache_backend) {
      if ($service_id != 'cache.menu') {
        $cache_backend->deleteAll();
      }
    }

    drupal_set_message($this->t('@language translation of %label was deleted', array('%label' => $this->mapper->getTitle(), '@language' => $this->language->name)));
    $form_state['redirect'] = $this->mapper->getBasePath() . '/translate';
  }

}
