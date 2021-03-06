<?php

/**
 * @file
 * Contains \Drupal\config_translation\Tests\ConfigMapperManagerTest.
 */

namespace Drupal\config_translation\Tests {

use Drupal\config_translation\ConfigMapperManager;
use Drupal\Core\Language\Language;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests ConfigMapperManager.
 *
 * @group Drupal
 * @group Config_translation
 */
class ConfigMapperManagerTest extends UnitTestCase {

  /**
   * The configuration mapper manager to test.
   *
   * @var \Drupal\config_translation\ConfigMapperManager
   */
  protected $configMapperManager;

  /**
   * The typed configuration manager used for testing.
   *
   * @var \Drupal\Core\Config\TypedConfigManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $typedConfigManager;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Configuration translation mapper manager',
      'description' => 'Tests the functionality provided by configuration translation mapper manager.',
      'group' => 'Configuration Translation',
    );
  }

  public function setUp() {
    $language = new Language(array('id' => 'en'));
    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManager');
    $language_manager->expects($this->once())
      ->method('getLanguage')
      ->with(Language::TYPE_INTERFACE)
      ->will($this->returnValue($language));

    $this->typedConfigManager = $this->getMockBuilder('Drupal\Core\Config\TypedConfigManager')
      ->disableOriginalConstructor()
      ->getMock();

    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $module_handler->expects($this->once())
      ->method('getModuleList')
      ->with()
      ->will($this->returnValue(array()));

    $this->configMapperManager = new TestConfigMapperManager(
      $this->getMock('Drupal\Core\Cache\CacheBackendInterface'),
      $language_manager,
      $module_handler,
      $this->typedConfigManager
    );
  }

  /**
   * Tests ConfigMapperManager::hasTranslatable().
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $element
   *   The schema element to test.
   * @param bool $expected
   *   The expected return value of ConfigMapperManager::hasTranslatable().
   *
   * @dataProvider providerTestHasTranslatable
   */
  public function testHasTranslatable(TypedDataInterface $element, $expected) {
    $this->typedConfigManager
      ->expects($this->once())
      ->method('get')
      ->with('test')
      ->will($this->returnValue($element));

    $result = $this->configMapperManager->hasTranslatable('test');
    $this->assertSame($expected, $result);
  }

  /**
   * Provides data for ConfigMapperManager::testHasTranslatable()
   *
   * @return array
   *   An array of arrays, where each inner array contains the schema element
   *   to test as the first key and the expected result of
   *   ConfigMapperManager::hasTranslatable() as the second key.
   */
  public function providerTestHasTranslatable() {
    return array(
      array($this->getElement(array()), FALSE),
      array($this->getElement(array('aaa' => 'bbb')), FALSE),
      array($this->getElement(array('translatable' => FALSE)), FALSE),
      array($this->getElement(array('translatable' => TRUE)), TRUE),
      array($this->getNestedElement(array(
        $this->getElement(array()),
      )), FALSE),
      array($this->getNestedElement(array(
        $this->getElement(array('translatable' => TRUE)),
      )), TRUE),
      array($this->getNestedElement(array(
        $this->getElement(array('aaa' => 'bbb')),
        $this->getElement(array('ccc' => 'ddd')),
        $this->getElement(array('eee' => 'fff')),
      )), FALSE),
      array($this->getNestedElement(array(
        $this->getElement(array('aaa' => 'bbb')),
        $this->getElement(array('ccc' => 'ddd')),
        $this->getElement(array('translatable' => TRUE)),
      )), TRUE),
      array($this->getNestedElement(array(
        $this->getElement(array('aaa' => 'bbb')),
        $this->getNestedElement(array(
          $this->getElement(array('ccc' => 'ddd')),
          $this->getElement(array('eee' => 'fff')),
        )),
        $this->getNestedElement(array(
          $this->getElement(array('ggg' => 'hhh')),
          $this->getElement(array('iii' => 'jjj')),
        )),
      )), FALSE),
      array($this->getNestedElement(array(
        $this->getElement(array('aaa' => 'bbb')),
        $this->getNestedElement(array(
          $this->getElement(array('ccc' => 'ddd')),
          $this->getElement(array('eee' => 'fff')),
        )),
        $this->getNestedElement(array(
          $this->getElement(array('ggg' => 'hhh')),
          $this->getElement(array('translatable' => TRUE)),
        )),
      )), TRUE),
    );
  }

  /**
   * Returns a mocked schema element.
   *
   * @param array $definition
   *   The definition of the schema element.
   *
   * @return \Drupal\Core\Config\Schema\Element
   *   The mocked schema element.
   */
  protected function getElement(array $definition) {
    $element = $this->getMock('Drupal\Core\TypedData\TypedDataInterface');
    $element->expects($this->any())
      ->method('getDefinition')
      ->will($this->returnValue($definition));
    return $element;
  }

  /**
   * Returns a mocked nested schema element.
   *
   * @param array $elements
   *   An array of simple schema elements.
   *
   * @return \Drupal\Core\Config\Schema\Mapping
   *   A nested schema element, containing the passed-in elements.
   */
  protected function getNestedElement(array $elements) {
    // ConfigMapperManager::findTranslatable() checks for the abstract class
    // \Drupal\Core\Config\Schema\ArrayElement, but mocking that directly does
    // not work.
    $nested_element = $this->getMockBuilder('Drupal\Core\Config\Schema\Mapping')
      ->disableOriginalConstructor()
      ->getMock();
    $nested_element->expects($this->once())
      ->method('getIterator')
      ->will($this->returnValue(new \ArrayIterator($elements)));
    return $nested_element;
  }

}

/**
 * Subclass of the tested class to avoid global function calls.
 *
 * @todo Remove this once https://drupal.org/node/2109287 is fixed in core.
 */
class TestConfigMapperManager extends ConfigMapperManager {

  /**
   * {@inheritdoc}
   */
  protected function getThemeList($refresh = FALSE) {
    return array();
  }
}

}

// @todo Remove this once https://drupal.org/node/2109287 is fixed in core.
namespace {
  if (!function_exists('drupal_get_path')) {
    function drupal_get_path() {}
  }
}
