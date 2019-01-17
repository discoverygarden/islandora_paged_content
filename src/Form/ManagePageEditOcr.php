<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use AbstractObject;

/**
 * Updates this objects OCR datastreams.
 */
class ManagePageEditOcr extends FormBase {

  protected $moduleHandler;

  /**
   * Constructor.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
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
    // @FIXME
  // The Assets API has totally changed. CSS, JavaScript, and libraries are now
  // attached directly to render arrays using the #attached property.
  //
  //
  // @see https://www.drupal.org/node/2169605
  // @see https://www.drupal.org/node/2408597
  // drupal_add_library('system', 'ui.dialog');

    $path = drupal_get_path('module', 'islandora_paged_content');
    // @FIXME
  // The Assets API has totally changed. CSS, JavaScript, and libraries are now
  // attached directly to render arrays using the #attached property.
  //
  //
  // @see https://www.drupal.org/node/2169605
  // @see https://www.drupal.org/node/2408597
  // drupal_add_js(array('pid' => $pid, 'width' => $width), 'setting');

    // @FIXME
  // The Assets API has totally changed. CSS, JavaScript, and libraries are now
  // attached directly to render arrays using the #attached property.
  //
  //
  // @see https://www.drupal.org/node/2169605
  // @see https://www.drupal.org/node/2408597
  // drupal_add_js("$path/js/islandora_page_ocr_edit.js");

    // @FIXME
  // The Assets API has totally changed. CSS, JavaScript, and libraries are now
  // attached directly to render arrays using the #attached property.
  //
  //
  // @see https://www.drupal.org/node/2169605
  // @see https://www.drupal.org/node/2408597
  // drupal_add_css("$path/css/islandora_paged_content.css");


    // @FIXME
  // theme() has been renamed to _theme() and should NEVER be called directly.
  // Calling _theme() directly can alter the expected output and potentially
  // introduce security issues (see https://www.drupal.org/node/2195739). You
  // should use renderable arrays instead.
  //
  //
  // @see https://www.drupal.org/node/2195739
  // $img = theme('image', array(
  //     'path' => "islandora/object/$pid/datastream/JPG/view",
  //     'alt' => $object->label,
  //     'attributes' => array('id' => 'source_image'),
  //   ));

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
