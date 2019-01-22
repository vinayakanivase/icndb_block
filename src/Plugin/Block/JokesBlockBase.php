<?php

namespace Drupal\icndb_block\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class JokesBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  /** @var \Drupal\icndb_block\ICNDbHttpClient */
  protected $icndbClient;

  /**
   * The uri for the HTTP GET request.
   *
   * @var string
   */
  protected $uri;

  /**
   * The options for the HTTP GET request.
   *
   * @var array
   */
  protected $options = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $icndb_client) {
    $this->icndbClient = $icndb_client;
    parent::__construct( $configuration, $plugin_id, $plugin_definition );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('icndb_block.http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // How many jokes to be shown.
      'quantity' => 1,

      // Rename main character.
      'renaming' => [
        'enable' => 0,
        'first_name' => 'Chuck',
        'last_name' => 'Norris',
      ],

      // Escape special characters.
      'escaping' => [
        'enable' => 0,
        'format' => 'html',
      ],

      // Filter jokes by categories.
      'filtering' => [
        'enable' => 0,
        'type' => 'inclusive',
        'inclusive' => ['nerdy' => 'nerdy'],
        'exclusive' => ['explicit' => 'explicit'],
      ],
    ];
  }

  /**
   * Get the list of joke categories.
   *
   * @return array
   */
  public function getCategories() {
    $response = $this->icndbClient->get('categories');
    $categories = Json::decode($response->getBody());

    if ($categories['type'] === 'success') {
      return array_combine($categories['value'], $categories['value']);
    }

    // Fallback values.
    return array_combine(['nerdy', 'explicit'], ['nerdy', 'explicit']);
  }

  /**
   * Get the jokes count.
   *
   * @return int
   */
  public function getCount() {
    $response = $this->icndbClient->get('jokes/count');
    $count = Json::decode($response->getBody());

    if ($count['type'] === 'success') {
      return $count['value'];
    }

    // Fallback value
    return 0;
  }

  protected function quantityForm(&$form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    // Total number of jokes found on the API server.
    $count = ($this->getCount() > 0) ? $this->getCount() : 500;

    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#description' => $this->t('Total jokes available at the API: @count', [
        '@count' => $count
      ]),
      '#min' => 1,
      '#max' => $count,
      '#default_value' => $config['quantity'],
    ];

    return $form;
  }

  protected function renameForm(&$form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    // Wrapper element.
    $form['renaming'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t("Rename main character"),
      '#description' => $this->t("The ICNDb API allows renaming the main character when fetching a joke."),
    ];

    // Switch the functionality on or off.
    $form['renaming']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable main character renaming'),
      '#default_value' => $config['renaming']['enable'],
    ];

    // First name for the main character.
    $form['renaming']['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Fisrt name"),
      '#maxlength' => 255,
      '#default_value' => $config['renaming']['first_name'],
      '#states' => [
        'visible' => [
          ':input[name="settings[renaming][enable]"]' => [
            'checked' => TRUE,
          ]
        ]
      ],
    ];

    // Last name for the main character.
    $form['renaming']['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Last name"),
      '#maxlength' => 255,
      '#default_value' => $config['renaming']['last_name'],
      '#states' => [
        'visible' => [
          ':input[name="settings[renaming][enable]"]' => [
            'checked' => TRUE,
          ]
        ]
      ],
    ];

    return $form;
  }

  protected function escapeForm(&$form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    // Escape special characters.
    $form['escaping'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t("Escaping special characters"),
      '#description' => $this->t("Some jokes contain some special characters such as “, & or <. These special characters can make it hard to show the joke correctly or can even ruin the syntax of the result."),
    ];

    // Switch the functionality on or off.
    $form['escaping']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable special character escaping'),
      '#default_value' => $config['escaping']['enable'],
    ];

    // The format for escaping special characters.
    $form['escaping']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Escape format'),
      '#options' => [
        'html' => 'HTML',
        'javascript' => 'JavaScript',
      ],
      '#default_value' => $config['escaping']['format'],
      '#states' => [
        'visible' => [
          ':input[name="settings[escaping][enable]"]' => [
            'checked' => TRUE,
          ]
        ]
      ],
    ];

    return $form;
  }

  protected function filterForm(&$form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    // Filtering the results.
    $form['filtering'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t("Filter results by categories"),
      '#description' => $this->t("The jokes in the database are given categories such as 'nerdy or 'explicit'. When fetching multiple jokes, it is possible to limit the scope to some of these categories or exclude some of them."),
    ];

    // Switch the functionality on or off.
    $form['filtering']['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable filtering by categories'),
      '#default_value' => $config['filtering']['enable'],
    ];

    // The type of filtering (inclusive or exclusive).
    $form['filtering']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Filtering type'),
      '#options' => [
        'inclusive' => 'Inclusive',
        'exclusive' => 'Exclusive',
      ],
      '#default_value' => $config['filtering']['type'],
      '#states' => [
        'visible' => [
          ':input[name="settings[filtering][enable]"]' => ['checked' => TRUE],
        ]
      ],
    ];

    // Categories to be included
    $form['filtering']['inclusive'] = [
      '#type' => 'select',
      '#title' => $this->t('Include results from'),
      '#options' => $this->getCategories(),
      '#multiple' => TRUE,
      '#default_value' => $config['filtering']['inclusive'],
      '#states' => [
        'visible' => [
          ':input[name="settings[filtering][enable]"]' => ['checked' => TRUE],
          ':input[name="settings[filtering][type]"]' => ['value' => 'inclusive'],
        ],
      ],
    ];

    // Categories to be excluded
    $form['filtering']['exclusive'] = [
      '#type' => 'select',
      '#title' => $this->t('Exclude results from'),
      '#options' => $this->getCategories(),
      '#multiple' => TRUE,
      '#default_value' => $config['filtering']['exclusive'],
      '#states' => [
        'visible' => [
          ':input[name="settings[filtering][enable]"]' => ['checked' => TRUE],
          ':input[name="settings[filtering][type]"]' => ['value' => 'exclusive'],
        ],
      ],
    ];

    return $form;
  }

}
