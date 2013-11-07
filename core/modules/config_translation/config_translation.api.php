<?php

/**
 * @file
 * Hooks provided by the Configuration Translation module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Introduce dynamic translation tabs for translation of configuration.
 *
 * This hook augments MODULE.config_translation.yml as well as
 * THEME.config_translation.yml files to collect dynamic translation mapper
 * information. If your information is static, just provide such a YAML file
 * with your module containing the mapping.
 *
 * Note that while themes can provide THEME.config_translation.yml files this
 * hook is not invoked for themes.
 *
 * @param array $info
 *   An associative array of configuration mapper information. Use an entity
 *   name for the key (for entity mapping) or a unique string for configuration
 *   name list mapping. The values of the associative array are arrays
 *   themselves in the same structure as the *.configuration_translation.yml
 *   files.
 *
 * @see hook_config_translation_info_alter()
 * @see \Drupal\config_translation\ConfigMapperManagerInterface
 * @see \Drupal\config_translation\Routing\RouteSubscriber::routes()
 */
function hook_config_translation_info(&$info) {
  // Add fields entity mappers to all fieldable entity types defined.
  $entity_manager = \Drupal::entityManager();
  foreach ($entity_manager->getDefinitions() as $entity_type => $entity_info) {
    // Make sure entity type is fieldable and has base path.
    if ($entity_info['fieldable'] && isset($entity_info['route_base_path'])) {
      $info[$entity_type . '_fields'] = array(
        'base_path' => $entity_info['route_base_path'] . '/fields/{field_instance}',
        'route_name' => 'config_translation.item.field_ui.instance_edit_' . $entity_type,
        'entity_type' => 'field_instance',
        'title' => t('!label field'),
        'class' => '\Drupal\config_translation\ConfigEntityMapper',
        'base_entity_type' => $entity_type,
        'list_controller' => '\Drupal\config_translation\Controller\ConfigTranslationFieldInstanceListController',
      );
    }
  }
}

/**
 * Alter existing translation tabs for translation of configuration.
 *
 * This hook is useful to extend existing configuration mappers with new
 * configuration names, for example when altering existing forms with new
 * settings stored elsewhere. This allows the translation experience to also
 * reflect the compound form element in one screen.
 *
 * @param array $info
 *   An associative array of discovered configuration mappers. Use an entity
 *   name for the key (for entity mapping) or a unique string for configuration
 *   name list mapping. The values of the associative array are arrays
 *   themselves in the same structure as the *.configuration_translation.yml
 *   files.
 *
 * @see hook_translation_info()
 * @see \Drupal\config_translation\ConfigMapperManagerInterface
 */
function hook_config_translation_info_alter(&$info) {
  // Add additional site settings to the site information screen, so it shows
  // up on the translation screen. (Form alter in the elements whose values are
  // stored in this config file using regular form altering on the original
  // configuration form.)
  $info['site_information']['names'][] = 'example.site.setting';
}

/**
 * Alter config typed data definitions.
 *
 * Used to automatically generate translation forms, you can alter the typed
 * data types representing each configuration schema type to change default
 * labels or form element renderers.
 *
 * @param $definitions
 *   Associative array of configuration type definitions keyed by schema type
 *   names. The elements are themselves array with information about the type.
 */
function hook_config_translation_type_info_alter(&$definitions) {
  // Enhance the text and date type definitions with classes to generate proper
  // form elements in ConfigTranslationFormBase. Other translatable types will
  // appear as a one line textfield.
  $definitions['text']['form_element_class'] = '\Drupal\config_translation\FormElement\Textarea';
  $definitions['date_format']['form_element_class'] = '\Drupal\config_translation\FormElement\DateFormat';
}

/**
 * @} End of "addtogroup hooks".
 */
