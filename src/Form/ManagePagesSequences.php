<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use AbstractObject;

/**
 * Form for reordering the child pages.
 */
class ManagePagesSequences extends FormBase {

  protected $renderer;

  /**
   * Constructor.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * Dependency Injection.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Injected container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_manage_pages_sequences_form';
  }

  /**
   * Form for reordering the child pages.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    // Cache the generated rows, as this build function gets called for every
    // ajax callback.
    if (isset($form_state->getStorage()['rows'])) {
      $rows = $form_state->getStorage()['rows'];
    }
    else {
      $islandora_path = drupal_get_path('module', 'islandora');
      $rows = [];
      $pages = islandora_paged_content_get_pages($object);
      foreach ($pages as $page) {
        $id = $page['pid'];
        $img = [
          '#theme' => 'image',
          '#uri' => islandora_datastream_access(ISLANDORA_VIEW_OBJECTS, $object['TN'] ?
            Url::fromRoute('islandora.view_datastream_view', ['object' => $id, 'datastream' => 'TN'])->toString() :
            "$islandora_path/images/folder.png"),
          '#attributes' => [
            'width' => '64px',
            'height' => '64px',
          ],
        ];
        $link = [
          '#type' => 'link',
          '#title' => $page['label'],
          '#url' => Url::fromRoute('islandora.view_object', ['object' => $id]),
        ];
        $rows[$id] = [
          'tn' => ['#markup' => $this->renderer->render($img)],
          'label' => ['#markup' => $this->renderer->render($link)],
        ];
      }
    }
    $form_state->setStorage(
      [
        'pid' => $object->id,
        'rows' => $rows,
      ]
    );
    return [
      'table' => [
        '#type' => 'swaptable',
        '#header' => [
          $this->t('Thumbnail'),
          $this->t('Label'),
        ],
        '#rows' => $rows,
      ],
      'quick_reorder' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Quick Reorder'),
        'pages_to_move' => [
          '#type' => 'textfield',
          '#title' => $this->t('Move Pages:'),
          '#description' => $this->t("Must follow the form '1, 2-4,5'."),
        ],
        'pages_insert_point' => [
          '#type' => 'textfield',
          '#title' => $this->t('Insert After:'),
        ],
        'submit' => [
          '#validate' => ['islandora_paged_content_quick_reorder_validate'],
          '#type' => 'submit',
          '#submit' => ['islandora_paged_content_quick_reorder_submit'],
          '#value' => $this->t('Go'),
        ],
      ],
      'actions' => [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Save Changes'),
        ],
      ],
    ];
  }

  /**
   * Submit handler for the sequence form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->getStorage()['pid']);
    $pages = $form_state->getValue('table')['order'];
    if ($pages) {
      $batch = islandora_paged_content_sequence_pages_batch($object, $pages);
      batch_set($batch);
    }
  }

}
