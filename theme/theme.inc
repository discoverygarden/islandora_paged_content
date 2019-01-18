<?php

/**
 * @file
 * Theme hooks.
 */

require_once dirname(__FILE__) . '/../includes/utilities.inc';

use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Theme the page navigator.
 */
function theme_islandora_paged_content_page_navigator(array $variables) {
  $object = $variables['object'];
  $results = $object->relationships->get(ISLANDORA_RELS_EXT_URI, 'isPageOf');
  $result = reset($results);
  $parent = $result ? islandora_object_load($result['object']['value']) : FALSE;
  $pages = $parent ? islandora_paged_content_get_pages($parent) : [];
  $pages = array_keys($pages);
  if (empty($pages)) {
    $pages[] = $object->id;
  }
  $index = array_search($object->id, $pages);
  $previous_page = isset($pages[$index - 1]) ? $pages[$index - 1] : NULL;
  $next_page = isset($pages[$index + 1]) ? $pages[$index + 1] : NULL;
  $links = [];
  if ($previous_page) {
    $links[] = [
      'title' => t('Prev'),
      'url' => Url::fromRoute('islandora.view_object', ['object' => $previous_page], ['absolute' => TRUE]),
    ];
  }
  if ($next_page) {
    $links[] = [
      'title' => t('Prev'),
      'url' => Url::fromRoute('islandora.view_object', ['object' => $next_page], ['absolute' => TRUE]),
    ];
  }
  if ($parent) {
    $links[] = [
      'title' => t('All Pages'),
      'url' => Url::fromRoute('islandora.view_object', ['object' => $parent->id], ['absolute' => TRUE]),
    ];
  }
  $pre_render_links = [
    '#theme' => 'links',
    '#links' => $links,
    '#title' => '<strong>' . t('Page') . '</strong>',
    '#attributes' => [
      'class' => [
        'links',
        'inline',
      ],
    ],
  ];
  return \Drupal::service('renderer')->render($pre_render_links);
}

/**
 * Implements template_preprocess_theme().
 */
function template_preprocess_swaptable(array &$variables) {
  global $pager_page_array, $pager_total, $pager_total_items, $pager_limits;
  Element::setAttributes($variables['element'], ['id', 'name']);
  $table = &$variables['element'];
  $variables['pager'] = swaptable_pagers($table['#pager'], count($table['#rows']));
  $variables['table']['left'] = swaptable_table('left', $table, $variables['pager']['left']);
  $variables['table']['right'] = swaptable_table('right', $table, $variables['pager']['right']);
  $base = $table['#id'];
  $variables['#attached']['drupalSettings']['swapTable'][$base] = [
    'name' => $table['#name'],
    'order' => $table['order']['#value'],
    'modified' => $table['modified']['#value'],
  ];

}

/**
 * Builds the descriptions of both pagers for use in the swaptable.
 *
 * @param array $pager
 *   The general description of the pagers.
 * @param int $total
 *   The total number of items to page through.
 *
 * @return array
 *   The description of both pagers for use in the swaptable.
 */
function swaptable_pagers(array $pager, $total) {
  return [
    'left' => swaptable_pager(
      $pager['element']['left'],
      $pager['tags'],
      $pager['quantity'],
      $pager['limit'],
      $total
    ),
    'right' => swaptable_pager(
      $pager['element']['right'],
      $pager['tags'],
      $pager['quantity'],
      $pager['limit'],
      $total
    ),
  ];
}

/**
 * Builds a description of a pager for use in the swaptable.
 *
 * @param int $element
 *   The unique id for the pager.
 * @param array $tags
 *   The tags definition to use when rendering the pager.
 * @param int $quantity
 *   The number of pages to display as links.
 * @param int $limit
 *   The number of items to show on a page.
 * @param int $total
 *   The total number of items to page through.
 *
 * @return array
 *   The pager definition for use in the swaptable.
 */
function swaptable_pager($element, array $tags, $quantity, $limit, $total) {
  $page = pager_default_initialize($total, $limit, $element);
  return [
    '#theme' => 'pager',
    '#element' => $element,
    '#tags' => $tags,
    '#quantity' => $quantity,
    '#parameters' => [],
    '#route_name' => \Drupal::routeMatch()->getRouteName(),
    '#route_parameters' => \Drupal::routeMatch()->getRawParameters()->all(),
    '#limit' => $limit,
    '#page' => $page,
  ];
}

/**
 * Prepares the rows to be rendered in a table.
 *
 * @param string $position
 *   The position of the table either 'left' or 'right'.
 * @param array $items
 *   The description of each row of the table where the keys are the unique
 *   identifers for each row.
 * @param array $ordering
 *   The ordering in which to display the given rows.
 * @param array $modified
 *   The rows which have been modified by the user directly.
 * @param array $pager
 *   The description of the pager to attach to this table.
 *
 * @return array
 *   The rows definition for the table.
 */
function swaptable_table_rows($position, array $items, array $ordering, array $modified, array $pager) {
  $rows = [];
  $offset = $pager['#page'] * $pager['#limit'];
  $ordering = array_slice($ordering, $offset, $pager['#limit']);
  $original_ordering = Element::children($items);
  foreach ($ordering as $i => $item) {
    $row = $items[$item];
    $data = [];
    $data[] = [
      'data' => $offset + $i + 1,
      'class' => ['ordering'],
    ];
    $data[] = [
      'data' => array_search($item, $original_ordering) + 1,
      'class' => ['original-ordering', 'swaptable-hide'],
    ];
    foreach (Element::children($row) as $j) {
      $cell = $row[$j];
      $data[] = [
        'data' => empty($cell['#printed']) ? \Drupal::service("renderer")->render($cell) : $cell['#children'],
        'class' => [$j],
      ];
    }
    $rows[] = [
      'id' => str_replace(':', '-', "{$position}-{$item}"),
      'class' => in_array($item, $modified) ? ['ui-modified'] : [],
      'data' => $data,
    ];
  }
  return $rows;
}

/**
 * Renders a single swap table where $position, denotes 'left' or 'right'.
 *
 * @param string $position
 *   The position of the table either 'left' or 'right'.
 * @param array $table
 *   The description of the table.
 * @param array $pager
 *   The description of the pager to attach to this table.
 *
 * @return string
 *   The rendered table.
 */
function swaptable_table($position, array $table, array $pager) {
  $table['#attributes']['id'] = "{$table['#id']}-{$position}";
  $table['#attributes']['name'] = $position;
  $table['#attributes']['valign'] = 'top';
  $header = [
    [
      'data' => t('Page #'),
      'class' => ['ordering'],
    ],
    [
      'data' => t('*Original Page #'),
      'class' => ['original-ordering', 'swaptable-hide'],
    ],
  ];
  return [
    '#theme' => 'table',
    '#header' => array_merge($header, $table['#header']),
    '#rows' => swaptable_table_rows($position, $table['#rows'], $table['order']['#value'], $table['modified']['#value'], $pager),
    '#attributes' => $table['#attributes'],
  ];
}
