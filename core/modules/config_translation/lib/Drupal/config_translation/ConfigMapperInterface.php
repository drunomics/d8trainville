<?php

/**
 * @file
 * Contains \Drupal\config_translation\ConfigMapperInterface.
 */

namespace Drupal\config_translation;

use Drupal\Core\Language\Language;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines an interface for configuration mapper.
 */
interface ConfigMapperInterface {

  /**
   * Returns title of this translation page.
   *
   * @return string
   *   The page title.
   */
  public function getTitle();

  /**
   * Returns the name of the base route the mapper is attached to.
   *
   * @return string
   *   The name of the base route the mapper is attached to.
   */
  public function getBaseRouteName();

  /**
   * Returns the base route object the mapper is attached to.
   *
   * @return \Symfony\Component\Routing\Route
   *   The base route object the mapper is attached to.
   */
  public function getBaseRoute();

  /**
   * Returns route name for the mapper.
   *
   * @return string
   *   Route name for the mapper.
   */
  public function getRouteName();

  /**
   * Returns the route parameters for the translation route.
   *
   * @return array
   */
  public function getRouteParameters();

  /**
   * Returns the route object for a translation page.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route object for the translation page.
   */
  public function getRoute();

  /**
   * Returns a processed path for the base page.
   *
   * @return string
   *   Processed path with placeholders replaced.
   */
  public function getBasePath();

  /**
   * Returns an array of configuration names for the mapper.
   *
   * @return array
   *   An array of configuration names for the mapper.
   */
  public function getConfigNames();

  /**
   * Adds the given configuration name to the list of names.
   *
   * @param string $name
   *   Configuration name.
   */
  public function addConfigName($name);

  /**
   * Returns the weight of the mapper.
   *
   * @return int
   *   The weight of the mapper.
   */
  public function getWeight();

  /**
   * Returns an array with all configuration data.
   *
   * @return array
   *   Configuration data keyed by configuration names.
   */
  public function getConfigData();

  /**
   * Returns the original language code of the configuration.
   *
   * @throws \RuntimeException
   *   Throws an exception if the language codes in the config files don't
   *   match.
   */
  public function getLangcode();

  /**
   * Returns language object for the configuration.
   *
   * If the language of the configuration files is not a configured language on
   * the site and it is English, we return a dummy language object to represent
   * the built-in language.
   *
   * @return \Drupal\Core\Language\Language
   *   A configured language object instance or a dummy English language object.
   *
   * @throws \RuntimeException
   *   Throws an exception if the language codes in the config files don't
   *   match.
   */
  public function getLanguageWithFallback();

  /**
   * Returns the name of the type of data the mapper encapsulates.
   *
   * @return string
   *   The name of the type of data the mapper encapsulates.
   */
  public function getTypeName();

  /**
   * Provides an array of information to build a list of operation links.
   *
   * @return array
   *   An associative array of operation link data for this list, keyed by
   *   operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - href: The path for the operation.
   *   - options: An array of URL options for the path.
   *   - weight: The weight of this operation.
   */
  public function getOperations();

  /**
   * Returns the label of the type of data the mapper encapsulates.
   *
   * @return string
   *   The label of the type of data the mapper encapsulates.
   */
  public function getTypeLabel();

  /**
   * Checks that all pieces of this configuration mapper have a schema.
   *
   * @return bool
   *   TRUE if all of the elements have schema, FALSE otherwise.
   */
  public function hasSchema();

  /**
   * Checks that all pieces of this configuration mapper have translatables.
   *
   * @return bool
   *   TRUE if all of the configuration elements have translatables, FALSE
   *   otherwise.
   */
  public function hasTranslatable();

  /**
   * Checks whether there is already a translation for this mapper.
   *
   * @param \Drupal\Core\Language\Language $language
   *   A language object.
   *
   * @return bool
   *   TRUE if any of the configuration elements have a translation in the
   *   given language, FALSE otherwise.
   */
  public function hasTranslation(Language $language);

  /**
   * Populate the config mapper with request data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request object.
   */
  public function populateFromRequest(Request $request);

}
