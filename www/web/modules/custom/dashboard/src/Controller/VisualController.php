<?php
/**
 * @file
 * Contains \Drupal\dashboard\Controller\DashController.
 */
namespace Drupal\dashboard\Controller;
use Drupal\questions\Entity\TestQuestion;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;


class VisualController {
  # array to store data
  public function content() {
    $tests = array();
    $fillers = array();
    $duration = array();
    $sub_dates = array();

    $ids = \Drupal::entityQuery('submission')->execute();

    // get storage interface for paragraph entity type
    $para_storage = \Drupal::entityTypeManager()->getStorage('paragraph');

    // Load all submission entities
    $submissions = \Drupal::entityTypeManager()->getStorage('submission')->loadMultiple($ids);

    $no_submissions = 0;

    if (sizeof($submissions)==0) {
      return array(
        '#markup' => 'There are no submissions.',
      );
    }

    // Load all question entities
    $question_storage = \Drupal::entityTypeManager()->getStorage('test_question');

    // to hold the row of csv file
    $row_data = "";

    //
    $entity_name = "";

    print($base_url);
    // Header row for csv file
    // $header_label = 'submission_id,test,user,start_time,end_time,max_score,answers'."\n";
    $header_label = 'test,user,answers'."\n";

    $headers = ['Test-name','Person','Overall-score','Dashboard'];
    $rows = array();

    // Iterate over submission ids
    foreach ($submissions as $id => $entity)
    {




      // Get field_submission_answers
      $ques_ans_ids = $entity->get('field_submission_answers');

      // Get field_test_round and load it
      $test_round = \Drupal::entityTypeManager()->getStorage('qualification_test_round')->load($entity->get('field_test_round')->target_id);
      $base_test = $test_round->get('field_base_test')->entity;
      $qual_std = $base_test->get('field_qualification_standard')->target_id;
      $filler = \Drupal::entityTypeManager()->getStorage('user')->load($entity->get('field_filler')->target_id);
      $child_tids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('qualification_standards', $qual_std, NULL, TRUE);




      $test_standard = $base_test->get('field_qualification_standard')->entity;
      if (isset($base_test->get('field_qualification_standard')->entity)) {
        $standard_name = $test_standard->getName();
        if ($standard_name == 'Õpetaja digipädevuste mudel 2020')
        {

          $rows [] = [$base_test->getName(),$filler->getUserName(),10,'link'];


          $qual_std = $base_test->get('field_qualification_standard')->target_id;
          $child_tids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('qualification_standards', $qual_std, 1, TRUE);
          //dpm($child_tids);

          foreach ($child_tids as $id => $term)
          {
            //print($term->getName());
            //print(json_encode($term->id()));
            //print($term->depth);
            $sub_dims = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('qualification_standards', $term->id(), 1, TRUE);
            foreach ($sub_dims as $id1 => $term1)
            {
              //print($term1->getName());
            }

          }
        }




      }


      /***
       * $msg = "Test_Round:".$test_round->id();
       * print("Submission:".$id.$msg);
       * // get all questions
       * $questions = $test_round->getAllQuestions();
       * foreach ($questions as $question) {
       * // print(json_encode($question->get('field_score')->getValue()));
       * $msg = "Question_id:".$question->id();
       *
       * print(json_encode($msg));
       *
       * $test_question = \Drupal::entityTypeManager()->getStorage('test_question')->load($question->id());
       *
       * $question_standard = $test_question->get('field_qualification_standard');
       *
       * print(json_encode($question_standard->entity->getName()));
       *
       *
       *
       * }
       */
      // Get number of question from the test round
      $max_question_count = $test_round->countQuestions();

      // Variable to track question in test round
      $temp_count = 0;

      // String to store answers in the form of <question_no>:submitted_answer
      $qa_string = "";

      // Iterate over each submitted ansnwer
      foreach ($ques_ans_ids as $qaid => $qaobject) {
        // Access paragraph containting question_id and submitted_answer pair
        $paragraph = $para_storage->load($qaobject->target_id);
        #print($paragraph->get('field_filler_answers'));

        // Check for number of questions not more than maximum number of questions.
        if ($temp_count < $max_question_count) {
          // add | after adding first question-answer pair to string
          if ($temp_count > 0) {
            $qa_string = $qa_string . "|";
          }

          // Add question number, submitted answer to the string
          /*
          if (isset($paragraph->get('field_question')->entity->get('field_qualification_standard')->entity)) {
            $qa_string = $qa_string.(string)($temp_count+1).":".$paragraph->get('field_filler_answers')->getString().":".$paragraph->get('field_question')->entity->get('field_qualification_standard')->entity->getName();
          } else {
            $qa_string = $qa_string.(string)($temp_count+1).":".$paragraph->get('field_filler_answers')->getString().":"."None";
          }
          */
          if ($id == 11) {


            // print($paragraph->get('field_question')->entity->get('field_qualification_standard')->entity->getName());

            $type = $paragraph->get('field_question')->entity->get('type')->getValue();
            if ($type[0]['target_id'] == 'checkbox') {
              $answers = $paragraph->get('field_question')->entity->get('field_checkbox_answers')->getValue();
              //print(json_encode($answers));
              /*
              foreach($answers as $id=>$obj) {
                $submission_answer = $para_storage->load($obj['target_id']);
                print(json_encode($submission_answer->get('field_correct')->getValue()));
                //print(json_encode($submission_answer->get('field_options')->getValue()));
              }
              */
            }
          }

          /*
           // Add question number, submitted answer to the string
           if ($id == 11) {
             if (isset($paragraph->get('field_question')->entity->get('field_qualification_standard')->entity)) {
               //print($paragraph->get('field_question')->entity->id());
               //print('{');
               // print($paragraph->get('field_question')->entity->get('field_qualification_standard')->entity->getName());
               //  print('}');
               $q_para = $para_storage->load($paragraph->get('field_question')->entity->id());
               //  print(json_encode($paragraph->get('field_score')->getValue()));
               //print(json_encode($para_storage->load($paragraph->get('field_question')->entity->get('field_options'))));

               foreach ($paragraph->get('field_filler_answers') as $aid => $aob) {

                 $ans_string = $aob->getString();
                 //print($ans_string);
                 foreach ($answers as $id => $obj) {
                   $submission_answer = $para_storage->load($obj['target_id']);
                   //print(json_encode($submission_answer->get('field_correct')->getValue()));
                   $opt_string = $submission_answer->get('field_options')->getValue();
                   //print($opt_string[0]['value']);
                   if ($ans_string == $opt_string[0]['value']) {
                     print('Answer Matched');
                   }


                 }
                 print("\n");
               }
               $qa_string = $qa_string . "\n" . (string)($paragraph->get('field_question')->entity->id()) . ":" . (string)($paragraph->get('field_filler_answers')->getString()) . ":" . $paragraph->get('field_question')->entity->get('field_qualification_standard')->entity->getName();
             } else {
               $qa_string = $qa_string . (string)($paragraph->get('field_question')->entity->id()) . ":" . $paragraph->get('field_filler_answers') . ":None";
             }
           }
           */


          ################# Added new code to download the qualification standard for each question

          #print($paragraph->get('field_question')->entity->get('field_qualification_standard')->entity->getName());


          ##########################################################################################################

          // Increase temp_count
          $temp_count = $temp_count + 1;
        }

      }

      // Load user who submitted the test
      $filler = \Drupal::entityTypeManager()->getStorage('user')->load($entity->get('field_filler')->target_id);

      // create a row of data for current submission
      // $row_data = $row_data.$id.",".$entity->getName().",".$filler->getUserName().",".$entity->get('field_started_answering')->getString().",".$entity->get('field_finished_answering')->getString().",".$entity->get('field_max_possible_score')->getString().",".$qa_string."\n";

      if ($id == 11) {
        $row_data = $qa_string . "\n";
      }

      # print($row_data);
      // create an array for the values to write to csv file
      // $csv_line = [$id,$entity->getName(),$filler->getUserName(),$entity->get('field_started_answering')->getString(),$entity->get('field_finished_answering')->getString(),$entity->get('field_max_possible_score')->getString(),$qa_string];

      $csv_line = [$entity->getName(), $filler->getUserName(), $qa_string];


      ######################### For Dashboard ############

      $tests[] = $entity->getName();

      $fillers[] = $filler->getUserName();
      # $duration[] = $entity->get('field_finished_answering')->diff($entity->get('field_started_answering'))->getString();
      $sub_dates[] = $entity->get('field_finished_answering')->getString();


      ####################################################
    }
    return [
      '#type' => 'table',
      '#hdeader' => $headers,
      '#rows' => $rows,
    ];
    /*
    return array(
  '#markup' => 'Hello Everyone'
);
*/
    /*
        return array(
          '#theme' => 'chart_template',
          '#title' => 'Dashboard',
          '#no_submissions' => $no_submissions ,
          '#sub_dates' => $sub_dates,

        );
    */
  }

}
