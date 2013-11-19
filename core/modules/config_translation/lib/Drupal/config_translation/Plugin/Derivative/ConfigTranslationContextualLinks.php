<?php

/**
 * @file
 * Contains \Drupal\config_translation\Plugin\Derivative\ConfigTranslationContextualLinks.
 */

namespace Drupal\config_translation\Plugin\Derivative;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic contextual links for configuration translation.
 */
class ConfigTranslationContextualLinks extends DerivativeBase implements ContainerDerivativeInterface {

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $mapperManager;

  /**
   * Constructs a new ConfigTranslationContextualLinks.
   *
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $mapper_manager
   *   The mapper plugin discovery service.
   */
  public function __construct(ConfigMapperManagerInterface $mapper_manager) {
    $this->mapperManager = $mapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.config_translation.mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Create contextual links for all mappers.
    $mappers = $this->mapperManager->getMappers();
    foreach ($mappers as $plugin_id => $mapper) {
      // @todo Contextual groups do not map to entity types in a predictable
      //   way. See https://drupal.org/node/2134841 to make them predictable.
      $group_name = $mapper->getContextualLinkGroup();
      if (empty($group_name)) {
        continue;
      }

      /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
      $route_name = $mapper->getOverviewRouteName();
      $this->derivatives[$route_name] = $base_plugin_definition;
      $this->derivatives[$route_name]['config_translation_plugin_id'] = $plugin_id;
      $this->derivatives[$route_name]['class'] = '\Drupal\config_translation\Plugin\Menu\ContextualLink\ConfigTranslationContextualLink';
      $this->derivatives[$route_name]['route_name'] = $route_name;
      $this->derivatives[$route_name]['group'] = $group_name;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
