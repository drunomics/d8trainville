<?php

/**
 * @file
 * Contains \Drupal\common_test\Controller\CommonTestController.
 */

namespace Drupal\common_test\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for common_test routes.
 */
class CommonTestController implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Returns links to the current page, with and without query strings.
   *
   * Using #type 'link' causes these links to be rendered with l().
   */
  public function typeLinkActiveClass() {
    return array(
      'no_query' => array(
        '#type' => 'link',
        '#title' => t('Link with no query string'),
        '#href' => current_path(),
      ),
      'with_query' => array(
        '#type' => 'link',
        '#title' => t('Link with a query string'),
        '#href' => current_path(),
        '#options' => array(
          'query' => array(
            'foo' => 'bar',
            'one' => 'two',
          ),
        ),
      ),
      'with_query_reversed' => array(
        '#type' => 'link',
        '#title' => t('Link with the same query string in reverse order'),
        '#href' => current_path(),
        '#options' => array(
          'query' => array(
            'one' => 'two',
            'foo' => 'bar',
          ),
        ),
      ),
    );
  }

  /**
   * Renders an element with an invalid render array key.
   *
   * @return array
   *   A render array.
   */
  public function drupalRenderInvalidKeys() {
    define('SIMPLETEST_COLLECT_ERRORS', FALSE);
    // Keys that begin with # may contain a value of any type, otherwise they must
    // contain arrays.
    $element = array('child' => 'This should be an array.');
    return drupal_render($element);
  }

  /**
   * Adds a JavaScript file and a CSS file with a query string appended.
   *
   * @return string
   *   An empty string.
   */
  public function jsAndCssQuerystring() {
    drupal_add_library('system', 'drupalSettings');
    drupal_add_js(drupal_get_path('module', 'node') . '/node.js');
    drupal_add_css(drupal_get_path('module', 'node') . '/css/node.admin.css');
    // A relative URI may have a query string.
    drupal_add_css('/' . drupal_get_path('module', 'node') . '/node-fake.css?arg1=value1&arg2=value2');
    return '';
  }

  /**
   * Prints a destination query parameter.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A new Response object containing a string with the destination query
   *   parameter.
   */
  public function destination() {
    $destination = drupal_get_destination();
    $output = "The destination: " . String::checkPlain($destination['destination']);

    return new Response($output);
  }

}
