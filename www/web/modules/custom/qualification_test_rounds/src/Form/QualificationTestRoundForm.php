<?php

namespace Drupal\qualification_test_rounds\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\qualification_test_rounds\EntityTrait;
use Drupal\qualification_tests\Entity\QualificationTest;
use Drupal\questions\Entity\TestQuestion;
use Drupal\questions\Entity\TestQuestionInterface;

/**
 * Form controller for Qualification Test Round edit forms.
 *
 * @ingroup qualification_test_rounds
 */
class QualificationTestRoundForm extends ContentEntityForm {

  /**
   * Indicator that shows if we are adding translation.
   *
   * @var bool
   */
  protected $addTranslation = false;
  use EntityTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\qualification_test_rounds\Entity\QualificationTestRound */
    $form = parent::buildForm($form, $form_state);

    $display = $this->getFormDisplay($form_state);
    $mode = $display->getMode();

    $form['#prefix'] = '<div id="qualification-base-test">';
    $form['#suffix'] = '</div>';

    if ($mode === 'add') {
      $form['revision_log_message']['#access'] = FALSE;
      $form['status']['#access'] = FALSE;

      $form['field_base_test']['widget'][0]['target_id']['#ajax'] = [
        'callback' => '::ajaxCallback',
        'event' => 'change',
        'wrapper' => 'qualification-base-test',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Looking for translations...'),
        ],
      ];

      if ($id = $form_state->getValue('field_base_test')) {
        $test = QualificationTest::load($id[0]['target_id']);
        // Find test translations.
        $languages = array_map(function ($item) {
          return $item->getName();
        }, $test->getTranslationLanguages());

        $form['langcode']['widget'][0]['value']['#options'] = $languages;
        $form['langcode']['widget'][0]['value']['#description'] = $this->t('Select in which language this test will be made.');
      }
      else {
        $form['langcode']['widget'][0]['#access'] = FALSE;
      }
    }
    elseif ($mode === 'default') {
      $form['field_test_round_questions']['widget']['add_more']['#access'] = FALSE;
      $form['field_base_test']['#disabled'] = TRUE;
      $this->formatSections($form, $form_state);

      // Language must be disabled for the round,
      // because it is set from Base test.
      $form['langcode']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = parent::validateForm($form, $form_state);

    $start = $form_state->getValue('field_start_time');
    $end = $form_state->getValue('field_end_time');

    // When creating the Test Round from base field,
    // these fields are not yet present.
    if ($start !== NULL || $end !== NULL) {

      $start_timestamp = $start[0]['value']->getTimeStamp();
      $end_timestamp = $end[0]['value']->getTimeStamp();

      if ($end_timestamp < $start_timestamp) {
        $form_state->setError($form['field_end_time'], t('Round cannot end before it starts.'));
      }

    }

    return $entity;
  }

  /**
   * Simple ajax callback.
   *
   * @param array $form
   *   Drupal form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   form.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Disable attributes that cannot be changed.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal form state.
   */
  protected function formatSections(array &$form, FormStateInterface $form_state) {
    $count = $form['field_test_round_questions']['widget']['#max_delta'];

    for ($x = 0; $x <= $count; $x++) {
      $form['field_test_round_questions']['widget'][$x]['subform']['field_title']['#disabled'] = TRUE;
      $form['field_test_round_questions']['widget'][$x]['subform']['field_randomise_questions']['#disabled'] = TRUE;
      $form['field_test_round_questions']['widget'][$x]['subform']['field_section_questions']['widget']['add_more']['#access'] = FALSE;

      $question_count = $form['field_test_round_questions']['widget'][$x]['subform']['field_section_questions']['widget']['#max_delta'];
      for ($i = 0; $i <= $question_count; $i++) {
        $form['field_test_round_questions']['widget'][$x]['subform']['field_section_questions']['widget'][$i]['subform']['field_question']['#disabled'] = TRUE;
        $form['field_test_round_questions']['widget'][$x]['subform']['field_section_questions']['widget'][$i]['subform']['field_required']['#disabled'] = TRUE;
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $display = $this->getFormDisplay($form_state);
    $mode = $display->getMode();
    $this->addTranslation = $form_state->getValue('source_langcode') ? ($form_state->getValue('source_langcode')['source'] ? true : false) : false;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label'
        . ' Qualification Test Round from a base test. Additional configuration'
        . ' is needed to allow applicants to use this round.',
          [
            '%label' => $entity->label(),
          ]
        ));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Qualification Test Round.', [
          '%label' => $entity->label(),
        ]));
    }
    if ($mode === 'add') {
      $base_test = $form_state->getValue('field_base_test');
      $language = $form_state->getValue('langcode') ? $form_state->getValue('langcode')[0]['value'] : NULL;
      $this->importFromTest($base_test, $language);
      $this->entity->set('status', NULL);
      $form_state->setRedirect('entity.qualification_test_round.edit_form', ['qualification_test_round' => $entity->id()]);
    }
    else {
      $form_state->setRedirect('view.qualification_test_rounds.qualification_test_round_collection');
    }
  }

  /**
   * Import settings, questions etc from test.
   *
   * @param array $base_test
   *   Test where to import questions.
   * @param null|string $language
   *   Language code of the test.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function importFromTest(array $base_test, ?string $language) {
    $test = QualificationTest::load($base_test[0]['target_id']);
    if ($test->hasTranslation($language)){
      $test = $test->getTranslation($language);
    }

    $test_questions = $test->get('field_test_questions')->getValue();
    $test_questions_ids = array_map(function ($item) {
      return $item['target_id'];
    }, $test_questions);

    if (!$this->addTranslation) {
      $this->entity->set('field_test_round_questions', $this->createQuestionSections($test_questions_ids, $language));
    } else {
      $this->addTranslationToSections($test_questions_ids, $language);
    }

    $this->entity->set('langcode', ($language ? $language : $test->get('langcode')->getValue()));
    $this->entity->set('field_description', $test->get('field_description')->getValue());
    $this->entity->set('field_time_to_completion', $test->get('field_time_to_completion')->getValue());
    $this->entity->save();
  }

  /**
   * Creates sections for new round.
   *
   * @param array $questions
   *   List on question sections from base test.
   * @param string $language
   *   Selected langcode.
   *
   * @return array
   *   Question sections.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createQuestionSections(array $questions, string $language) {
    $questions = Paragraph::loadMultiple($questions);
    $paragraphs = [];
    foreach ($questions as $section) {
      if ($section->hasTranslation($language)){
        $section = $section->getTranslation($language);
      }

      $paragraph = Paragraph::create([
        'type' => 'test_round_questions',
        'field_title' => $section->get('field_title')->getValue(),
        'field_randomise_questions' => $section->get('field_randomise_questions')->getValue(),
        'field_section_questions' => $this->createQuestions($section->get('field_questions')->getValue()),
        'langcode' => $language
      ]);
      $paragraph->save();

      $paragraphs[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
    }

    return $paragraphs;
  }

  /**
   * Clones questions and attach them to paragraphs.
   *
   * @param array $section_questions
   *   Questions from section.
   *
   * @return array
   *   Section question.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createQuestions(array $section_questions) : array {
    $ids = [];
    foreach ($section_questions as $question) {
      $test_question = Paragraph::load($question['target_id']);
      // Make sure that in case something weird has happened to the base test
      // we are still able to create the test round.
      // @todo: we shouldn't allow creating a test round of a test where
      // there are no questions, it should error out to the user.
      if ($test_question instanceof ParagraphInterface
          && !empty($test_question->get('field_test_questions')->getValue())
      ) {
        $target_question = TestQuestion::load($test_question->get('field_test_questions')->getValue()[0]['target_id']);
        if ($target_question instanceof TestQuestionInterface) {
          $cloneQuestion = $target_question->createDuplicate();
          $cloneQuestion->setIsClone();
          $cloneQuestion->save();

          $paragraph = Paragraph::create([
            'type' => 'test_question',
            'field_question' => ['target_id' => $cloneQuestion->id()],
            'field_required' => $test_question->get('field_required')
              ->getValue(),
          ]);
          $paragraph->save();

          $ids[] = [
            'target_id' => $paragraph->id(),
            'target_revision_id' => $paragraph->getRevisionId()
          ];
        }
      }
    }

    return $ids;
  }

  protected function addTranslationToSections($questions, $language) {
    $current_sections = $this->entity->get('field_test_round_questions')->getValue();

    $questions = Paragraph::loadMultiple($questions);
    $i = 0;
    foreach ($questions as $key => $section) {
      if ($section->hasTranslation($language)){
        $section = $section->getTranslation($language);
      }

      $currentSection = Paragraph::load($current_sections[$i]['target_id']);
      $fields['field_title'] = $section->get('field_title')->getValue();
      $fields['field_randomise_questions'] = $section->get('field_randomise_questions')->getValue();
      $this->addTranslationToQuestions(
        $section->get('field_questions')->getValue(),
        $currentSection->get('field_section_questions')->getValue(),
        $language
      );
      $fields['field_section_questions'] = $currentSection->get('field_section_questions')->getValue();
      $currentSection->addTranslation($language, $fields);
      $currentSection->save();
      $i++;
    }
  }

  protected function addTranslationToQuestions(array $baseTestSectionQuestions, array $currentSectionQuestions, string $language) {
    $i = 0;
    foreach ($baseTestSectionQuestions as $question) {
      $test_question = $this->getEntityByLanguage(Paragraph::load($question['target_id']), $language);
      $currentQuestion = Paragraph::load($currentSectionQuestions[$i]['target_id']);
      $target_question = $this->getEntityByLanguage(TestQuestion::load($test_question->get('field_test_questions')->getValue()[0]['target_id']), $language);
      if ($target_question instanceof TestQuestionInterface) {
        $cloneQuestion = $target_question->createDuplicate();
        $cloneQuestion->setIsClone();
        $cloneQuestion->save();

        $fields['field_question'] = ['target_id' => $cloneQuestion->id()];
        $fields['field_required'] = $test_question->get('field_required');
        $currentQuestion->addTranslation($language, $fields);
        $currentQuestion->save();
      }
      $i++;
    }
  }

}
