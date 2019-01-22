<?php

namespace Drupal\icndb_block;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ICNDbClient.
 */
class ICNDbHttpClient implements ContainerInjectionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The http client factory.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The http client factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   *   The config factory service.
   * @var \Drupal\Core\Http\ClientFactory
   *   The http client factory service.
   */
  public function __construct($config_factory, $http_client_factory) {
    $this->configFactory = $config_factory;
    $this->httpClientFactory = $http_client_factory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client_factory')
    );
  }

  /**
   * Retrieves a configuration object.
   *
   * @param string $name
   *   The name of the configuration object to retrieve.
   *
   * @return \Drupal\Core\Config\Config
   *   A configuration object.
   */
  protected function config($name) {
    return $this->configFactory->get($name);
  }

  /**
   * Returns the http client service.
   *
   * @return \GuzzleHttp\Client
   */
  protected function httpClient() {
    return $this->httpClientFactory->fromOptions(
      $this->config('icndb_block.http_client')->get('options')
    );
  }

  /**
   * An HTTP GET request.
   *
   * @param string $uri
   *   The uri for the request.
   * @param array $options
   *   An associative array of options, such as 'query'. The query option let's
   *   you add query parameters to the request uri.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *
   * @see http://docs.guzzlephp.org/en/stable/request-options.html
   */
  public function get(string $uri, array $options = []) {
    return $this->httpClient()->get($uri, $options);
  }

}
