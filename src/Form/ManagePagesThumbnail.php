<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use AbstractObject;

/**
 * {@inheritdoc}
 */
class ManagePagesThumbnail extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_manage_pages_thumbnail_form';
  }

  /**
   * Updates the objects thumbnail from the first child page.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');

    $form_state->setStorage(['object' => $object]);
    return [
      'description' => [
        '#type' => 'item',
        '#description' => $this->t("Update the thumbnail image. The book must have pages, and the first page must have a TN datastream."),
      ],
      'submit' => [
        '#disabled' => !islandora_paged_content_can_update_paged_content_thumbnail($object),
        '#type' => 'submit',
        '#value' => $this->t('Update Thumbnail'),
      ],
    ];
  }

  /**
   * The submit handler for the update thumbnail form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = $form_state->getStorage()['object'];
    if (islandora_paged_content_update_paged_content_thumbnail($object)) {
      drupal_set_message($this->t('Thumbnail successfully updated.'), 'status');
    }
    else {
      drupal_set_message($this->t('Failed to update thumbnail'), 'error');
    }
  }

}
