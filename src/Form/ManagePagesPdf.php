<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use AbstractObject;

/**
 * Creates a PDF of all the child pages.
 */
class ManagePagesPdf extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_manage_pages_pdf_form';
  }

  /**
   * Generate a PDF file for each page and then combine them into a single PDF.
   *
   * @param array $form
   *   The Drupal form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal form state.
   * @param \AbstractObject $object
   *   The object to fetch the pages from.
   *
   * @return array
   *   The Drupal form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/batch');

    $form_state->setStorage(['object' => $object]);
    $can_derive = islandora_paged_content_can_create_pdf() && islandora_paged_content_can_combine_pdf();
    return [
      'description' => [
        '#type' => 'item',
        '#description' => $this->t('You must have both <b>ImageMagick</b> and <b>GhostScript</b> installed to create/combine PDF files.<br/> This will also update the PDF datastreams for each Page object.'),
      ],
      'dpi' => [
        '#type' => 'select',
        '#title' => $this->t('DPI - Dots Per Inch'),
        '#description' => $this->t('Set the DPI for the generated PDF. The higher the resolution the longer the process will take.'),
        '#options' => array_combine(
          ['72', '96', '300', '600'],
          ['72', '96', '300', '600']
        ),
      ],
      'submit' => [
        '#disabled' => !$can_derive,
        '#type' => 'submit',
        '#value' => $this->t('Create PDF'),
      ],
    ];
  }

  /**
   * Creates a PDF of the book and a PDF of every page.
   *
   * Triggers a batch to derive a PDF datastreams in each page object.then
   * combined them into a single PDF which is stored in the book object.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = $form_state->getStorage()['object'];
    $pages = array_keys(islandora_paged_content_get_pages($object));
    $options = [
      '-density' => $form_state->getValue('dpi'),
      '-compress' => 'LZW',
    ];
    $batch = islandora_paged_content_create_pdf_batch($object, $pages, $options);
    batch_set($batch);
  }

}
