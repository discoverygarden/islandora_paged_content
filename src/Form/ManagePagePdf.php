<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use AbstractObject;

/**
 * Derives the given page objects PDF datastream.
 */
class ManagePagePdf extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_manage_page_pdf_form';
  }

  /**
   * Derives the given page objects PDF datastream.
   *
   * @param array $form
   *   The Drupal form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Drupal form state.
   * @param \AbstractObject $object
   *   The object to update.
   *
   * @return array
   *   The Drupal form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');

    $form_state->setStorage(['object' => $object->id]);
    $can_derive = islandora_paged_content_can_derive($object, 'PDF');
    return [
      'description' => [
        '#type' => 'item',
        '#description' => $this->t('You must have <b>ImageMagick</b> installed to create PDF files.'),
      ],
      'dpi' => [
        '#type' => 'select',
        '#title' => $this->t('DPI (Dots Per Inch)'),
        '#description' => $this->t('Set the DPI for the generated PDF.'),
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
   * Submit handler for the manage page PDF form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->getStorage()['object']);
    $options = [
      '-density' => $form_state->getValue('dpi'),
      '-compress' => 'LZW',
    ];
    if (islandora_paged_content_page_derive_pdf_datastream($object, $options)) {
      drupal_set_message($this->t('Successfully created PDF.'), 'status');
    }
    else {
      drupal_set_message($this->t('Failed to created PDF.'), 'error');
    }
  }

}
