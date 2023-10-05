<?php

namespace Drupal\Tests\node\Traits;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Provides methods to create node based on default settings.
 *
 * This trait is meant to be used only by test classes.
 */
trait NodeCreationTrait {

  /**
   * Get a node from the database based on its title.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $title
   *   A node title, usually generated by $this->randomMachineName().
   * @param $reset
   *   (optional) Whether to reset the entity cache.
   *
   * @return \Drupal\node\NodeInterface|false
   *   A node entity matching $title, FALSE when node with $title is not found.
   */
  public function getNodeByTitle($title, $reset = FALSE) {
    if ($reset) {
      \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    }
    // Cast MarkupInterface objects to string.
    $title = (string) $title;
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $title]);
    // Load the first node returned from the database.
    $returned_node = reset($nodes);
    return $returned_node;
  }

  /**
   * Creates a node based on default settings.
   *
   * @param array $values
   *   (optional) An associative array of values for the node, as used in
   *   creation of entity. Override the defaults by specifying the key and value
   *   in the array, for example:
   *
   *   @code
   *     $this->drupalCreateNode(array(
   *       'title' => t('Hello, world!'),
   *       'type' => 'article',
   *     ));
   *   @endcode
   *   The following defaults are provided, if the node has the field in
   *   question:
   *   - body: Random string using the default filter format:
   *     @code
   *       $values['body'][0] = array(
   *         'value' => $this->randomMachineName(32),
   *         'format' => filter_default_format(),
   *       );
   *     @endcode
   *   - title: Random string.
   *   - type: 'page'.
   *   - uid: The currently logged in user, or anonymous.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node entity.
   */
  protected function createNode(array $values = []) {
    // Populate defaults array.
    $values += [
      'title' => $this->randomMachineName(8),
      'type' => 'page',
    ];

    // Create node object.
    $node = Node::create($values);

    // If the node has a field named 'body', we assume it's a body field and
    // that the filter module is present.
    if (!array_key_exists('body', $values) && $node->hasField('body')) {
      $body = [
        'value' => $this->randomMachineName(32),
        'format' => filter_default_format(),
      ];
      $node->set('body', $body);
    }

    if (!array_key_exists('uid', $values)) {
      $user = User::load(\Drupal::currentUser()->id());
      if ($user) {
        $uid = $user->id();
      }
      elseif (method_exists($this, 'setUpCurrentUser')) {
        /** @var \Drupal\user\UserInterface $user */
        $user = $this->setUpCurrentUser();
        $uid = $user->id();
      }
      else {
        $uid = 0;
      }
      $node->set('uid', $uid);
    }
    $node->save();

    return $node;
  }

}
