<?php

namespace Drupal\islandora_paged_content\Form;

use Drupal\Core\Form\FormStateInterface;

use Drupal\islandora\Form\ModuleHandlerAdminForm;

/**
 * Module admin form.
 */
class Admin extends ModuleHandlerAdminForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_paged_content_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('islandora_paged_content.settings');

    $config->set('islandora_paged_content_gs', $form_state->getValue('islandora_paged_content_gs'));
    $config->set('islandora_paged_content_pdfinfo', $form_state->getValue('islandora_paged_content_pdfinfo'));
    $config->set('islandora_paged_content_pdftotext', $form_state->getValue('islandora_paged_content_pdftotext'));
    $config->set('islandora_paged_content_pdftotext_use_raw', $form_state->getValue('islandora_paged_content_pdftotext_use_raw'));

    $config->set('islandora_paged_content_djatoka_url', $form_state->getValue('islandora_paged_content_djatoka_url'));
    $config->set('islandora_paged_content_sequence_number_field', $form_state->getValue('islandora_paged_content_sequence_number_field'));
    $config->set('islandora_paged_content_use_solr_for_dimensions', $form_state->getValue('islandora_paged_content_use_solr_for_dimensions'));
    $config->set('islandora_paged_content_solr_width_field', $form_state->getValue('islandora_paged_content_solr_width_field'));
    $config->set('islandora_paged_content_solr_height_field', $form_state->getValue('islandora_paged_content_solr_height_field'));
    $config->set('islandora_paged_content_page_label', $form_state->getValue('islandora_paged_content_page_label'));
    $config->set('islandora_paged_content_hide_pages_solr', $form_state->getValue('islandora_paged_content_hide_pages_solr'));
    $config->set('islandora_paged_content_solr_results_alter', $form_state->getValue('islandora_paged_content_solr_results_alter'));
    $config->set('islandora_paged_content_solr_fq', $form_state->getValue('islandora_paged_content_solr_fq'));

    $config->save();
    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora_paged_content.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->loadInclude('islandora_paged_content', 'inc', 'includes/admin.form');
    $get_default_value = function ($name) use ($form_state) {
      $values = $form_state->getValues();
      return isset($values[$name]) ? $values[$name] : $this->config('islandora_paged_content.settings')->get($name);
    };
    $gs = $get_default_value('islandora_paged_content_gs');
    $pdfinfo = $get_default_value('islandora_paged_content_pdfinfo');
    $pdftotext = $get_default_value('islandora_paged_content_pdftotext');

    $djatoka_url = $get_default_value('islandora_paged_content_djatoka_url');
    $djatoka_availible_message = islandora_paged_content_admin_settings_form_djatoka_availible_message($djatoka_url);
    $solr_enabled = $this->moduleHandler->moduleExists('islandora_solr');

    $form = [
      'pdf_derivative_settings' => [
        '#type' => 'fieldset',
        '#title' => $this->t('PDF Derivative Settings'),
        'islandora_paged_content_gs' => [
          '#type' => 'textfield',
          '#title' => $this->t('gs (GhostScript)'),
          '#description' => $this->t('GhostScript is used to combine PDF files into a representation of a book or newspaper.<br/>') .
          islandora_paged_content_admin_settings_form_executable_available_message($gs),
          '#default_value' => $gs,
          '#prefix' => '<div id="gs-wrapper">',
          '#suffix' => '</div>',
          '#ajax' => [
            'callback' => 'islandora_paged_content_admin_settings_form_gs_ajax_callback',
            'wrapper' => 'gs-wrapper',
            'effect' => 'fade',
            'event' => 'change',
          ],
        ],
      ],
      'pdf_paged_content_ingestion_settings' => [
        '#type' => 'fieldset',
        '#title' => $this->t('PDF Paged Content Ingest Settings'),
        'islandora_paged_content_pdfinfo' => [
          '#type' => 'textfield',
          '#title' => $this->t('pdfinfo'),
          '#description' => $this->t('Pdfinfo is used to extract information needed when ingesting a single PDF into paged content and individual page objects.<br/>') .
          islandora_paged_content_admin_settings_form_executable_available_message($pdfinfo),
          '#default_value' => $pdfinfo,
          '#prefix' => '<div id="pdfinfo-wrapper">',
          '#suffix' => '</div>',
          '#ajax' => [
            'callback' => 'islandora_paged_content_admin_settings_form_pdfinfo_ajax_callback',
            'wrapper' => 'pdfinfo-wrapper',
            'effect' => 'fade',
            'event' => 'change',
          ],
        ],
        'islandora_paged_content_pdftotext' => [
          '#type' => 'textfield',
          '#title' => $this->t('pdftotext'),
          '#description' => $this->t('Pdftotext is used to extract text for OCR when ingesting a single PDF into paged content and individual page objects.<br/>') .
          islandora_paged_content_admin_settings_form_executable_available_message($pdftotext),
          '#default_value' => $pdftotext,
          '#prefix' => '<div id="pdftotext-wrapper">',
          '#suffix' => '</div>',
          '#ajax' => [
            'callback' => 'islandora_paged_content_admin_settings_form_pdftotext_ajax_callback',
            'wrapper' => 'pdftotext-wrapper',
            'effect' => 'fade',
            'event' => 'change',
          ],
        ],
        'islandora_paged_content_pdftotext_use_raw' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Allow Extraction of Raw Text'),
          '#description' => $this->t('This will pass the -raw parameter off to pdftotext when extracting text. By default, pdftotext extracts text in natural reading order. In edge case documents, where PDF creation tools have made blocks in errorneous order, text extraction will yield unexpected results. As such, this parameter will pull out text in the order that the PDF creation tool wrote it (layout ignored). This is not a valid default option to have due to that PDF creation tools are not constrained to writing blocks in the order they appear and it is up to the PDF reader to render them correctly.'),
          '#default_value' => $this->config('islandora_paged_content.settings')->get('islandora_paged_content_pdftotext_use_raw'),
        ],
      ],
      'islandora_paged_content_djatoka_url' => [
        '#type' => 'textfield',
        '#prefix' => '<div id="djatoka-path-wrapper">',
        '#suffix' => '</div>',
        '#title' => $this->t('djatoka URL'),
        '#description' => $this->t('<strong>Externally accessible</strong> URL to the djatoka instance.<br/>') . $djatoka_availible_message,
        '#default_value' => $djatoka_url,
        '#ajax' => [
          'callback' => 'islandora_paged_content_admin_settings_form_djatoka_ajax_callback',
          'wrapper' => 'djatoka-path-wrapper',
          'effect' => 'fade',
          'event' => 'change',
        ],
      ],
      'islandora_paged_content_sequence_number_field' => [
        '#access' => $solr_enabled,
        '#type' => 'textfield',
        '#title' => $this->t('Solr page sequence number field'),
        '#description' => $this->t('The page or sequence number of each page or image.'),
        '#default_value' => $get_default_value('islandora_paged_content_sequence_number_field'),
      ],
      'islandora_paged_content_use_solr_for_dimensions' => [
        '#access' => $solr_enabled,
        '#type' => 'checkbox',
        '#title' => $this->t('Use Solr to derive pages and sequence numbers'),
        '#default_value' => $get_default_value('islandora_paged_content_use_solr_for_dimensions', FALSE),
      ],
      'islandora_paged_content_solr_width_field' => [
        '#access' => $solr_enabled,
        '#type' => 'textfield',
        '#title' => $this->t('Solr width dimension field'),
        '#default_value' => $get_default_value('islandora_paged_content_solr_width_field'),
        '#states' => [
          'visible' => [
            ':input[name="islandora_paged_content_use_solr_for_dimensions"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ],
      'islandora_paged_content_solr_height_field' => [
        '#access' => $solr_enabled,
        '#type' => 'textfield',
        '#title' => $this->t('Solr height dimension field'),
        '#default_value' => $get_default_value('islandora_paged_content_solr_height_field'),
        '#states' => [
          'visible' => [
            ':input[name="islandora_paged_content_use_solr_for_dimensions"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ],
      'islandora_paged_content_page_label' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Set page labels to sequence numbers'),
        '#description' => $this->t('The sequence number of each page will be used to set its label.'),
        '#default_value' => $get_default_value('islandora_paged_content_page_label'),
      ],
      'islandora_paged_content_solr_results_alter' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Solr Results Altering'),
        'islandora_paged_content_hide_pages_solr' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Hide Page Objects From Search Results'),
          '#default_value' => $get_default_value('islandora_paged_content_hide_pages_solr'),
        ],
        'islandora_paged_content_solr_fq' => [
          '#type' => 'textfield',
          '#title' => $this->t('Paged Content Solr Filter Query'),
          '#description' => $this->t('Enter a string representing a query to use to filter pages from Solr results.'),
          '#default_value' => $get_default_value('islandora_paged_content_solr_fq'),
          '#states' => [
            'invisible' => [
              ':input[name="islandora_paged_content_hide_pages_solr"]' => [
                'checked' => FALSE,
              ],
            ],
          ],
        ],
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

}
