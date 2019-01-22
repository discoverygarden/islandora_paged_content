<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the form for uploading a PDF for extraction.
 */
class UploadPdf extends FormBase {

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
    return 'islandora_paged_content_upload_pdf_form';
  }

  /**
   * Form used to accept an uploaded PDF and user input for ingest.
   *
   * @param array $form
   *   An array representing a form within Drupal.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An array containing the Drupal form state.
   * @param string $model
   *   A string specifying the content model of the page(s) to be ingested.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $model = NULL) {
    $form_state->loadInclude('islandora', 'inc', 'includes/ingest.form');
    $form['pdf_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('PDF File'),
      '#description' => $this->t('You can optionally upload a PDF file, from which the pages of the document will be generated.'),
      '#islandora_plupload_do_not_alter' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf'],
      ],
    ];
    $form['image_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Page Image Settings'),
      '#collapsed' => FALSE,
      '#collapsible' => FALSE,
    ];
    $form['image_settings']['image_format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Image Format'),
      '#options' => [
        'tiffgray' => $this->t('8-bit grayscale'),
        'tiff12nc' => $this->t('12-bit RGB'),
        'tiff24nc' => $this->t('24-bit RGB'),
        'tiff48nc' => $this->t('48-bit RGB'),
        'tiff32nc' => $this->t('32-bit CMYK'),
        'tiff64nc' => $this->t('64-bit CMYK'),
      ],
      '#default_value' => 'tiff32nc',
      '#description' => $this->t('You can choose the format for the page images extracted from the uploaded PDF. We recommend 32-bit CMYK for most cases.'),
    ];
    $dpi = [
      '72',
      '96',
      '150',
      '300',
      '600',
    ];
    $form['image_settings']['resolution'] = [
      '#type' => 'radios',
      '#title' => $this->t("Resolution (DPI)"),
      '#description' => $this->t('The higher the resolution selected, the more legible the text will be, but be aware it takes longer to process higher resolution images, and they will consume more space.'),
      '#options' => array_combine($dpi, $dpi),
      '#default_value' => '300',
    ];
    $form['text_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Page OCR / Text Settings'),
      '#collapsed' => FALSE,
      '#collapsible' => FALSE,
    ];
    $ocr_options = [
      'none' => $this->t('Do not extract text.'),
    ];
    $states = [];
    $languages = [];
    $default_value = 'none';
    if ($this->moduleHandler->moduleExists('islandora_ocr')) {
      $form_state->loadInclude('islandora_ocr', 'inc', 'includes/utilities');
      $ocr_options['ocr'] = $this->t('Perform OCR on the PDF.');
      $states[] = ['value' => 'ocr'];
      $languages = islandora_ocr_get_enabled_tesseract_languages();
      // Don't need the no OCR option.
      unset($languages['no_ocr']);
    }
    if (islandora_paged_content_pdftotext_availability()) {
      $ocr_options['extract'] = $this->t('Extract text from the PDF.');
      $default_value = 'extract';
      $states[] = ['value' => 'extract'];
      // Need to fallback if tesseract isn't available for future OCR.
      $languages = [
        'eng' => $this->t('English'),
        'fra' => $this->t('French'),
        'deu-frak' => $this->t('German'),
        'por' => $this->t('Portugese'),
        'spa' => $this->t('Spanish'),
        'hin' => $this->t('Hindi'),
        'jpn' => $this->t('Japanese'),
        'ita' => $this->t('Italian'),
      ];
    }
    $form['text_settings']['extract_text'] = [
      '#type' => 'radios',
      '#title' => $this->t('Extract Text'),
      '#options' => $ocr_options,
      '#default_value' => $default_value,
      '#description' => $this->t("Extracted text is used to aid in the discovery of this object when searching. If the uploaded PDF contains text and not just images of text, we recommend you try to extract it from the PDF. On the other hand if the uploaded PDF is composed of Images of text we and doesn't contain text we recommend you perform OCR."),
    ];
    if ($this->config('islandora_paged_content.settings')->get('islandora_paged_content_pdftotext_use_raw')) {
      $form['text_settings']['rawtext'] = [
        '#type' => 'radios',
        '#title' => $this->t('Use the raw text of the PDF'),
        '#description' => $this->t('This will pass the -raw parameter off to pdftotext when extracting text. By default, pdftotext extracts text in natural reading order. In edge case documents, where PDF creation tools have made blocks in errorneous order, text extraction will yield unexpected results. As such, this parameter will pull out text in the order that the PDF creation tool wrote it (layout ignored). This is not a valid default option to have due to that PDF creation tools are not constrained to writing blocks in the order they appear and it is up to the PDF reader to render them correctly.'),
        '#options' => [
          'yes' => $this->t('Yes'),
          'no' => $this->t('No'),
        ],
        '#default_value' => 'no',
        '#states' => [
          'visible' => [
            'input[name="extract_text"]' => ['value' => 'extract'],
          ],
        ],
      ];
    }
    $form['text_settings']['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#description' => $this->t('Please select the language the page is written in.'),
      '#options' => $languages,
      '#states' => [
        'visible' => [
          ':input[name="extract_text"]' => $states,
        ],
      ],
    ];
    $form['model'] = [
      '#type' => 'value',
      '#value' => $model,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Only do things if the user has uploaded a PDF.
    if ($form_state->getValue('pdf_file')) {
      $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
      $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/batch');
      $form_state->loadInclude('islandora', 'inc', 'includes/ingest.form');
      $form_state->loadInclude('islandora', 'inc', 'includes/utilities');
      $file_obj = $this->fileEntityStorage->load(reset($form_state->getValue('pdf_file')));
      $object = islandora_ingest_form_get_object($form_state);
      islandora_paged_content_add_pdf_to_object($object, $file_obj);
      $num_pages = $form_state->get('number_of_pages');
      $pages = range(1, $num_pages);
      $options = array_intersect_key($form_state->getValues(), array_combine([
        'image_format',
        'resolution',
        'language',
        'extract_text',
        'model',
      ], [
        'image_format',
        'resolution',
        'language',
        'extract_text',
        'model',
      ])) + ['pdf_file' => $file_obj, 'parent' => $object->id];
      if ($this->config('islandora_paged_content.settings')->get('islandora_paged_content_pdftotext_use_raw')) {
        $options['rawtext'] = $form_state->getValue('rawtext') == 'yes' ? TRUE : FALSE;
      }
      else {
        $options['rawtext'] = FALSE;
      }
      $batch = islandora_paged_content_create_paged_content_from_pdf_batch($object, $pages, $options);
      batch_set($batch);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Only do things if the user has uploaded a PDF.
    if ($form_state->getValue('pdf_file')) {
      $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
      $pdf_file = reset($form_state->getValue('pdf_file'));
      $file_obj = $this->fileEntityStorage->load($pdf_file);
      $num_pages = islandora_paged_content_length_of_pdf($file_obj->getFileUri());
      if ($num_pages) {
        $form_state->set('number_of_pages', $num_pages);
      }
      else {
        $form_state->setError($form['pdf_file'], $this->t('Unable to extract required information from the supplied PDF.'));
      }
    }
  }

}
