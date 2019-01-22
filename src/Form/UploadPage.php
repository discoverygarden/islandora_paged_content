<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use AbstractObject;

/**
 * The form for uploading a single page image.
 */
class UploadPage extends FormBase {

  protected $fileEntityStorage;
  protected $moduleHandler;

  /**
   * Constructor.
   */
  public function __construct(EntityStorageInterface $file_entity_storage, ModuleHandlerInterface $module_handler) {
    $this->fileEntityStorage = $file_entity_storage;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_upload_page_form';
  }

  /**
   * The form for uploading a single page image.
   *
   * @param array $form
   *   The Drupal form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal form state.
   * @param \AbstractObject $parent
   *   An AbstractObject representing an object within Fedora.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $parent = NULL) {
    $get_default_value = function ($name, $default) use (&$form_state) {
      return isset($form_state->getValues()[$name]) ? $form_state->getValue($name) : $default;
    };
    $form_state->set('parent', $parent);
    $upload_size = min((int) ini_get('post_max_size'), (int) ini_get('upload_max_filesize'));
    $extensions = ['tiff tif jp2 jpg jpeg'];
    $languages = [];
    if ($this->moduleHandler->moduleExists('islandora_ocr')) {
      $form_state->loadInclude('islandora_ocr', 'inc', 'includes/utilities');
      $languages = islandora_ocr_get_enabled_tesseract_languages();
    }
    $default_language = in_array('eng', $languages) ? 'eng' : NULL;

    $upload_form = [
      'file' => [
        '#title' => $this->t('Page'),
        '#type' => 'managed_file',
        '#description' => $this->t('Select an image to upload.<br/>Files must be less than <b>@size MB.</b><br/>Allowed file types: <b>@ext.</b>', ['@size' => $upload_size, '@ext' => $extensions[0]]),
        '#default_value' => $get_default_value('files', NULL),
        '#upload_location' => 'temporary://',
        '#required' => TRUE,
        '#upload_validators' => [
          'file_validate_extensions' => $extensions,
          'file_validate_size' => [$upload_size * 1024 * 1024],
        ],
      ],
    ];

    if ($this->moduleHandler->moduleExists('islandora_ocr')) {
      $upload_form['language'] = [
        '#access' => $this->moduleHandler->moduleExists('islandora_ocr'),
        '#title' => $this->t('Language'),
        '#type' => 'select',
        '#description' => $this->t('Please select the language the page is written in.'),
        '#options' => $languages,
        '#default_value' => $get_default_value('language', $default_language),
      ];
      $upload_form['ocr_ignored_derivatives'] = islandora_ocr_get_ignored_derivatives_fieldset();
    }

    return $upload_form;
  }

  /**
   * Sets RELS-EXT/RELS-INT properties and creates the 'OBJ' datastream.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
    $object = islandora_ingest_form_get_object($form_state);
    $file = $this->fileEntityStorage->load(reset($form_state->getValue('file')));
    $parent = $form_state->get('parent');
    $pages = islandora_paged_content_get_pages($parent);
    $num_pages = count($pages) + 1;
    $rels_ext = $object->relationships;

    $label = $file->getFilename();
    // Change the label of the page to the sequence number if variable is set.
    if ($this->config('islandora_paged_content.settings')->get('islandora_paged_content_page_label')) {
      $label = $num_pages;
    }
    $object->label = $label;

    // Add the OCR creation flags before the datastream is updated.
    if ($this->moduleHandler->moduleExists('islandora_ocr')) {
      $form_state->loadInclude('islandora_ocr', 'inc', 'includes/derivatives');
      $language = isset($form_state->getValues()['language']) ? $form_state->getValue('language') : NULL;
      islandora_ocr_set_generating_rels_ext_statements($object, !$form_state->getValue('ignore_ocr'), !$form_state->getValue('ignore_hocr'), $language);
    }
    islandora_paged_content_update_datastream($object, $file->getFileUri(), 'OBJ', NULL, NULL, 'M', FALSE);
    // Update RELS-EXT properties, page/sequence/etc, and append the page at the
    // end of the book.
    islandora_paged_content_set_relationship($rels_ext, ISLANDORA_RELS_EXT_URI, 'isPageOf', $parent->id);
    islandora_paged_content_set_relationship($rels_ext, ISLANDORA_RELS_EXT_URI, 'isSequenceNumber', (string) $num_pages, TRUE);
    islandora_paged_content_set_relationship($rels_ext, ISLANDORA_RELS_EXT_URI, 'isPageNumber', (string) $num_pages, TRUE);
    islandora_paged_content_set_relationship($rels_ext, ISLANDORA_RELS_EXT_URI, 'isSection', '1', TRUE);
    islandora_paged_content_set_relationship($rels_ext, FEDORA_RELS_EXT_URI, 'isMemberOf', $parent->id);
  }

}
