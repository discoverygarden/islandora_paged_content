<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;

use Symfony\Component\DependencyInjection\ContainerInterface;

use AbstractObject;

/**
 * Updates this objects OCR datastreams.
 */
class ManagePageEditOcr extends FormBase {

  protected $moduleHandler;
  protected $renderer;

  /**
   * Constructor.
   */
  public function __construct(ModuleHandlerInterface $module_handler, RendererInterface $renderer) {
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_manage_page_edit_ocr_form';
  }

  /**
   * Updates this objects OCR datastreams.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
    $form_state->setStorage(['object' => $object->id]);
    $pid = $object->id;
    $jpg_ds = $object['JPG'];
    $dimensions = $jpg_ds->relationships->get();
    // Safety value if rels-int doesn't exist.  This should never be necessary.
    $width = 500;
    if (is_array($dimensions)) {
      foreach ($dimensions as $dimension) {
        if ($dimension['predicate']['value'] == 'width') {
          $width = $dimension['object']['value'] + 25;
        }
      }
    }

    $form['#attached']['drupalSettings']['islandora_paged_content']['edit_ocr'] = [
      'pid' => $pid,
      'width' => $width,
    ];
    $form['#attached']['library'][] = 'islandora_paged_content/edit-ocr';
    $img = [
      '#theme' => 'image',
      '#uri' => Url::fromRoute('islandora.view_datastream_view', ['object' => $pid, 'datastream' => 'JPG'])->toString(),
      '#attributes' => [
        'id' => 'source_image',
      ],
    ];
    $img = $this->renderer->render($img);
    $label = $object->label;
    $prompt = $this->t('Show reference image');
    $form['image'] = [
      '#title' => $this->t('Reference Image'),
      '#type' => 'markup',
      '#markup' => "<div id ='ref_image' title='$prompt'><a class='preview' href='#'>$prompt</a> <div class='ref_image' title = '$label'>$img</div>",
      '#attributes' => ['class' => ['ocr_image']],
    ];

    $form['ocr'] = [
      '#title' => $this->t('Current OCR'),
      '#type' => 'textarea',
      '#default_value' => $object['OCR']->content,
      '#rows' => 40,
      '#cols' => 20,
      '#attributes' => ['class' => ['ocr_window']],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update OCR'),
    ];

    return $form;
  }

  /**
   * Submit handler for the edit form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->getStorage()['object']);
    $success = TRUE;
    try {
      $object["OCR"]->content = $form_state->getValue('ocr');
    }
    catch (Exception $e) {
      $success = FALSE;
      drupal_set_message($this->t("OCR update failed."));
    }
    if ($success) {
      drupal_set_message($this->t("OCR successfully updated."));
    }
  }

}
