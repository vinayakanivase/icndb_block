<?php

namespace Drupal\icndb_block\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RandomJokesBlock.
 *
 * @Block(
 *   id = "icndb_block_random_jokes",
 *   admin_label = @Translation("ICNDb Block: Random Jokes")
 * )
 */
class RandomJokesBlock extends JokesBlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // The uri for the request.
    // e.g. http://api.icndb.com/jokes/random
    $this->uri = 'jokes/random';

    // If quantity is greater than one, append the integer to the uri.
    // e.g. http://api.icndb.com/jokes/random/2
    if ($config['quantity'] > 1) {
      $this->uri = $this->uri . '/' . $config['quantity'];
    }

    // Renaming the main character.
    // e.g. http://api.icndb.com/jokes/random?firstName=john&lastName=smith
    $renaming = $config['renaming'];

    if ($renaming['enable']) {

      // e.g. http://api.icndb.com/jokes/random?firstName=john
      if ($renaming['first_name']) {
        $this->options['query']['firstName'] = $renaming['first_name'];
      }

      // e.g. http://api.icndb.com/jokes/random?lastName=smith
      if ($renaming['last_name']) {
        $this->options['query']['lastName'] = $renaming['last_name'];
      }
    }

    // Escaping special characters.
    // e.g. http://api.icndb.com/jokes/random?escape=javascript
    $escaping = $config['escaping'];

    if ($escaping['enable']) {
      $this->options['query']['escape'] = $escaping['format'];
    }

    // Filtering results by categories.
    $filtering = $config['filtering'];

    // e.g. http://api.icndb.com/jokes/random?limitTo=[nerdy,explicit]
    if ($filtering['enable']) {
      if ($filtering['type'] === 'inclusive') {
        $this->options['query']['limitTo'] = '[' . implode(',', $filtering['inclusive']) . ']';
      }

      // e.g. http://api.icndb.com/jokes/random?exclude=[explicit]
      if ($filtering['type'] === 'exclusive') {
        $this->options['query']['exclude'] = '[' . implode(',', $filtering['exclusive']) . ']';
      }
    }

    // Send GET request.
    $response = $this->icndbClient->get($this->uri, $this->options);
    $data = Json::decode($response->getBody());

    $build = [];

    // If error occurred, show an error message.
    if ($data['type'] !== 'success') {
      $build[] = [
        '#markup' => $this->t('Something went wrong.')
      ];
    }

    // If quantity is greater than one, show the items as ordered list.
    if ($config['quantity'] > 1) {
      $jokes = [];

      foreach ($data['value'] as $value) {
        $jokes[] = $value['joke'];
      }

      $build[] = [
        '#theme' => 'item_list',
        '#items' => $jokes,
        '#list_type' => 'ol'
      ];
    }
    else {
      $build[] = [
        '#markup' => $data['value']['joke'],
        '#prefix' => '<blockquote>',
        '#suffix' => '</blockquote>'
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Total number of jokes to be fetched.
    $this->quantityForm($form, $form_state);

    // Rename the main character.
    $this->renameForm($form, $form_state);

    // Escape special characters.
    $this->escapeForm($form, $form_state);

    // Filter results by categories.
    $this->filterForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    // Set an error if the quantity is greater than jokes count.
    $count = $this->getCount();
    if ($count > 0 && $form_state->getValue('quantity') > $count) {
      $form_state->setError(
        $form['quantity'],
        $this->t('Quantity must be lower than or equal to @count.', ['@count' => $count])
      );
    }

    // Set an error if the first name and/or last name field contains non-alphabetic characters.
    if ($form_state->getValue(['renaming', 'enable'])) {
      if (!preg_match('/[a-zA-Z]+/', $form_state->getValue(['renaming', 'first_name']))) {
        $form_state->setError(
          $form['renaming']['first_name'],
          "First name must be lowercase or uppercase letters."
        );
      }

      if (!preg_match('/[a-zA-Z]+/', $form_state->getValue(['renaming', 'last_name']))) {
        $form_state->setError(
          $form['renaming']['last_name'],
          "Last name must be lowercase or uppercase letters."
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Quantity
    $this->configuration['quantity'] = $form_state->getValue('quantity');

    // Renaming
    $this->configuration['renaming'] = [
      'enable' => $form_state->getValue(['renaming', 'enable']),
      'first_name' => $form_state->getValue(['renaming', 'first_name']),
      'last_name' => $form_state->getValue(['renaming', 'last_name']),
    ];

    // Escaping
    $this->configuration['escaping'] = [
      'enable' => $form_state->getValue(['escaping', 'enable']),
      'format' => $form_state->getValue(['escaping', 'format']),
    ];

    // Filtering
    $filter_type = $form_state->getValue(['filtering', 'type']);

    $this->configuration['filtering'] = [
      'enable' => $form_state->getValue(['filtering', 'enable']),
      'filter_type' => $form_state->getValue(['filtering', 'type']),
    ];

    if ($filter_type === 'inclusive') {
      $this->configuration['filtering']['inclusive'] =
        $form_state->getValue(['filtering', 'inclusive']);
    }

    if ($filter_type === 'exclusive') {
      $this->configuration['filtering']['exclusive'] =
        $form_state->getValue(['filtering', 'exclusive']);
    }
  }

}
