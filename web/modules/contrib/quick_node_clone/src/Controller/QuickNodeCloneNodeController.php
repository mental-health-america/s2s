<?php

namespace Drupal\quick_node_clone\Controller;

use Drupal\quick_node_clone\Entity\QuickNodeCloneEntityFormBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Controller\NodeController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Quick Node Clone Node routes.
 */
class QuickNodeCloneNodeController extends NodeController {

  /**
   * The entity form builder.
   *
   * @var \Drupal\quick_node_clone\Form\QuickNodeCloneEntityFormBuilder
   */
  protected $qncEntityFormBuilder;

  /**
   * Constructs a NodeController object.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(DateFormatterInterface $date_formatter, RendererInterface $renderer, QuickNodeCloneEntityFormBuilder $entity_form_builder) {
    parent::__construct($date_formatter, $renderer);
    $this->qncEntityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('quick_node_clone.entity.form_builder')
    );
  }

  /**
   * Retrieves the entity form builder.
   *
   * @return \Drupal\quick_node_clone\Form\QuickNodeCloneFormBuilder
   *   The entity form builder.
   */
  protected function entityFormBuilder() {
    return $this->qncEntityFormBuilder;
  }

  /**
   * Provides the node submission form.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type entity for the node.
   *
   * @return array
   *   A node submission form.
   */
  public function cloneNode($node) {
    $parent_node = $this->entityTypeManager()->getStorage('node')->load($node);
    if(!empty($parent_node)){
      $form = $this->entityFormBuilder()->getForm($parent_node, 'quick_node_clone');
      return $form;
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * The _title_callback for the node.add route.
   *
   * @param int $node_id
   *   The current node id.
   *
   * @return string
   *   The page title.
   */
  public function clonePageTitle($node) {
    $parent = Node::load($node);
    return $this->t('Clone of "@node"', array(
      '@node' => $parent->getTitle()
      )
    );
  }

}
