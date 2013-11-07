<?php

/**
 * @file
 * Contains \Drupal\config_translation\Access\ConfigNameCheck.
 */

namespace Drupal\config_translation\Access;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Checks access for displaying configuration translation page.
 */
class ConfigNameCheck implements StaticAccessCheckInterface {

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $configMapperManager;

  /**
   * Constructs a ConfigNameCheck object.
   *
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The mapper plugin discovery service.
   */
  public function __construct(ConfigMapperManagerInterface $config_mapper_manager) {
    $this->configMapperManager = $config_mapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_config_translation_config_name_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $mapper_plugin = $route->getDefault('mapper_plugin');
    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = $this->configMapperManager->createInstance($mapper_plugin['plugin_id'], $mapper_plugin['plugin_definition']);
    $mapper->populateFromRequest($request);

    $source_language = $mapper->getLanguageWithFallback();
    $target_language = NULL;
    if ($request->query->has('langcode')) {
      $target_language = language_load($request->query->get('langcode'));
    }

    // Only allow access to translate configuration, if proper permissions are
    // granted, the configuration has translatable pieces, the source language
    // and target language are not locked, and the target language is not the
    // original submission language. Although technically configuration can be
    // overlaid with translations in the same language, that is logically not
    // a good idea.
    return (
      $account->hasPermission('translate configuration') &&
      $mapper->hasSchema() &&
      $mapper->hasTranslatable() &&
      !$source_language->locked &&
      (empty($target_language) || (!$target_language->locked && $target_language->id != $source_language->id))
    ) ? static::ALLOW : static::DENY;
  }

}
