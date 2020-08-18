<?php

namespace Drupal\crowdsourcing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\node\NodeInterface;
use Drupal\comment\Entity\Comment;



/**
 * Class CommentCSV.
 *
 * @package Drupal\crowdsourcing\Controller
 */
class CommentCSV extends ControllerBase{

   public function build(NodeInterface $node) {
     // Start using PHP's built in file handler functions to create a temporary file.
     $handle = fopen('php://temp', 'w+');

     // Set up the header that will be displayed as the first line of the CSV file.// Blank strings are used for multi-cell values where there is a count of// the "keys" and a list of the keys with the count of their usage.
     $header = [
       'Sr. No',
       'Posted in',
       'Thoughts',
       'Author',
       'User ID',
       'IP Address',
       'Created On',
       'Updated on',
     ];
     // Add the header as the first line of the CSV.
     fputcsv($handle, $header);
     // Find and load all of the Comment nodes we are going to include
     $cids = \Drupal::entityQuery('comment')
            ->condition('entity_id', $node->id())
            ->condition('entity_type', 'node')
            ->sort('cid', 'ASC')
            ->execute();
      $comments = Comment::loadMultiple($cids);
      $i = 1;
     // Iterate through the nodes.  We want one row in the CSV per Comment.
     foreach ($comments as $comment) {
       // Build the array for putting the row data together.
       $data = $this->buildRow($comment, $node, $i);

       // Add the data we exported to the next line of the CSV>
       fputcsv($handle, array_values($data));
       $i++;
     }

     // Reset where we are in the CSV.
     rewind($handle);

     // Retrieve the data from the file handler.
     $csv_data = stream_get_contents($handle);

     // Close the file handler since we don't need it anymore.  We are not storing// this file anywhere in the filesystem.
     fclose($handle);

     // This is the "magic" part of the code.  Once the data is built, we can// return it as a response.
     $response = new Response();

     // By setting these 2 header options, the browser will see the URL// used by this Controller to return a CSV file called "comments-report.csv".
     $response->headers->set('Content-Type', 'text/csv');
     $response->headers->set('Content-Disposition', 'attachment; filename="comments-report.csv"');

     // This line physically adds the CSV data we created
     $response->setContent($csv_data);

     return $response;
   }

   private function buildRow($comment, $node, $i) {
    $ip_address = '';
    $uid = $comment->getOwnerId();
     if($comment->getOwner()->getDisplayname() == 'Anonymous'){
       $ip_address = $comment->getHostname();
       $uid = 0;
     }
     $data = [
       'serial_number' => $i,
       'posted_in' => $node->label(),
       'thoughts' => $comment->get('field_idea_comment')->value,
       'author' => $comment->getOwner()->getDisplayname(),
       'uid' => $uid,
       'ip_address' => $ip_address,
       'created_on' => \Drupal::service('date.formatter')->format($comment->getCreatedTime(), '$format'),
       'updated_on' => \Drupal::service('date.formatter')->format($comment->getChangedTime(), '$format'),
     ];

     return $data;
   }

  }
