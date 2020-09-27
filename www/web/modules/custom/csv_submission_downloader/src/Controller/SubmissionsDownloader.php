<?php
/**
 * @file
 * Contains \Drupal\hello_world\Controller\HelloController.
 */
namespace Drupal\csv_submission_downloader\Controller;
use Symfony\Component\HttpFoundation\Response;



class SubmissionsDownloader
{


// Function to return csv file as resonse
  public function content()
  {
    // Get ids of all submission entities.
    $ids = \Drupal::entityQuery('submission')->execute();

    // get storage interface for paragraph entity type
    $para_storage = \Drupal::entityTypeManager()->getStorage('paragraph');

    // Load all submission entities
    $submissions = \Drupal::entityTypeManager()->getStorage('submission')->loadMultiple($ids);


    if (sizeof($submissions)==0) {
      return array(
        '#markup' => 'No Submissions are available to download.',
      );
    }

    // Load all question entities
    $question_storage = \Drupal::entityTypeManager()->getStorage('test_question');

    // to hold the row of csv file
    $row_data = "";

    //
    $entity_name = "";


    // Header row for csv file
    $header_label = 'submission_id,test,user,start_time,end_time,max_score,answers'."\n";


    // Iterate over submission ids
    foreach ($submissions as $id => $entity)
    {


      // Get field_submission_answers
      $ques_ans_ids = $entity->get('field_submission_answers');

      // Get field_test_round and load it
      $test_round = \Drupal::entityTypeManager()->getStorage('qualification_test_round')->load($entity->get('field_test_round')->target_id);
      
      if(is_null($test_round)) {
      	continue;
      }
      

      // Get number of question from the test round
      $max_question_count = $test_round->countQuestions();

      // Variable to track question in test round
      $temp_count = 0;

      // String to store answers in the form of <question_no>:submitted_answer
      $qa_string = "";

      // Iterate over each submitted ansnwer
      foreach($ques_ans_ids as $qaid => $qaobject)
        {
          // Access paragraph containting question_id and submitted_answer pair
          $paragraph = $para_storage->load($qaobject->target_id);

          // Check for number of questions not more than maximum number of questions.
          if ($temp_count < $max_question_count)
            {
              // add | after adding first question-answer pair to string
              if ($temp_count > 0) {
                $qa_string = $qa_string."|";
              }

              // Add question number, submitted answer to the string
              // $qa_string = $qa_string.(string)($temp_count+1).":".$paragraph->get('field_filler_answers')->getString();

              ############# Add question number, submitted answer to the string (updated version)
              ### Add qualification standard of each question to downloaded data


              if (isset($paragraph->get('field_question')->entity->get('field_qualification_standard')->entity)) {
                $qa_string = $qa_string.(string)($temp_count+1).":".$paragraph->get('field_filler_answers')->getString().":".$paragraph->get('field_question')->entity->get('field_qualification_standard')->entity->getName();
              } else {
                $qa_string = $qa_string.(string)($temp_count+1).":".$paragraph->get('field_filler_answers')->getString().":"."None";
              }





              // Increase temp_count
              $temp_count = $temp_count + 1;
            }

        }

        // Load user who submitted the test
        $filler = \Drupal::entityTypeManager()->getStorage('user')->load($entity->get('field_filler')->target_id);

        // create a row of data for current submission
        $row_data = $row_data.$id.",".$entity->getName().",".$filler->getUserName().",".$entity->get('field_started_answering')->getString().",".$entity->get('field_finished_answering')->getString().",".$entity->get('field_max_possible_score')->getString().",".$qa_string."\n";

        // create an array for the values to write to csv file
        $csv_line = [$id,$entity->getName(),$filler->getUserName(),$entity->get('field_started_answering')->getString(),$entity->get('field_finished_answering')->getString(),$entity->get('field_max_possible_score')->getString(),$qa_string];

      }



      // Create HTTP response
      $response = new Response();

      // Set header to send csv file as response
      $response->headers->set('Content-type','text/csv');
      $response->headers->set('Content-Disposition','attachment; filename="submmision_data.csv"');

      // add header label with data
      $row_data = $header_label.$row_data;



      // Set content to csv data
      $response->setContent($row_data);

      // return response
      return $response;



  }
}
