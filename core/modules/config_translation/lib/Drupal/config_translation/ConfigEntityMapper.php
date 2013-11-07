<?php

/**
 * @file
 * Contains \Drupal\config_translation\ConfigEntityMapper.
 */

namespace Drupal\config_translation;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\locale\LocaleConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Configuration entity mapper.
 */
class ConfigEntityMapper extends ConfigNamesMapper {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Configuration entity type name.
   *
   * @var string
   */
  protected $entityType;

  /**
   * Loaded entity instance to help produce the translation interface.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The label for the entity type.
   *
   * @var string
   */
  protected $typeLabel;

  /**
   * Constructs a ConfigEntityMapper.
   *
   * @param string $plugin_id
   *   The config mapper plugin ID.
   * @param array $plugin_definition
   *   An array of definitions with mapper details.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory.
   * @param \Drupal\locale\LocaleConfigManager $locale_config_manager
   *   The locale configuration manager.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The mapper plugin discovery service.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The string translation manager.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct($plugin_id, array $plugin_definition, ConfigFactory $config_factory, LocaleConfigManager $locale_config_manager, ConfigMapperManagerInterface $config_mapper_manager, RouteProviderInterface $route_provider, TranslationInterface $translation_manager, EntityManager $entity_manager) {
    parent::__construct($plugin_id, $plugin_definition, $config_factory, $locale_config_manager, $config_mapper_manager, $route_provider, $translation_manager);
    $this->setType($plugin_definition['entity_type']);

    $this->entityManager = $entity_manager;

    // Field instances are grouped by the entity type they are attached to.
    // Create a useful label from the entity type they are attached to.
    if ($plugin_definition['entity_type'] == 'field_instance') {
      $base_entity_type = $this->entityManager->getDefinition($plugin_definition['base_entity_type']);
      $this->typeLabel = $this->t('@label fields', array('@label' => $base_entity_type['label']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    // Note that we ignore the plugin $configuration because mappers have
    // nothing to configure in themselves.
    return new static (
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('locale.config.typed'),
      $container->get('plugin.manager.config_translation.mapper'),
      $container->get('router.route_provider'),
      $container->get('string_translation'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function populateFromRequest(Request $request) {
    $entity = $request->attributes->get($this->entityType);
    $this->setEntity($entity);
  }

  /**
   * Sets the entity instance for this mapper.
   *
   * This method can only be invoked when the concrete entity is known, that is
   * in a request for an entity translation path. After this method is called,
   * the mapper is fully populated with the proper display title and
   * configuration names to use to check permissions or display a translation
   * screen.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to set.
   *
   * @return bool
   *   TRUE, if the entity was set successfully; FALSE otherwise.
   */
  public function setEntity(EntityInterface $entity) {
    if (isset($this->entity)) {
      return FALSE;
    }

    $this->entity = $entity;

    // Replace title placeholder with entity label. It is later escaped for
    // display.
    $this->pluginDefinition['title'] = $this->t($this->getTitle(), array('!label' => $entity->label()));

    // Add the list of configuration IDs belonging to this entity. We add on a
    // possibly existing list of names. This allows modules to alter the entity
    // page with more names if form altering added more configuration to an
    // entity. This is not a Drupal 8 best practice (ideally the configuration
    // would have pluggable components), but this may happen as well.
    $entity_type_info = $this->entityManager->getDefinition($this->entityType);
    $this->addConfigName($entity_type_info['config_prefix'] . '.' . $entity->id());

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    return array($this->entityType => $this->entity->id());
  }

  /**
   * Set entity type for this mapper.
   *
   * This should be set in initialization. A mapper that knows its type but
   * not yet its names is still useful for router item and tab generation. The
   * concrete entity only turns out later with actual controller invocations,
   * when the setEntity() method is invoked before the rest of the methods are
   * used.
   *
   * @param string $entity_type
   *   The entity type to set.
   *
   * @return bool
   *   TRUE if the entity type was set correctly; FALSE otherwise.
   */
  public function setType($entity_type) {
    if (isset($this->entityType)) {
      return FALSE;
    }
    $this->entityType = $entity_type;
    return TRUE;
  }

  /**
   * Gets the entity type from this mapper.
   *
   * @return string
   */
  public function getType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeName() {
    $entity_type_info = $this->entityManager->getDefinition($this->entityType);
    return $entity_type_info['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    // The typeLabel is used to override the default entity type label in
    // configuration translation UI. It is used to distinguish field instances
    // from each other, but also can easily override in other mapper
    // implementations.
    if (isset($this->typeLabel)) {
      return $this->typeLabel;
    }

    $entityType = $this->entityManager->getDefinition($this->entityType);
    return $entityType['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return array(
      'list' => array(
        'title' => $this->t('List'),
        'href' => 'admin/config/regional/config-translation/' . $this->getPluginId(),
      ),
    );
  }

}
