<?php
/**
 * @file
 * Contains \Drupal\dashboard\Controller\DashController.
 */
namespace Drupal\dashboard\Controller;
use Drupal\questions\Entity\TestQuestion;
use Drupal\submissions\Entity\Submission;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;


class DashController
{

  # array to store data
  public function content()
  {

    # All Submission Ids
    $ids = \Drupal::entityQuery('submission')->execute();

    // get storage interface for paragraph entity type
    $para_storage = \Drupal::entityTypeManager()->getStorage('paragraph');

    // Load all submission entities
    $submissions = \Drupal::entityTypeManager()->getStorage('submission')->loadMultiple($ids);

    if (sizeof($submissions) == 0) {
      return array(
        '#markup' => 'There are no submissions.',
      );
    }


    $headers = ['Test-name', 'Person', 'Overall-score', 'Dashboard'];
    $rows = array();

    // Iterate over submission ids
    foreach ($submissions as $id => $entity) {

      // Get all submitted answers for submissions
      $ques_ans_ids = $entity->get('field_submission_answers');

      // Get field_test_round and load it
      $test_round = \Drupal::entityTypeManager()->getStorage('qualification_test_round')->load($entity->get('field_test_round')->target_id);



      // Get base test of submission
      $base_test = $test_round->get('field_base_test')->entity;

      // Get qualification standard of base test
      $qual_std = $base_test->get('field_qualification_standard')->target_id;

      // Get the submitter's id
      $filler = \Drupal::entityTypeManager()->getStorage('user')->load($entity->get('field_filler')->target_id);


      // Access children terms
      $child_tids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('qualification_standards', $qual_std, NULL, TRUE);


      // check if base test is associated with qualification standard
      if (isset($base_test->get('field_qualification_standard')->entity)) {


        $test_standard = $base_test->get('field_qualification_standard')->entity;


        // Get the standard name
        $standard_name = $test_standard->getName();

        // Process only base test with all slider questions
        if ($standard_name == 'Õpetaja digipädevuste mudel 2020' && $filler->getUserName()=='katiaus') {
          //print($filler->getUserName());
          // Create rows for table
          $rows [] = [$base_test->getName(), $filler->getUserName(), 10, 'link'];


          // Access the qualification standard id
          $qual_std = $base_test->get('field_qualification_standard')->target_id;



          // array to store scores categories wise
          $qual_scores = array();

          $qual_scores[$qual_std] = [
              'score' => 0,
              'level' => 0,
              'parent' => NULL,
              'label' => $standard_name,
              'children_count' => 0
            ];

          //
          $child_tids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('qualification_standards', $qual_std, NULL, TRUE);
          //dpm($child_tids);

          // maximum depth of qualification taxonomy tree
          $max_depth = 0;

          foreach ($child_tids as $id => $term) {
            $level = 0;
            $level = $term->depth + 1;
            if ($max_depth < $level) {
              $max_depth = $level;
            }

            $qual_scores[$term->parent->entity->id()]['children_count']++;

            $qual_scores[$term->id()] = [
              'parent' => $term->parent->entity->id(),
              'score' => 0,
              'level' => $level,
              'label' => $term->getName(),
              'children_count' => 0
            ];
            //print($term->getName());
            //print(json_encode($term->id()));
            //print($term->depth);
          }

          // Get all submitted answers
          $ques_ans_ids = $entity->get('field_submission_answers');

          // iterate over each submitted answer
          foreach ($ques_ans_ids as $qaid => $qaobject) {
            // Access paragraph containting question_id and submitted_answer pair
            $paragraph = $para_storage->load($qaobject->target_id);

            // question type
            $type = $paragraph->get('field_question')->entity->get('type')->getValue();

            // qualification standard-dimension associated with question
            $q_dim = $paragraph->get('field_question')->entity->get('field_qualification_standard')->entity->id();

            // Question score
            $max_score = $paragraph->get('field_score')->getValue()[0]['value'];



            if ($type[0]['target_id'] == 'checkbox') {
              // Code to score checkbox type questions
              $answers = $paragraph->get('field_question')->entity->get('field_checkbox_answers')->getValue();
            }

            if ($type[0]['target_id'] == 'slider') {

              // Code to score checkbox type questions
              $q = $paragraph->get('field_question')->entity;
              $max_steps = $q->get('field_end')->getValue()[0]['value'];

             // print('['.$paragraph->get('field_question')->entity->id().' '.$paragraph->get('field_filler_answers')->getString().' '.$q_dim.']');

              $eval_score = intval($paragraph->get('field_filler_answers')->getString());

              $eval_score = $eval_score * 100/$max_steps;

              // Case where more than one question attached to one single sub-dimension
              if ($qual_scores[$q_dim]['score'] != 0) {
                $pre_score = $qual_scores[$q_dim]['score'];
                $qual_scores[$q_dim]['score'] = ($pre_score + $eval_score)/2;
              }
              else {
                $qual_scores[$q_dim]['score'] = $eval_score;
              }
              //print(json_encode($qual_scores[$q_dim]));
            }

            //dpm($qual_scores);
          }

          // Process scores to compute score for  sub-dimensions
          $processed_level = $max_depth;
          for($i=$processed_level;$i>0;$i--) {
            // this for loop add the score to upwards
            foreach($qual_scores as $sub_dim){
              if ($sub_dim['level']==$i){

                $parent = $sub_dim['parent'];
                $parent_score = $qual_scores[$parent]['score'];

                // Update the parent score
                $qual_scores[$parent]['score'] = ($sub_dim['score'] + $parent_score);
              }

            }

            // This for loop normalize the score in the range 0-100
            foreach($qual_scores as  $id => $sub_dim){
              if ($sub_dim['level']==$i-1 ){

                $qual_scores[$id]['score'] = $qual_scores[$id]['score']/$qual_scores[$id]['children_count'];
              }
            }
          }

          # Array to send to twig
          $score_values = array();
          foreach($qual_scores as  $id => $sub_dim){

            $score_values[] = [
              "id" => $id,
              "value" => $sub_dim
            ];
          }

        }



      }



        ######################### For Dashboard ############

        $tests[] = $entity->getName();

        $fillers[] = $filler->getUserName();
        # $duration[] = $entity->get('field_finished_answering')->diff($entity->get('field_started_answering'))->getString();
        $sub_dates[] = $entity->get('field_finished_answering')->getString();


        ####################################################
      }


          return array(
            '#theme' => 'chart_template',
            '#title' => 'Dashboard V2',
            '#qual_scores' => $qual_scores ,

          );

    }

  public function view_list()
  {

    # All Submission Ids
    $ids = \Drupal::entityQuery('submission')->execute();

    // get storage interface for paragraph entity type
    $para_storage = \Drupal::entityTypeManager()->getStorage('paragraph');

    // Load all submission entities
    $submissions = \Drupal::entityTypeManager()->getStorage('submission')->loadMultiple($ids);

    if (sizeof($submissions) == 0) {
      return array(
        '#markup' => 'There are no submissions.',
      );
    }


    $headers = ['Test-name', 'Person', 'Overall-score', 'Dashboard'];
    $rows = array();

    // Iterate over submission ids
    foreach ($submissions as $id => $entity) {

      // Get all submitted answers for submissions
      $ques_ans_ids = $entity->get('field_submission_answers');

      // Get field_test_round and load it
      $test_round = \Drupal::entityTypeManager()->getStorage('qualification_test_round')->load($entity->get('field_test_round')->target_id);



      // Get base test of submission
      $base_test = $test_round->get('field_base_test')->entity;

      // Get qualification standard of base test
      $qual_std = $base_test->get('field_qualification_standard')->target_id;

      // Get the submitter's id
      $filler = \Drupal::entityTypeManager()->getStorage('user')->load($entity->get('field_filler')->target_id);


      // Access children terms
      $child_tids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('qualification_standards', $qual_std, NULL, TRUE);


      // check if base test is associated with qualification standard
      if (isset($base_test->get('field_qualification_standard')->entity)) {


        $test_standard = $base_test->get('field_qualification_standard')->entity;


        // Get the standard name
        $standard_name = $test_standard->getName();

        // Process only base test with all slider questions
        if ($standard_name == 'Õpetaja digipädevuste mudel 2020') {
          //print($filler->getUserName());
          // Create rows for table
          $url = Url::fromRoute('dashboard.graphs',['submission' =>$entity->id()]);

          $link = Link::fromTextandUrl('Dashboard',$url);
          $rows [] = [$base_test->getName(), $filler->getUserName(), 10, $link];


          // Access the qualification standard id
          $qual_std = $base_test->get('field_qualification_standard')->target_id;














        }



      }




    }


    return [
      '#type' => 'table',
      '#hdeader' => $headers,
      '#rows' => $rows,
    ];

  }


  public function visualize(Submission $submission)
  {


    $entity = $submission;
    // get storage interface for paragraph entity type
    $para_storage = \Drupal::entityTypeManager()->getStorage('paragraph');


      // Get all submitted answers for submissions
      $ques_ans_ids = $entity->get('field_submission_answers');

      // Get field_test_round and load it
      $test_round = \Drupal::entityTypeManager()->getStorage('qualification_test_round')->load($entity->get('field_test_round')->target_id);



      // Get base test of submission
      $base_test = $test_round->get('field_base_test')->entity;

      // Get qualification standard of base test
      $qual_std = $base_test->get('field_qualification_standard')->target_id;

      // Get the submitter's id
      $filler = \Drupal::entityTypeManager()->getStorage('user')->load($entity->get('field_filler')->target_id);


      // Access children terms
      $child_tids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('qualification_standards', $qual_std, NULL, TRUE);


      // check if base test is associated with qualification standard
      if (isset($base_test->get('field_qualification_standard')->entity)) {


        $test_standard = $base_test->get('field_qualification_standard')->entity;


        // Get the standard name
        $standard_name = $test_standard->getName();

        $user_name = $filler->getUserName();

        $submission_date = $entity->get('field_finished_answering')->getString();

        // Process only base test with all slider questions
        if ($standard_name == 'Õpetaja digipädevuste mudel 2020') {
          //print($filler->getUserName());
          // Create rows for table
          $rows [] = [$base_test->getName(), $filler->getUserName(), 10, 'link'];


          // Access the qualification standard id
          $qual_std = $base_test->get('field_qualification_standard')->target_id;



          // array to store scores categories wise
          $qual_scores = array();

          $qual_scores[$qual_std] = [
            'score' => 0,
            'level' => 0,
            'parent' => NULL,
            'label' => $standard_name,
            'children_count' => 0
          ];

          //
          $child_tids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('qualification_standards', $qual_std, NULL, TRUE);
          //dpm($child_tids);

          // maximum depth of qualification taxonomy tree
          $max_depth = 0;

          foreach ($child_tids as $id => $term) {
            $level = 0;
            $level = $term->depth + 1;
            if ($max_depth < $level) {
              $max_depth = $level;
            }

            $qual_scores[$term->parent->entity->id()]['children_count']++;

            $qual_scores[$term->id()] = [
              'parent' => $term->parent->entity->id(),
              'score' => 0,
              'level' => $level,
              'label' => $term->getName(),
              'children_count' => 0
            ];
            //print($term->getName());
            //print(json_encode($term->id()));
            //print($term->depth);
          }

          // Get all submitted answers
          $ques_ans_ids = $entity->get('field_submission_answers');

          // iterate over each submitted answer
          foreach ($ques_ans_ids as $qaid => $qaobject) {
            // Access paragraph containting question_id and submitted_answer pair
            $paragraph = $para_storage->load($qaobject->target_id);

            // question type
            $type = $paragraph->get('field_question')->entity->get('type')->getValue();

            // qualification standard-dimension associated with question
            $q_dim = $paragraph->get('field_question')->entity->get('field_qualification_standard')->entity->id();

            // Question score
            $max_score = $paragraph->get('field_score')->getValue()[0]['value'];



            if ($type[0]['target_id'] == 'checkbox') {
              // Code to score checkbox type questions
              $answers = $paragraph->get('field_question')->entity->get('field_checkbox_answers')->getValue();
            }

            if ($type[0]['target_id'] == 'slider') {

              // Code to score checkbox type questions
              $q = $paragraph->get('field_question')->entity;
              $max_steps = $q->get('field_end')->getValue()[0]['value'];

              // print('['.$paragraph->get('field_question')->entity->id().' '.$paragraph->get('field_filler_answers')->getString().' '.$q_dim.']');

              $eval_score = intval($paragraph->get('field_filler_answers')->getString());

              $eval_score = $eval_score * 100/$max_steps;

              // Case where more than one question attached to one single sub-dimension
              if ($qual_scores[$q_dim]['score'] != 0) {
                $pre_score = $qual_scores[$q_dim]['score'];
                $qual_scores[$q_dim]['score'] = ($pre_score + $eval_score)/2;
              }
              else {
                $qual_scores[$q_dim]['score'] = $eval_score;
              }
              //print(json_encode($qual_scores[$q_dim]));
            }

            //dpm($qual_scores);
          }

          // Process scores to compute score for  sub-dimensions
          $processed_level = $max_depth;
          for($i=$processed_level;$i>0;$i--) {
            // this for loop add the score to upwards
            foreach($qual_scores as $sub_dim){
              if ($sub_dim['level']==$i){

                $parent = $sub_dim['parent'];
                $parent_score = $qual_scores[$parent]['score'];

                // Update the parent score
                $qual_scores[$parent]['score'] = ($sub_dim['score'] + $parent_score);
              }

            }

            // This for loop normalize the score in the range 0-100
            foreach($qual_scores as  $id => $sub_dim){
              if ($sub_dim['level']==$i-1 ){

                $qual_scores[$id]['score'] = $qual_scores[$id]['score']/$qual_scores[$id]['children_count'];
              }
            }
          }




          return array(
            '#theme' => 'chart_template',
            '#title' => 'Dashboard',
            '#qual_scores' => $qual_scores ,
            '#user_name' => $user_name,
            '#sub_date' => $submission_date,

          );

        }
        else {
          return array(
            '#markup' => 'At the moment only slider based test is supported.',


          );
        }
      }





  }
  }
