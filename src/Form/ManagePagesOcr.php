<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use AbstractObject;

/**
 * Form for children OCR.
 */
class ManagePagesOcr extends FormBase {

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
    return 'islandora_paged_content_manage_pages_ocr_form';
  }

  /**
   * Derives the OCR datastreams in each child page.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/batch');

    $form_state->setStorage(['object' => $object]);
    $can_derive = FALSE;
    $languages = [];
    if ($this->moduleHandler->moduleExists('islandora_ocr')) {
      module_load_include('inc', 'islandora_ocr', 'includes/utilities');
      $can_derive = islandora_ocr_can_derive_ocr();
      $languages = islandora_ocr_get_enabled_tesseract_languages();
    }
    unset($languages['no_ocr']);
    return [
      'description' => [
        '#type' => 'item',
        '#description' => $this->t('You must have <b>Tesseract</b> installed to perform OCR.<br/> This will update the OCR and HOCR datastreams for each Page object.'),
      ],
      'language' => [
        '#access' => $can_derive,
        '#title' => $this->t('Language'),
        '#type' => 'select',
        '#description' => $this->t('Please select the language the pages are written in.'),
        '#options' => $languages,
      ],
      'aggregate_ocr' => [
        '#access' => $can_derive,
        '#type' => 'checkbox',
        '#title' => $this->t('Aggregate OCR to the Parent'),
        '#description' => $this->t('Check this to aggregate a consolidated OCR datastream generated from each page and append it to the current object.'),
        '#default_value' => FALSE,
      ],
      'submit' => [
        '#disabled' => !$can_derive,
        '#type' => 'submit',
        '#value' => $this->t('Create OCR'),
      ],
    ];
  }

  /**
   * Triggers a batch to derive the OCR datastreams in each page object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = $form_state->getStorage()['object'];
    $pages = array_keys(islandora_paged_content_get_pages($object));
    $options = [
      'language' => $form_state->getValue('language'),
      'preprocess' => FALSE,
      'aggregate_ocr' => $form_state['values']['aggregate_ocr'],
    ];
    $batch = islandora_paged_content_create_ocr_batch($object, $pages, $options);
    batch_set($batch);
  }

}
