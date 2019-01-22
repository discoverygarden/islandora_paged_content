<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use AbstractObject;

/**
 * {@inheritdoc}
 */
class ManagePageProgression extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_manage_page_progression_form';
  }

  /**
   * Sets the page progression for a paged content.
   */
  public function buildForm(array $form, FormStateInterface $form_state, AbstractObject $object = NULL) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/utilities');
    $page_progression = islandora_paged_content_get_page_progression($object);
    $form_state->setStorage(['pid' => $object->id]);
    return [
      'page_progression' => [
        '#type' => 'radios',
        '#title' => $this->t('Page Progression'),
        '#options' => [
          'lr' => $this->t('Left-to-Right'),
          'rl' => $this->t('Right-to-Left'),
        ],
        '#default_value' => $page_progression,
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Set page progression'),
      ],
    ];
  }

  /**
   * Submit handler to set page progression for paged content.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $object = islandora_object_load($form_state->getStorage()['pid']);
    islandora_paged_content_set_page_progression($object, $form_state->getValue('page_progression'));
    drupal_set_message($this->t('Page progression updated.'));
  }

}
