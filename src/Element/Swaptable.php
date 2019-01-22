<?php

namespace Drupal\islandora_paged_content\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the swaptable form element.
 *
 * @FormElement("swaptable")
 */
class Swaptable extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#pager' => [
        // Pager ID's you may need to override this value if there are already
        // several pagers on the page.
        'element' => [
          'left' => 0,
          'right' => 1,
        ],
        'tags' => ['<<', '<', '', '>', '>>'],
        'limit' => 10,
        'quantity' => 3,
      ],
      '#prefix' => '<div class="swaptable-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => [
        'class' => ['swaptable'],
      ],
      '#process' => ['swaptable_process'],
      '#theme' => 'swaptable',
      '#theme_wrappers' => ['form_element'],
      '#attached' => [
        'library' => [
          'islandora_paged_content/swaptable',
        ],
      ],
    ];

    return $info;
  }

  /**
   * Value callback for swaptable element.
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input !== FALSE) {
      // Deserialize 'order' and 'modified' they are submitted as a sting.
      $input['order'] = explode(' ', $input['order']);
      $input['modified'] = empty($input['modified']) ? [] : explode(' ', $input['modified']);
      return $input;
    }
    return [];
  }

}
