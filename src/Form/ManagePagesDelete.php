<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use AbstractObject;

/**
 * {@inheritdoc}
 */
class ManagePagesDelete extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_manage_pages_delete_form';
  }

  /**
   * Gets the delete pages form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');

    $form_state->setStorage(['object' => $object]);
    $pages = islandora_paged_content_get_pages($object);
    return [
      'table' => [
        '#type' => 'tableselect',
        '#header' => [
          'pid' => $this->t('PID'),
          'page' => $this->t('Sequence Number'),
          'label' => $this->t('Label'),
        ],
        '#options' => $pages,
        '#multiple' => TRUE,
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Delete Selected Pages'),
      ],
      'item' => [
        '#type' => 'item',
        '#markup' => $this->t('Note: Make sure to re-order pages after deleting pages to keep the numerical index in sequence for search term highlighting.'),
      ],
    ];
  }

  /**
   * Submit handler for the delete pages form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = $form_state->getStorage()['object'];
    $pages = array_values(array_filter($form_state->getValue('table')));
    $batch = islandora_paged_content_delete_pages_batch($object, $pages);
    batch_set($batch);
  }

}
