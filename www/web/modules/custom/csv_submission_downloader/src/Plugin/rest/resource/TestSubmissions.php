<?php


namespace Drupal\csv_submission_downloader\Plugin\rest\resource;

use Drupal\qualification_test_rounds\Entity\QualificationTestRound;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;


/**
 * Provides a Demo Resource
 *
 * @RestResource(
 *   id = "test_submissions",
 *   label = @Translation("Test Results"),
 *   uri_paths = {
 *     "canonical" = "/demo_rest_api/test_submissions/{test_id}"
 *   }
 * )
 */
class TestSubmissions extends ResourceBase
{
  /**
   * Responds to entity GET requests.
   * @return \Drupal\rest\ResourceResponse
   */
  public function get(int $test_id)
  {


    // Fetching all test rounds ids
    $ids = \Drupal::entityQuery('qualification_test_round')->execute();

    // loading test rounds
    $test_rounds = \Drupal::entityTypeManager()->getStorage('qualification_test_round')->loadMultiple($ids);


    // Fetching data of all Submissions
    // Get ids of all submission entities.
    $sub_ids = \Drupal::entityQuery('submission')->condition('field_test_round', $test_id)->execute();


    $res = [
      'meta' => ['total_submission' => count($sub_ids)],
      'data' => [
        'submissions' => [],
      ],
    ];

    // get storage interface for paragraph entity type
    $para_storage = \Drupal::entityTypeManager()->getStorage('paragraph');

    // Load all submission entities
    $submissions = \Drupal::entityTypeManager()->getStorage('submission')->loadMultiple($sub_ids);

    foreach ($submissions as $id => $entity) {
      $submission_data = [];


      $submission_data = [
        'id' => $id,
        'submission_user_id'=> $entity->get('field_filler')->target_id,
        'answers' => [],
        'start_time' => $entity->get('field_started_answering')->getString(),
        'end_time' => $entity->get('field_finished_answering')->getString(),
      ];
      // Get field_submission_answers
      $ques_ans_ids = $entity->get('field_submission_answers');
      foreach ($ques_ans_ids as $qaid => $qaobject) {


        // Access paragraph containting question_id and submitted_answer pair
        $paragraph = $para_storage->load($qaobject->target_id);

        $submission_data['answers'][] = [
          'question_id' => $paragraph->get('field_question')->target_id,
          'answer' => $paragraph->get('field_filler_answers')->getString(),
        ];

        // End of fetching data of all submission
      }

      $res['data']['submissions'][] = $submission_data;

    }





    $response = ['message' => $res];
    return new ResourceResponse($response);
  }

}
