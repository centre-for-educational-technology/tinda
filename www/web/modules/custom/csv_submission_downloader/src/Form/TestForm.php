<?php

namespace Drupal\csv_submission_downloader\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Drupal\paragraphs\Entity\Paragraph;
class TestForm extends FormBase {

   /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'test_form';

  }
    /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get ids of all test rounds
    $ids = \Drupal::entityQuery('qualification_test_round')->execute();

    // load test rounds
    $tests = \Drupal::entityTypeManager()->getStorage('qualification_test_round')->loadMultiple($ids);

    // options for select input
    $options = [];

    foreach($tests as $id => $entity){
       $options[$id] = $entity->getName();
    }


    // Form select input
    $form['qualification_test'] = array (
      '#type' => 'select',
      '#title' => $this->t('Select the qualitification test round?'),
      '#options' => $options,
      '#attributes' => [
            'class' => ['form-control'],
          ],
    );

    // Submit button
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Download Data'),
      '#button_type' => 'primary',
      '#prefix' => '<div class="col-auto">',
      '#suffix' => '</div>',
      '#attributes' => [
            'class' => ['btn btn-focus m-btn--pill pl-5 pr-5 mt-5'],
          ],

    );
    return $form;
  }

  // Function to get column options for Match interation type question
  protected function getCols(array $options) : array {
   $form_options = [];
   foreach ($options as $option) {
     $option = Paragraph::load($option['target_id']);
     if ($option) {
       $value = $option->get('field_match_row')->getValue()[0]['value'];;
       $form_options[$value] = $value;
     }
   }
   return $form_options;
 }

   /**
   * {@inheritdoc}
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
   // drupal_set_message($this->t('@can_name ,Your application is being submitted!', array('@can_name' => $form_state->getValue('candidate_name'))));

   // Header with all questions of test round
    $header="";

    // get storage interface for paragraph entity type
    $para_storage = \Drupal::entityTypeManager()->getStorage('paragraph');

    // Get submitted values
    $form_data = $form_state->getValues();

    // selected test round
    $test_round = $form_data['qualification_test'];


    //Load selected test round
    $round =  \Drupal::entityTypeManager()->getStorage('qualification_test_round')->load($test_round);

    // getting all questions from test round (Paragrah Type: Test Question)
    $questions = $round->getAllQuestions();

    foreach($questions as $qid => $que) {

      // question label
      $question_label = (string)($que->get('field_question')->entity->get('name')->value);

      // Replace command with dot
      $question_label = str_replace(',',' ',$question_label);

      // Accessing question type
      $question_type = $que->get('field_question')->entity->get('type')->getValue()['0']['target_id'];


      // Processing mach interaction
      if ($question_type === 'match_interaction')
      {

        // Get the question entity
        $question = $que->get('field_question')->entity;

        // Get the column options
        $columns = $this->getCols($question->get('field_row_options')->getValue());


        // Iterate through columns and add in header
        foreach($columns as $key => $value) {
          if ($header != '') {
            $header = $header.','.$question_label.'['.$key.']';
           } else {
            $header = $question_label.'['.$key.']';
          }


        }


      }

      // processing other question types
      else {

        if ($header != '') {
          $header = $header.','.$question_label;
         } else {
          $header = $question_label;
        }

      }



     }


     // Building rows for csv download

    $sub_ids = \Drupal::entityQuery('submission')->execute();




    $submissions = \Drupal::entityTypeManager()->getStorage('submission')->loadMultiple($ids);

    $all_rows = $header."\n";


    foreach ($submissions as $id => $entity)
    {

      $sub_round_id = $entity->get('field_test_round')->target_id;
      //print('sub test round:'.$sub_round_id);
      //print('\n');

      // Selecting submission with selected test round
      if ($sub_round_id == $test_round) {


        $row = "";
        // Get field_submission_answers
        $ques_ans_ids = $entity->get('field_submission_answers');

        // Iterate submitted answers
        foreach($ques_ans_ids as $qaid => $qaobject)
          {

            // Access paragraph containting question_id and submitted_answer pair
            $paragraph = $para_storage->load($qaobject->target_id);


            $q = $paragraph->get('field_question')->entity;

            $q_type = $paragraph->get('field_question')->entity->get('type')->getValue()['0']['target_id'];


            // Processing match interaction type
            if ($q_type === 'match_interaction')
            {
              $answers = $paragraph->get('field_filler_answers')->getString();

              // extracting answers from brackets
               preg_match_all("/\[([^\]]*)\]/", $answers, $matches);

               // Add answers to the row
               foreach ($matches[1] as $ans){

                if ($row != "") {
                 $row= $row.','.$ans;
                } else {
                   $row= $ans;

                }

              }



            }
            // processing other types
            else{
              if ($row != "") {
               $row= $row.','.$paragraph->get('field_filler_answers')->getString();
              } else {
                 $row= $paragraph->get('field_filler_answers')->getString();

              }


            }

          }
          // Adding non-empty rows to the final data
          if($row != "") {
            $row = $row."\n";
            $all_rows = $all_rows.$row;
          }

        }
      }



      // Create HTTP response
      $response = new Response();

      $fname = (string)($round->getName()).'_'.date(d_m_Y).'.csv';

      // Set header to send csv file as response
      $response->headers->set('Content-type','text/csv');
      $response->headers->set('Content-Disposition','attachment; filename="'.$fname.'"');


      // Set content to csv data
      $response->setContent($all_rows);

      // Setting resonse
      $form_state->setResponse($response);




 }

}
