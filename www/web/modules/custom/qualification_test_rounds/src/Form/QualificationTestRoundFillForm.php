<?php

namespace Drupal\qualification_test_rounds\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\qualification_test_rounds\Builder\FormElementBuilderInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\qualification_test_rounds\Builder\AssociateBuilder;
use Drupal\qualification_test_rounds\Builder\CheckboxesBuilder;
use Drupal\qualification_test_rounds\Builder\OrderFieldBuilder;
use Drupal\qualification_test_rounds\Builder\RangeBuilder;
use Drupal\qualification_test_rounds\Builder\TextFieldBuilder;
use Drupal\qualification_test_rounds\Builder\UploadBuilder;
use Drupal\qualification_test_rounds\Builder\MatchBuilder;
use Drupal\qualification_test_rounds\Builder\ExtendedTextFieldBuilder;
use Drupal\qualification_test_rounds\EntityTrait;
use Drupal\questions\Entity\TestQuestionInterface;
use Drupal\submissions\Entity\Submission;
use Drupal\submissions\Entity\SubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QualificationTestRoundFillForm.
 *
 * @ingroup qualification_test_rounds
 */
class QualificationTestRoundFillForm extends ContentEntityForm {

  const TYPE_SECTION = 0;
  const TYPE_QUESTION = 1;

  use EntityTrait;

  /**
   * Max count of the questions in test.
   *
   * @var int
   */
  public $maxCount;

  /**
   * Temporary storage factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Temporary storage.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * The Test Round that the user is answering to.
   *
   * @var \Drupal\qualification_test_rounds\Entity\QualificationTestRound
   */
  protected $currentTestRound;

  /**
   * Current form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
    TimeInterface $time = NULL,
    PrivateTempStoreFactory $temp_store_factory
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->tempStoreFactory = $temp_store_factory;

    $this->currentTestRound = \Drupal::routeMatch()->getParameter('qualification_test_round');
    $this->store = $this->tempStoreFactory->get('test_' . $this->currentTestRound->id() . '_' . \Drupal::currentUser()->id());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('tempstore.private')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'test_round_fill_form';
  }

  /**
   * Get the type of the test.
   *
   * @return mixed
   *   Type of the test.
   */
  protected function getFormType() {
    return (int) $this->entity->get('field_show_questions')->getValue()[0]['value'];
  }

  /**
   * Get the max amout of steps the user has to go through to finish the Test.
   *
   * @return int
   *   Max steps count.
   */
  protected function getMaxStep() {
    return $this->maxCount - 1;
  }

  /**
   * Returns the number of questions/sections the Test has..
   *
   * @return int
   *   Max steps count.
   */
  protected function getMaxCount() {
    return $this->maxCount;
  }

  /**
   * Find the current step of the test.
   *
   * @return \Drupal\user\PrivateTempStore|int|mixed
   *   current step of the test, starts from 0.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function getStep() : int {
    $done = $this->store->get('step');

    if (!$done) {
      $done = 0;
      $this->store->set('step', 0);
    }

    return $done;
  }

  /**
   * Sets the max count based on Test Round type.
   */
  protected function setMaxStep() {
    $type = $this->getFormType();
    if ($type === self::TYPE_SECTION) {
      $sections = $this->entity->get('field_test_round_questions')->getValue();
      $this->maxCount = count($sections);
    }
    elseif ($type === self::TYPE_QUESTION) {
      $this->maxCount = $this->entity->countQuestions();
    }
  }

  /**
   * Find Question type for form render array.
   *
   * @param \Drupal\questions\Entity\TestQuestionInterface $testQuestion
   *   Testquestion entity.
   * @param int $id
   *   Id to give to name.
   *
   * @return array|bool
   *   Type of question or false.
   */
  protected function getQuestion(TestQuestionInterface $testQuestion, int $id) {
    $builder = $this->getBuilder($testQuestion);

    if ($builder) {

      $question_answer = NULL;

      // Check the formState first, maybe they have just entered the value,
      // but the validation failed and we didn't store the values.
      if (!empty($user_input = $this->formState->getUserInput())
        && array_key_exists('question', $user_input)
      ) {
        if (array_key_exists($id, $user_input['question'])) {
          $question_answer = $user_input['question'][$id];
        }
      }

      if ($question_answer === NULL) {
        $answers = $this->store->get('answers');
        $question_answer = $answers[$id] ?? NULL;
      }

      /** @var \Drupal\qualification_test_rounds\Builder\FormElementBuilderInterface $builder */
      return $builder->build($testQuestion, $id, $question_answer);
    }

    return FALSE;
  }

  /**
   * Get builder of the element.
   *
   * @param \Drupal\questions\Entity\TestQuestionInterface $testQuestion
   *   Testquestion entity.
   *
   * @return bool|FormElementBuilderInterface
   *   type of question builder or false.
   */
  private function getBuilder(TestQuestionInterface $testQuestion) {
    $question_type = $testQuestion->get('type')->getValue()[0]['target_id'];

    $types = [
      'checkbox' => CheckboxesBuilder::class,
      'slider' => RangeBuilder::class,
      'associate' => AssociateBuilder::class,
      'textentry' => TextFieldBuilder::class,
      'upload' => UploadBuilder::class,
      'order' => OrderFieldBuilder::class,
      'extended_text' => ExtendedTextFieldBuilder::class,
      'match_interaction' => MatchBuilder::class,
    ];

    if (isset($types[$question_type])) {
      /** @var \Drupal\qualification_test_rounds\Builder\FormElementBuilderInterface $builder */
      return new $types[$question_type]();
    }

    return FALSE;
  }

  /**
   * Get Section Questions from store or from entity.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $section
   *   Section where to take questions.
   *
   * @return array
   *   List on question ID's.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function getSectionQuestions(ParagraphInterface $section) : array {
    $randomize = (int) $section->get('field_randomise_questions')->getValue()[0]['value'];

    // Get From store.
    $questions = $this->store->get('sections');
    if ($questions && isset($questions[$section->id()])) {
      return $questions[$section->id()];
    }

    // Get straightly from entity.
    $questions = $section->get('field_section_questions')->getValue();
    if ($randomize) {
      shuffle($questions);
      $this->saveData([$section->id() => $questions], 'sections');
    }

    return $questions;
  }

  /**
   * Checks if the store contains the started answering time.
   *
   * @return string|null
   *   The started time timestamp or NULL.
   */
  protected function getStartedAnsweringTime() {
    return $this->store->get('started_answering') ?? NULL;
  }

  /**
   * Saves the current time as started answering time.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function setStartedAnsweringTime() {
    $this->store->set('started_answering', $this->getCurrentTime());
  }

  /**
   * Wrapper for the timestamp logic.
   *
   * @return string
   *   Formatted datetime.
   *
   * @throws \Exception
   */
  protected function getCurrentTime() {
    // Use php-s DateTime instead of Drupal's,
    // otherwise the timezone is converted twice when displayed in the UI.
    $date_utc = new \DateTime("now", new \DateTimeZone("UTC"));

    return $date_utc->format('Y-m-d\TH:i:s');
  }

  /**
   * Calculates the max possible score for the Test Round.
   *
   * @param array $input
   *   Array of questions.
   *
   * @return int
   *   The max possible score.
   */
  protected function getMaxPossiblePoints(array $input) : int {
    $max_possible_score = 0;
    foreach ($input as $paragraph_id => $answer) {
      $question_from_round = Paragraph::load($paragraph_id);
      $max_possible_score += $question_from_round->get('field_score')->getValue();
    }

    return $max_possible_score;
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $this->formState = $form_state;

    if ($this->getStartedAnsweringTime() === NULL) {
      $this->setStartedAnsweringTime();
    }

    $type = $this->getFormType();

    $this->setMaxStep();

    $this->buildProgressBar($form);

    if ($type === self::TYPE_SECTION) {
      $this->buildSection($form);
    }
    elseif ($type === self::TYPE_QUESTION) {
      $this->buildQuestions($form);
    }

    if (array_key_exists('question', $form)) {
      $this->addOrderNumbersToQuestionTitles($form['question']);
    }

    $this->buildActionButtons($form);

    $form['#attributes']['class'][] = 'm-form c-test-page-form';
    $form['#attached']['library'][] = 'tinda/test_page';
    $form['question']['#tree'] = TRUE;

    return $form;
  }

  /**
   * Build the section.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function buildSection(array &$form) {
    $step = $this->getStep();
    $sections = $this->entity->get('field_test_round_questions')->getValue();

    if (!empty($sections)) {

      $section = $sections[$step];

      $section_entity = $this->getEntityByLanguage(Paragraph::load($section['target_id']));

      $section_key = $section['target_id'] . '_section';
      $section_title = !empty($section_entity->get('field_title')->getValue())
        ? $section_entity->get('field_title')->getValue()[0]['value']
        : t('Empty section');
      $form[$section_key]['title']['#markup'] = '<h4 class="t--font-primary mt-5 mb-4">' . $section_title . '</h4>';

      $questions = $this->getSectionQuestions($section_entity);
      foreach ($questions as $question) {
        $question = $this->getEntityByLanguage(Paragraph::load($question['target_id']));
        $this->addQuestionToForm($form, $question, $section_key);
      }

    }
  }

  /**
   * Build the Question when type is by question.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function buildQuestions(array &$form) {
    $step = $this->getStep();
    $questions = $this->entity->getAllQuestions();

    if (!empty($questions)) {

      $current_question = $questions[$step];

      $this->addQuestionToForm($form, $current_question, 'section_' . $current_question->id());
    }
  }

  /**
   * Add question to form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\paragraphs\ParagraphInterface $question
   *   Question paragraph entity.
   * @param string $section
   *   Section key.
   */
  protected function addQuestionToForm(array &$form, ParagraphInterface $question, string $section) {
    $question_entity = $this->getEntityByLanguage($question->get('field_question')->entity);

    $form_question = $this->getQuestion($question_entity, $question->id());
    if ($form_question) {
      $form['question'][$question->id()] = $form_question;
    }
  }

  /**
   * Builds the progress bar for the test.
   *
   * @param array $form
   *   Forms render array.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function buildProgressBar(array &$form) {

    $step = $this->getStep() + 1;
    $max_count = $this->getMaxCount();
    // When there is only 1 section.
    if ($max_count === 0) {
      $current_progress = 0;
    }
    else {
      $current_progress = round(($step * 100) / $max_count);
    }

    // It wasn't possible to get the finished button to work properly with a
    // custom template. So had to resolve it with a massive render array...
    $form['progress'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['row align-items-center'],
      ],
      'progress_bar' => [
        '#type' => 'markup',
        '#markup' =>
          Markup::create('<div class="col-sm-12 mb-4 mb-md-0 col-md">'
            . '<div class="progress m-progress--lg">'
               . '<div class="progress-bar m--bg-focus" role="progressbar" style="width: ' . $current_progress. '%;" aria-valuenow="' . $current_progress. '" aria-valuemin="0" aria-valuemax="100"></div>'
            . '</div>'
        . '</div>'),
      ],
      'current_step' => [
        '#type' => 'markup',
        '#markup' => '<div class="col-auto mr-auto mr-md-0"><span class="h3">' . $step . '/' . $max_count . '</span></div>',
      ],
      'finished_btn' => [
        '#type' => 'submit',
        '#name' => 'op',
        '#id' => 'finished',
        '#value' => $this->t('Finish'),
        '#save_type' => 'save',
        '#disabled' => $step !== $max_count,
        '#submit' => ['::submitForm'],
        '#prefix' => '<div class="col-auto">',
        '#suffix' => '</div>',
        '#attributes' => [
          'class' => ['btn btn-focus m-btn--pill pl-5 pr-5'],
        ],
      ],
    ];

  }

  /**
   * Builds submit and/or back buttons for the form.
   *
   * @param array $form
   *   Form render aray.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function buildActionButtons(array &$form) {

    $step = $this->getStep();
    $maxStep = $this->getMaxStep();
    $allowMove = (int) $this->entity->get('field_move_between_questions')->getValue()[0]['value'];

    $form['actions']['#type'] = 'actions';
    $form['actions']['#attributes']['class'][] = 'form-actions row t-form-actions';

    if ($allowMove && $step !== 0) {
      $form['actions']['submit_back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Previous'),
        '#submit' => ['::previousStep'],
        '#save_type' => 'previous',
        '#prefix' => '<div class="col-auto mr-auto mb-2 mb-md-0">',
        '#suffix' => '</div>',
        '#attributes' => [
          'class' => ['btn btn-outline-focus m-btn--pill pl-5 pr-5 mr-4'],
        ],
        '#limit_validation_errors' => [],
      ];
    }

    if ($step !== $maxStep) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#save_type' => 'next',
        '#prefix' => '<div class="col-auto">',
        '#suffix' => '</div>',
        '#attributes' => [
          'class' => ['btn btn-focus m-btn--pill pl-5 pr-5'],
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if ($trigger['#save_type'] === 'previous') {
      $form_state->clearErrors();
      return;
    }

    $input = $form_state->getUserInput();

    if (is_array($input) && array_key_exists('question', $input)) {
      foreach ($input['question'] as $id => $item) {
        $question_from_round = Paragraph::load($id);
        $testQuestion = $question_from_round->get('field_question')->entity;
        $builder = $this->getBuilder($testQuestion);

        $form_state = $builder::validate(
          $form_state,
          $item,
          $testQuestion,
          $form['question'][$id]
        );
      }
    }

  }

  /**
   * Move form one step back.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function previousStep(array &$form, FormStateInterface $form_state) {
    $this->handleSubmission($form, $form_state);

    $this->store->set('step', $this->getStep() - 1);
  }

  /**
   * Form submission Next/save handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->handleSubmission($form, $form_state);

    if ($this->getMaxStep() == $this->getStep()) {
      $submission = $this->createSubmission();
      $this->clearAnswersFromStore();
      $this->redirectToFinishedPage($submission, $form_state);
    }
    else {
      $this->store->set('step', $this->getStep() + 1);
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function handleSubmission(array &$form, FormStateInterface $form_state) {
    $answers = $form_state->getValue('question');
    if (!empty($answers)) {
      foreach ($answers as $key => $answer) {
        if (is_array($answer) && array_key_exists('uploaded_files', $answer)) {
          $answers[$key]['uploaded_files'] = $this->handleUploadSubmission($answer, $key, $form, $form_state);
        }
      }
      $this->saveData($answers, 'answers');
    }
  }

  /**
   * Handles Dropzone file upload cases.
   *
   * @param array $answer
   *   User's answer from the store.
   * @param int $element_id
   *   The Dropzone element id.
   * @param array $form
   *   The whole form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array|bool
   *   Array containing uploaded file uris or FALSE if no uploaded files.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function handleUploadSubmission(array $answer, int $element_id, array &$form, FormStateInterface $form_state) {
    $files = [];
    // First if we already have the answer, but it's still not uploaded.
    if (!empty($answer['uploaded_files'])) {
      // We reach here after the client first uploaded the file
      // & now is submitting the form.
      foreach ($answer['uploaded_files'] as $file) {
        $uploaded_file = $this->uploadFile($file);
        if ($uploaded_file) {
          $files[] = $uploaded_file->getFileUri();
        }
      }
    }
    // We don't have an answer, we need to add it.
    if (!empty($form['question'][$element_id]['#default_value'])) {
      foreach ($form['question'][$element_id]['#default_value'] as $file) {
        $file_found = FALSE;
        // We have the value from form build and it's somehow uploaded already.
        if (!is_array($file) || $file instanceof FileInterface) {
          if (!($file instanceof FileInterface)) {
            $uploaded_file = File::load($file);
          }
          $files[] = $uploaded_file->getFileUri();
          $file_found = TRUE;
        }
        // The file is uploaded and we have it's name.
        elseif (is_array($file) && array_key_exists('name', $file)) {
          $uploaded_file = \Drupal::entityTypeManager()
            ->getStorage('file')
            ->loadByProperties([
              'filename' => $file,
              'uid' => \Drupal::currentUser()->id(),
            ]);

          if (!empty($uploaded_file)) {
            $uploaded_file = reset($uploaded_file);
            $files[] = $uploaded_file->getFileUri();
            $file_found = TRUE;
          }
        }
        if (!$file_found) {
          // We have the value from form build, but it's not yet uploaded.
          $tmp_file_storage = file_directory_temp();
          $file_name = $file['name'];
          $file_with_path = $tmp_file_storage . '/' . $file['name'];
          // Dropzone appends .txt to dangerous extensions.
          if (!file_exists($file_with_path)) {
            $file_info = pathinfo($file_with_path);
            $file_with_path = $tmp_file_storage . '/' . $file_info['filename'];
            $file_name = $file_info['filename'];
          }
          if (file_exists($file_with_path)) {
            $data = [
              'filename' => $file_name,
              'path' => 'temporary://' . $file_name,
            ];
            $uploaded_file = $this->uploadFile($data);
            if ($uploaded_file) {
              $files[] = $uploaded_file->getFileUri();
            }

          }
        }
      }
    }
    return !empty($files) ? $files : FALSE;
  }

  /**
   * Adds order number to the question title.
   *
   * @param array $questions
   *   Array of question render arrays.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function addOrderNumbersToQuestionTitles(array &$questions) {
    $current_step = $this->getStep() + 1;
    $index = 1;
    foreach ($questions as $key => &$question) {
      // Make sure we only add the order numbers to question,
      // not some other render array stuff.
      if (is_numeric($key)) {

        if ($question['#type'] === 'container') {
          $question['title']['#markup'] = $current_step . '.' . $index . ' ' . $question['title']['#markup'];
        }
        else {
          $question['#title'] = $current_step . '.' . $index . ' ' . $question['#title'];
        }

        $index++;

      }
    }
  }

  /**
   * Create a new submission.
   *
   * @return \Drupal\submissions\Entity\SubmissionInterface
   *   New submission entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createSubmission() : SubmissionInterface {
    $input = $this->store->get('answers');

    $user = \Drupal::currentUser();
    $submission = Submission::create([
      'name' => $this->entity->getName(),
      'langcode' => $this->entity->language(),
      'field_started_answering' => $this->getStartedAnsweringTime(),
      'field_finished_answering' => $this->getCurrentTime(),
      'field_filler' => ['target_id' => $user->id()],
      'field_test_round' => ['target_id' => $this->entity->id(), 'target_revision_id' => $this->entity->getRevisionId()],
      'field_submission_answers' => is_array($input) ? $this->createAnswers($input) : [],
      'field_max_possible_score' => $this->entity->getMaxPossibleScore(),
    ]);
    $submission->save();

    return $submission;
  }

  /**
   * Loop through answers and get saved answers.
   *
   * @param array $input
   *   User answers list.
   *
   * @return array
   *   List of saved answers.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createAnswers(array $input) : array {
    $answers = [];
    foreach ($input as $paragraph_id => $answer) {
      $answers[] = $this->createQuestionAnswer($paragraph_id, $answer);
    }

    return $answers;
  }

  /**
   * Upload the temporary file.
   *
   * @param array $uploaded_file
   *   Uploaded file info.
   *
   * @return \Drupal\file\FileInterface|null
   *   The file instance or NULL.
   */
  protected function uploadFile(array $uploaded_file) : ?FileInterface {
    $final_file_path = 'public://testfiles/' . $uploaded_file['filename'];

    // Save the file as a permanent file.
    if ($file_data = file_get_contents($uploaded_file['path'])) {
      unlink($uploaded_file['path']);
      if ($final_file = file_save_data($file_data, $final_file_path, FILE_EXISTS_RENAME)) {
        // Delete the temporary file.
        return $final_file;
      }
    }
    return NULL;
  }

  /**
   * Create an answer for the question in round test.
   *
   * @param int $paragraph_id
   *   Question id from test round.
   * @param mixed $answer
   *   User inserted answer.
   *
   * @return array
   *   Id of saved answer for submission.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createQuestionAnswer(int $paragraph_id, $answer) : array {
    $question_from_round = $this->getEntityByLanguage(Paragraph::load($paragraph_id));
    $possible_score = $question_from_round->get('field_score')->getValue();

    /** @var \Drupal\questions\Entity\TestQuestionInterface $testQuestion */
    $testQuestion = $question_from_round->get('field_question')->entity;
    $builder = $this->getBuilder($testQuestion);

    $answer = is_array($answer) ? $answer : [$answer];
    $question_answer = Paragraph::create([
      'type' => 'submission_answers',
      'field_score' => reset($possible_score),
      'field_question' => $testQuestion->id(),
      'field_filler_answers' => $builder::formatAnswer($answer),
    ]);
    $question_answer->save();

    return ['target_id' => $question_answer->id(), 'target_revision_id' => $question_answer->getRevisionId()];
  }

  /**
   * Store data to temporary storage.
   *
   * @param array $data_to_save
   *   List data to save.
   * @param string $key
   *   Which data to edit.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function saveData(array $data_to_save, string $key) {
    $current_data = $this->store->get($key);
    $current_data = $current_data ?? [];

    $store = array_replace_recursive($current_data, $data_to_save);

    $this->store->set($key, $store);
  }

  /**
   * Delete values from store.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function clearAnswersFromStore() {
    $this->store->delete('step');
    $this->store->delete('answers');
    $this->store->delete('sections');
    $this->store->delete('started_answering');
  }

  /**
   * Redirect user to the submission finished page.
   *
   * @param \Drupal\submissions\Entity\SubmissionInterface $submission
   *   Client's Submission.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  protected function redirectToFinishedPage(SubmissionInterface $submission, FormStateInterface $form_state) {
    $url = Url::fromRoute('submissions.submission_finished', ['submission' => $submission->id()]);
    $form_state->setRedirectUrl($url);
  }

}
