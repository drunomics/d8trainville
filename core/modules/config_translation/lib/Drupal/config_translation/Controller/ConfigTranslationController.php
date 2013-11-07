<?php

/**
 * @file
 * Contains \Drupal\config_translation\Controller\ConfigTranslationController.
 */

namespace Drupal\config_translation\Controller;

use Drupal\config_translation\ConfigMapperManagerInterface;
use Drupal\Core\Access\AccessManager;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Provides page callbacks for the configuration translation interface.
 */
class ConfigTranslationController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Configuration mapper manager.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $configMapperManager;

 /**
   * The menu link access service.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

 /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The path processor service.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a ConfigTranslationController.
   *
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The configuration mapper manager.
   * @param \Drupal\Core\Access\AccessManager $access_manager
   *   The menu link access service.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The dynamic router service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(ConfigMapperManagerInterface $config_mapper_manager, AccessManager $access_manager, RequestMatcherInterface $router, InboundPathProcessorInterface $path_processor, FormBuilderInterface $form_builder, AccountInterface $account) {
    $this->configMapperManager = $config_mapper_manager;
    $this->accessManager = $access_manager;
    $this->router = $router;
    $this->pathProcessor = $path_processor;
    $this->formBuilder = $form_builder;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.config_translation.mapper'),
      $container->get('access_manager'),
      $container->get('router'),
      $container->get('path_processor_manager'),
      $container->get('form_builder'),
      $container->get('current_user')
    );
  }

  /**
   * Language translations overview page for a configuration name.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request object.
   * @param array $mapper_plugin
   *  An array of plugin details with the following keys:
   *  - plugin_id: The plugin ID of the mapper.
   *  - plugin_definition: An array of mapper details with the following keys:
   *    - base_path_pattern: Base path pattern to attach
   *      the translation user interface to.
   *    - title: The title for translation editing screen.
   *    - names: The list of configuration names for this mapper.
   *    - entity_type: (optional) The type of the entity.
   *
   * @return array
   *   Page render array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception if the language code provided as a query parameter in
   *   the request does not match an active language.
   */
  public function itemPage(Request $request, array $mapper_plugin) {
    /** @var \Drupal\config_translation\ConfigMapperInterface $mapper */
    $mapper = $this->configMapperManager->createInstance($mapper_plugin['plugin_id'], $mapper_plugin['plugin_definition']);
    $mapper->populateFromRequest($request);

    // This is only necessary as we have to provide multiple forms and pages
    // on a single route due to the hook_menu() parent limitation.
    if ($request->query->has('action') && $request->query->has('langcode')) {
      switch ($request->query->get('action')) {
        case 'add':
          $class = 'Drupal\config_translation\Form\ConfigTranslationAddForm';
          break;

        case 'edit':
          $class = 'Drupal\config_translation\Form\ConfigTranslationEditForm';
          break;

        case 'delete':
          $class = 'Drupal\config_translation\Form\ConfigTranslationDeleteForm';
          break;

        default:
          throw new NotFoundHttpException();
      }

      $target_language = language_load($request->query->get('langcode'));
      if (!$target_language) {
        throw new NotFoundHttpException();
      }
      return $this->formBuilder->getForm($class, $mapper, $target_language);
    }

    $page = array();
    $page['#title'] = $this->t('Translations for %label', array('%label' => $mapper->getTitle()));

    // It is possible the original language this configuration was saved with is
    // not on the system. For example, the configuration shipped in English but
    // the site has no English configured. Represent the original language in
    // the table even if it is not currently configured.
    $languages = language_list();
    $original_langcode = $mapper->getLangcode();
    if (!isset($languages[$original_langcode])) {
      $language_name = language_name($original_langcode);
      if ($original_langcode == 'en') {
        $language_name = $this->t('Built-in English');
      }
      // Create a dummy language object for this listing only.
      $languages[$original_langcode] = new Language(array('id' => $original_langcode, 'name' => $language_name));
    }

    $path = $mapper->getBasePath();
    $header = array($this->t('Language'), $this->t('Operations'));
    $page['languages'] = array(
      '#type' => 'table',
      '#header' => $header,
    );
    foreach ($languages as $language) {
      if ($language->id == $original_langcode) {
        $page['languages'][$language->id]['language'] = array(
          '#markup' => '<strong>' . $this->t('@language (original)', array('@language' => $language->name)) . '</strong>',
        );

        // Check access for the path/route for editing, so we can decide to
        // include a link to edit or not.
        $route_request = $this->getRequestForPath($request, $path);
        $edit_access = FALSE;
        if (!empty($route_request)) {
          $route_name = $route_request->attributes->get(RouteObjectInterface::ROUTE_NAME);
          // Note that the parameters don't really matter here since we're
          // passing in the request which already has the upcast attributes.
          $parameters = array();
          $edit_access = $this->accessManager->checkNamedRoute($route_name, $parameters, $this->account, $route_request);
        }

        // Build list of operations.
        $operations = array();
        if ($edit_access) {
          $operations['edit'] = array(
            'title' => $this->t('Edit'),
            'href' => $path,
            'query' => array('destination' => $path . '/translate'),
          );
        }
        $page['languages'][$language->id]['operations'] = array(
          '#type' => 'operations',
          '#links' => $operations,
        );
      }
      else {
        $page['languages'][$language->id]['language'] = array(
          '#markup' => $language->name,
        );
        $operations = array();
        $path_options = array('langcode' => $language->id);
        // If no translation exists for this language, link to add one.
        if (!$mapper->hasTranslation($language)) {
          $operations['add'] = array(
            'title' => $this->t('Add'),
            'href' => $path . '/translate',
            'query' => array('action' => 'add') + $path_options,
          );
        }
        else {
          // Otherwise, link to edit the existing translation.
          $operations['edit'] = array(
            'title' => $this->t('Edit'),
            'href' => $path . '/translate',
            'query' => array('action' => 'edit') + $path_options,
          );
          $operations['delete'] = array(
            'title' => $this->t('Delete'),
            'href' => $path . '/translate',
            'query' => array('action' => 'delete') + $path_options,
          );
        }

        $page['languages'][$language->id]['operations'] = array(
          '#type' => 'operations',
          '#links' => $operations,
        );
      }
    }
    return $page;
  }

  /**
   * Matches a path in the router.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Page request object.
   * @param string $path
   *   Path to look up.
   *
   * @return \Symfony\Component\HttpFoundation\Request|null
   *   A populated request object or NULL if the patch couldn't be matched.
   */
  protected function getRequestForPath(Request $request, $path) {
    // @todo Use the RequestHelper once https://drupal.org/node/2090293 is
    //   fixed.
    $route_request = Request::create($request->getBaseUrl() . '/' . $path);
    // Find the system path by resolving aliases, language prefix, etc.
    $processed = $this->pathProcessor->processInbound($path, $route_request);
    $route_request->attributes->set('_system_path', $processed);
    // Attempt to match this path to provide a fully built request.
    try {
      $route_request->attributes->add($this->router->matchRequest($route_request));
      return $route_request;
    }
    catch (NotFoundHttpException $e) {
      return NULL;
    }
    catch (ResourceNotFoundException $e) {
      return NULL;
    }
  }

}
