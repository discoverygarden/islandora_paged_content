<?php

/**
 * @file
 * Defines the page holding the forms for managing objects with child pages.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Displays all the book management forms in a set of vertical tabs.
 *
 * @param AbstractObject $object
 *   The book object to manage.
 *
 * @return array
 *   A renderable array containing all the management forms related to
 *   book objects.
 */
function islandora_paged_content_manage_pages_menu(AbstractObject $object) {
  $manage_tabs = [
    '#title' => $object->label,
    'pdf' => [
      '#access' => islandora_object_access(ISLANDORA_ADD_DS, $object),
      '#title' => t('Create PDF'),
      '#type' => 'details',
      '#open' => FALSE,
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePagesPdf', $object),
    ],
    'ocr' => [
      '#access' => islandora_object_access(ISLANDORA_ADD_DS, $object),
      '#title' => t('Perform OCR'),
      '#type' => 'details',
      '#open' => FALSE,
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePagesOcr', $object),
    ],
    'images' => [
      '#access' => islandora_object_access(ISLANDORA_ADD_DS, $object),
      '#title' => t('Create Images'),
      '#type' => 'details',
      '#open' => FALSE,
      'form_1' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePagesThumbnail', $object),
      'form_2' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePagesImages', $object),
    ],
    'page_progression' => [
      '#access' => islandora_datastream_access(ISLANDORA_METADATA_EDIT, $object['RELS-EXT']),
      '#title' => t('Page Progression'),
      '#type' => 'details',
      '#open' => FALSE,
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePageProgression', $object),
    ],
    'viewinghint' => [
      '#access' => \Drupal::currentUser()->hasPermission(ISLANDORA_METADATA_EDIT),
      '#title' => t('Page display mode'),
      '#type' => 'details',
      '#open' => FALSE,
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManageViewingHint', $object),
    ],
    'hascover' => [
      '#access' => \Drupal::currentUser()->hasPermission(ISLANDORA_METADATA_EDIT),
      '#title' => t('Book Cover?'),
      '#type' => 'details',
      '#open' => FALSE,
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManageHasCover', $object),
    ],
    'sequence' => [
      '#access' => islandora_datastream_access(ISLANDORA_METADATA_EDIT, $object['RELS-EXT']),
      '#title' => t('Reorder Pages'),
      '#type' => 'details',
      '#open' => FALSE,
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePagesSequences', $object),
    ],
    'delete' => [
      '#access' => islandora_object_access(ISLANDORA_PURGE, $object),
      '#title' => t('Delete Pages'),
      '#type' => 'details',
      '#open' => FALSE,
      'form' => \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\ManagePagesDelete', $object),
    ],
  ];
  $context = ['object' => $object];
  \Drupal::moduleHandler()->alter('islandora_paged_content_pages_management_tabs', $manage_tabs, $context);
  return $manage_tabs;
}

/**
 * Validates quick_reorder fieldset.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The Drupal form state.
 */
function islandora_paged_content_quick_reorder_validate($form, FormStateInterface $form_state) {
  module_load_include('inc', 'islandora_paged_content', 'includes/utilities');

  $pages = islandora_paged_content_get_pages(islandora_object_load($form_state->getStorage()['pid']));
  $first_page = array_shift($pages);
  $last_page = array_pop($pages);
  $page_numbers = range($first_page['page'], $last_page['page']);

  // Insert point validation.
  if (!preg_match('/^[0-9\s]/', $form_state->getValue('pages_insert_point')) ||
      !in_array($form_state->getValue('pages_insert_point'), $page_numbers)) {
    $form_state->setError('pages_insert_point', t('Insert point must be a valid page number.'));
  }

  // Pages to move validation.
  if (!preg_match('/^[0-9\s,\-]/', $form_state->getValue('pages_to_move'))) {
    $form_state->setError(
        'pages_to_move', t("Pages to move must follow the form '1, 2-4,5'.")
    );
  }
  $pages_to_move_list = islandora_paged_content_parse_pages_to_move($form_state->getValue('pages_to_move'));
  if ($pages_to_move_list != array_intersect($pages_to_move_list, $page_numbers)) {
    $form_state->setError('pages_to_move', t('All pages must be in range.'));
  }
}

/**
 * Validates quick_reorder fieldset.
 *
 * @param array $form
 *   The Drupal form definition.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The Drupal form state.
 */
function islandora_paged_content_quick_reorder_submit($form, FormStateInterface $form_state) {
  module_load_include('inc', 'islandora_paged_content', 'includes/utilities');
  $page_numbers_to_move = islandora_paged_content_parse_pages_to_move($form_state->getValue('pages_to_move'));

  // Build page array without changed pids.
  $book = islandora_object_load($form_state->getStorage()['pid']);
  $raw_pages = islandora_paged_content_get_pages($book);
  $pages = [];
  $page_pids_to_move = [];
  foreach ($raw_pages as $page_pid => $page_info) {
    if (in_array($page_info['page'], $page_numbers_to_move)) {
      $page_pids_to_move[] = $page_pid;
    }
    else {
      $pages[$page_info['page']] = $page_pid;
    }
  }

  // Add in changed PIDs. Fixes sequence numbers.
  array_splice(
      $pages, array_search(
          $form_state->getValue('pages_insert_point'), array_keys($pages)
      ) + 1, 0, $page_pids_to_move
  );

  $batch = islandora_paged_content_sequence_pages_batch($book, $pages);
  batch_set($batch);
}

/**
 * Local menu action to present a zipped file ingest form.
 *
 * @param AbstractObject $object
 *   The object to ingest into.
 * @param array $allowed_extensions
 *   The array of allowed extensions.
 * @param string $model
 *   The content model of the page object
 * @param array $derivatives
 *   The array of derivatives.
 *
 * @return string
 *   The HTML representation of the ingest page form.
 */
function islandora_paged_content_ingest_zipped_pages(AbstractObject $object, $allowed_extensions, $model, $derivatives) {
  //module_load_include('inc', 'islandora', 'includes/breadcrumb');
  module_load_include('inc', 'islandora', 'includes/utilities');
  //drupal_set_breadcrumb(islandora_get_breadcrumbs($object));
  return \Drupal::formBuilder()->getForm('Drupal\islandora_paged_content\Form\UploadZippedPages', $object->id, $allowed_extensions, $model, $derivatives);
}

/**
 * Creates page objects and associates them with object.
 *
 * @param Repository $repository
 *   Active repository object to build NewFedoraObject.
 * @param array $config
 *   Associative array of required values.
 * @param string $pages_directory
 *   This is a hack.  The finished callback needs this value so it can delete
 *   the temporary directory used to store  pages.
 */
function islandora_paged_content_add_pages($repository, $config, $pages_directory, &$context) {
  module_load_include('inc', 'islandora_paged_content', 'includes/utilities');
  $context['results']['pages_directory'] = $pages_directory;
  $context['results']['file_count'] = $config['file_count'];
  $context['results']['book_label'] = $config['book_label'];

  // Create object.
  $object = $repository->constructObject($config['namespace']);
  $label = pathinfo($config['image'], PATHINFO_FILENAME);
  if (\Drupal::config('islandora_paged_content.settings')->get('islandora_paged_content_page_label')) {
    $label = $config['page_number'];
  }
  $object->label = $label;
  $ds_label = pathinfo($config['image'], PATHINFO_BASENAME);
  islandora_paged_content_update_datastream($object, $config['image'], 'OBJ', $ds_label, NULL, 'M', FALSE);
  $rels_ext = $object->relationships;
  $parent = $config['pid'];
  $object->relationships->add(FEDORA_MODEL_URI, 'hasModel', $config['model']);

  // Re-checking module existence here in case this batch op is called from
  // somewhere other than the form.
  if (\Drupal::moduleHandler()->moduleExists('islandora_ocr')) {
    module_load_include('inc', 'islandora_ocr', 'includes/derivatives');
    islandora_ocr_set_generating_rels_ext_statements($object, !$config['ignored_derivs']['ocr'], !$config['ignored_derivs']['hocr'], $config['language']);
  }
  islandora_paged_content_set_relationship($rels_ext, ISLANDORA_RELS_EXT_URI, 'isPageOf', $parent);
  islandora_paged_content_set_relationship($rels_ext, ISLANDORA_RELS_EXT_URI, 'isSequenceNumber', (string) $config['page_number'], TRUE);
  islandora_paged_content_set_relationship($rels_ext, ISLANDORA_RELS_EXT_URI, 'isPageNumber', (string) $config['page_number'], TRUE);
  islandora_paged_content_set_relationship($rels_ext, ISLANDORA_RELS_EXT_URI, 'isSection', '1', TRUE);
  islandora_paged_content_set_relationship($rels_ext, FEDORA_RELS_EXT_URI, 'isMemberOf', $parent);

  if (\Drupal::moduleHandler()->moduleExists('islandora_xacml_editor')) {
    // XACML inheritance.
    // TODO: Implement in an object altered hook? May need a better way to
    // identify paged content objects.
    $parent_object = islandora_object_load($parent);
    islandora_xacml_editor_apply_parent_policy($object, $parent_object);
  }

  islandora_add_object($object);
}

/**
 * Alters page and sequence number of page object to allow for inserted pages.
 *
 * @param array $page_to_reindex
 *   Array Containing page pid and current page number.
 * @param int $offset
 *   Offset to determine new page number.
 */
function islandora_paged_content_insert_sequence_gap($page_to_reindex, $offset) {
  module_load_include('inc', 'islandora_paged_content', 'includes/utilities');
  $new_page_num = (int) $page_to_reindex['page'] + $offset;
  $page_object = islandora_object_load($page_to_reindex['pid']);
  $rels_ext = $page_object->relationships;
  islandora_paged_content_set_relationship($rels_ext, ISLANDORA_RELS_EXT_URI, 'isSequenceNumber', (string) $new_page_num, TRUE);
  islandora_paged_content_set_relationship($rels_ext, ISLANDORA_RELS_EXT_URI, 'isPageNumber', (string) $new_page_num, TRUE);
}

/**
 * Batch 'finished' callback.
 *
 * Deletes the temporary files associated with the ingested pages.
 */
function islandora_paged_content_zipped_upload_ingest_finished($success, $results, $operations) {
  file_unmanaged_delete_recursive($results['pages_directory']);
  if ($success) {
    drupal_set_message(t('Successfully added @pages pages to @label.', [
      '@pages' => $results['file_count'],
      '@label' => $results['book_label'],
    ]));
  }
  else {
    drupal_set_message(t('Problems were encountered while trying to add pages to @label. Please contact an administrator or check the logs for details.', [
      '@label' => $results['book_label'],
    ]));
  }
}
