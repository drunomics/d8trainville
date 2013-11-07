<?php

/**
 * @file
 * Contains \Drupal\config_translation\Plugin\Derivative\ConfigTranslationLocalTasks.
 */

namespace Drupal\config_translation\Plugin\Derivative;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic local tasks for config translation.
 */
class ConfigTranslationLocalTasks extends DerivativeBase implements ContainerDerivativeInterface {

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $mapperManager;

  /**
   * The base plugin ID
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * Constructs a new ConfigTranslationLocalTasks.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $mapper_manager
   *   The mapper plugin discovery service.
   */
  public function __construct($base_plugin_id, ConfigMapperManagerInterface $mapper_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->mapperManager = $mapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('plugin.manager.config_translation.mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    $mappers = $this->mapperManager->getMappers();
    foreach ($mappers as $mapper) {
      /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
      $route_name = $mapper->getRouteName();
      $this->derivatives[$route_name] = $base_plugin_definition;

      // @todo Convert this to title and placeholder once the core issue at
      //   https://drupal.org/node/2120235 is fixed.
      $this->derivatives[$route_name]['title'] = 'Translate ' . Unicode::strtolower($mapper->getTypeName());

      $this->derivatives[$route_name]['route_name'] = $route_name;
    }
    return $this->derivatives;
  }

  /**
   * Alters the local tasks to find the proper tab_root_id for each task.
   */
  public function alterLocalTasks(array &$local_tasks) {
    $mappers = $this->mapperManager->getMappers();
    foreach ($mappers as $mapper) {
      /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
      $route_name = $mapper->getRouteName();
      $translation_tab = $this->basePluginId . ':' . $route_name;
      // The config translation routes are based on the base route with a
      // prefix prepended.
      $tab_root_route_name = str_replace('config_translation.item.', '', $route_name);
      $tab_root_id = $this->getTaskFromRoute($tab_root_route_name, $local_tasks);
      if (!empty($tab_root_id)) {
        $local_tasks[$translation_tab]['tab_root_id'] = $tab_root_id;
      }
      else {
        unset($local_tasks[$translation_tab]);
      }
    }
  }

  /**
   * Find the local task ID of the parent route given the route name.
   *
   * @param string $route_name
   *   The route name of the parent local task.
   * @param array $local_tasks
   *   An array of all local task definitions.
   *
   * @return bool|string
   *   Returns the local task ID of the parent task, otherwise return FALSE.
   */
  protected function getTaskFromRoute($route_name, &$local_tasks) {
    $root_local_task = FALSE;
    foreach ($local_tasks as $plugin_id => $local_task) {
      if ($local_task['route_name'] == $route_name) {
        $root_local_task = $plugin_id;
        break;
      }
    }

    return $root_local_task;
  }

}
