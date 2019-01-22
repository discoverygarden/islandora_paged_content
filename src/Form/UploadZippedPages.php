<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Unicode;

use Symfony\Component\DependencyInjection\ContainerInterface;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ZipArchive;

use IslandoraTuque;

/**
 * Defines the form for uploading a PDF for extraction.
 */
class UploadZippedPages extends FormBase {

  protected $fileEntityStorage;
  protected $moduleHandler;
  protected $fileSystem;

  /**
   * Constructor.
   */
  public function __construct(EntityStorageInterface $file_entity_storage, ModuleHandlerInterface $module_handler, FileSystemInterface $file_system) {
    $this->fileEntityStorage = $file_entity_storage;
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('file'),
      $container->get('module_handler'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_zipped_upload_form';
  }

  /**
   * Defines the zipped page form.
   *
   * @param array $form
   *   The Drupal form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal form state.
   * @param string $pid
   *   PID of object into which pages are being ingested.
   * @param array $allowed_extensions
   *   The array of allowed extensions.
   * @param string $model
   *   The content model of the page object.
   * @param array $derivatives
   *   The array of derivatives.
   *
   * @return array
   *   Drupal form definition.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pid = NULL, array $allowed_extensions = [], $model = NULL, array $derivatives = []) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
    $object = islandora_object_load($pid);
    $current_pages = islandora_paged_content_get_pages($object);
    $last_page_number = count($current_pages);
    $upload_size = min((int) ini_get('post_max_size'), (int) ini_get('upload_max_filesize'));
    $extensions = ['zip'];
    $form = [];
    $message = $this->t("This sequence currently has @count pages. Additional pages will be appended to the end of the sequence by default. @break", ["@count" => $last_page_number, '@break' => '<br />']);
    $message .= $this->t("Choose a number lower than @count to insert page(s) at a specific location in the sequence.", ["@count" => $last_page_number]);

    if ($this->moduleHandler->moduleExists('islandora_ocr') && in_array('ocr', $derivatives)) {
      $form_state->loadInclude('islandora_ocr', 'inc', 'includes/utilities');
      $languages = islandora_ocr_get_enabled_tesseract_languages();
      $form['language'] = [
        '#title' => $this->t('Language'),
        '#type' => 'select',
        '#description' => $this->t('Please select the language the page is written in.'),
        '#options' => $languages,
      ];
      if (in_array('English', $languages)) {
        $form['language']['#default_value'] = 'eng';
      }
      $form['ignored_ocr_derivatives'] = islandora_ocr_get_ignored_derivatives_fieldset();
    }

    if ($current_pages) {
      $form['insertion_point'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Last sequence number'),
        '#default_value' => $last_page_number,
        '#description' => Xss::filterAdmin($message),
        '#size' => 5,
      ];
    }

    // Value behaves more consistently when passed as a string.
    $form['current_pages'] = [
      '#type' => 'hidden',
      '#value' => serialize($current_pages),
    ];

    $form['file'] = [
      '#title' => $this->t('Compressed images file.'),
      '#type' => 'managed_file',
      '#required' => TRUE,
      '#description' => $this->t('Select file to upload.<br/>Files must be less than <b>@size MB.</b><br/>Allowed file types: <b>@ext.</b>', ['@size' => $upload_size, '@ext' => $extensions[0]]),
      '#default_value' => isset($form_state->getValues()['file']) ? $form_state->getValue('file') : NULL,
      '#upload_location' => 'temporary://',
      '#upload_validators' => [
        'file_validate_extensions' => $extensions,
        'file_validate_size' => [$upload_size * 1024 * 1024],
      ],
    ];

    $form['pid'] = [
      '#type' => 'hidden',
      '#value' => $pid,
    ];
    $form['allowed_extensions'] = [
      '#type' => 'hidden',
      '#value' => $allowed_extensions,
    ];
    $form['model'] = [
      '#type' => 'hidden',
      '#value' => $model,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add files'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
    $tuque = new IslandoraTuque();
    $repository = $tuque->repository;
    $pid = $form_state->getValue('pid');
    $form_state->setRedirect('islandora.view_object', ['object' => $pid]);
    $namespace = substr($pid, 0, strpos($pid, ":"));
    $tmp_dir = uniqid();
    $object = islandora_object_load($pid);
    $book_label = $object->label;
    if (!$object) {
      drupal_set_message($this->t("This object does not exist in this repository"), 'warning');
      return;
    }
    $current_pages = unserialize($form_state->getValue('current_pages'));

    $insertion_point = isset($form_state->getValues()['insertion_point']) ? (int) $form_state->getValue('insertion_point') : 0;
    $pages_to_renumber = [];
    foreach ($current_pages as $current_page) {
      if ((int) $current_page['page'] > $insertion_point) {
        $pages_to_renumber[] = $current_page;
      }
    }
    // Extract file.
    $zip_file = $this->fileEntityStorage->load(reset($form_state->getValue('file')));
    $zip = new ZipArchive();
    $zip->open($this->fileSystem->realpath($zip_file->getFileUri()));
    $destination_dir = $this->fileSystem->realpath("temporary://$tmp_dir");

    // Extract zipped file to named directory.
    if (!$zip->extractTo($destination_dir)) {
      drupal_set_message($this->t('Ingest failed.'), 'warning');
      return;
    }
    $zip->close();
    $zip_file->delete();

    $allowed_extensions = $form_state->getValue('allowed_extensions');
    $callback = function ($element) use ($allowed_extensions) {
      $ext = pathinfo($element, PATHINFO_EXTENSION);
      $ext = Unicode::strtolower($ext);

      // An allowed extension and does /not/ contain __MACOSX.
      return in_array($ext, $allowed_extensions) && preg_match('/__MACOSX/', $element) === 0;
    };

    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($destination_dir), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $file => $object) {
      $unfiltered[] = $file;
    }
    $files_to_add = array_values(array_filter($unfiltered, $callback));
    // Sort files based on name.
    $comparator = function ($a, $b) {
      $file_a = pathinfo($a, PATHINFO_FILENAME);
      $file_b = pathinfo($b, PATHINFO_FILENAME);
      return ($file_a < $file_b) ? -1 : 1;
    };
    usort($files_to_add, $comparator);

    $renumber_count = count($pages_to_renumber);
    $add_count = count($files_to_add);
    $status_message = $this->formatPlural(
        $add_count, 'adding 1 page', 'adding @count pages'
    );

    if ($renumber_count) {
      $status_message = $this->formatPlural(
          $renumber_count, 'Renumbering 1 page and @adding_message.', 'Renumbering @count pages and @adding_message.', [
            '@adding_message' => $status_message,
          ]
      );
    }

    if ($add_count > 0) {
      $batch = [
        'title' => ucfirst($status_message),
        'progress_message' => $this->t('Completed @current operations out of @total.'),
        'operations' => [],
        'file' => drupal_get_path('module', 'islandora_paged_content') . '/includes/manage_pages.inc',
        'finished' => 'islandora_paged_content_zipped_upload_ingest_finished',
      ];
      $file_count = count($files_to_add);
      $config = [
        'pid' => $pid,
        'destination_dir' => $destination_dir,
        'namespace' => $namespace,
        'language' => $form_state->getValue('language'),
        'model' => $form_state->getValue('model'),
        'file_count' => $file_count,
        'book_label' => $book_label,
        'language' => isset($form_state->getValues()['language']) ? $form_state->getValue('language') : 'eng',
        'ignored_derivs' => [
          'ocr' => isset($form_state->getValues()['ignore_ocr']) ? $form_state->getValue('ignore_ocr') : FALSE,
          'hocr' => isset($form_state->getValues()['ignore_hocr']) ? $form_state->getValue('ignore_hocr') : FALSE,
        ],
      ];
      if (isset($pages_to_renumber[0])) {
        foreach ($pages_to_renumber as $page) {
          $batch['operations'][] = [
            'islandora_paged_content_content_insert_sequence_gap',
            [$page, $file_count],
          ];
        }
      }
      foreach ($files_to_add as $image) {
        $config['page_number'] = ++$insertion_point;
        $config['image'] = $image;
        $batch['operations'][] = [
          'islandora_paged_content_add_pages',
          [$repository, $config, $destination_dir],
        ];
      }

      batch_set($batch);
    }
    else {
      drupal_set_message($this->t('No files were found to add in the provided ZIP file.'), 'warning');
    }
  }

}
