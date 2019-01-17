<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use AbstractObject;

/**
 * Form for page OCR.
 */
class ManagePageOcr extends FormBase {

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
    return 'islandora_paged_content_manage_page_ocr_form';
  }

  /**
   * Updates this objects derived OCR datastreams.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
    $form_state->loadInclude('islandora_ocr', 'inc', 'includes/utilities');

    $form_state->setStorage(['object' => $object->id]);
    $can_derive = islandora_paged_content_can_derive($object, 'OCR');
    $languages = $this->moduleHandler->moduleExists('islandora_ocr') ? islandora_ocr_get_enabled_tesseract_languages() : ['eng' => $this->t('English')];
    unset($languages['no_ocr']);
    return [
      'description' => [
        '#type' => 'item',
        '#description' => $this->t('You must have <b>Islandora OCR</b> installed to create OCR/HOCR files.'),
      ],
      'language' => [
        '#title' => $this->t('Language'),
        '#type' => 'select',
        '#description' => $this->t('Please select the language the page is written in.'),
        '#options' => $languages,
      ],
      'submit' => [
        '#disabled' => !$can_derive,
        '#type' => 'submit',
        '#value' => $this->t('Perform OCR'),
      ],
    ];
  }

  /**
   * Submit handler for the manage page OCR form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->getStorage()['object']);
    $options = [
      'language' => $form_state->getValue('language'),
      'preprocess' => FALSE,
    ];
    if (islandora_paged_content_page_derive_ocr_datastreams($object, $options)) {
      drupal_set_message($this->t('Successfully performed OCR.'), 'status');
    }
    else {
      drupal_set_message($this->t('Failed to perform OCR.'), 'error');
    }
  }

}
