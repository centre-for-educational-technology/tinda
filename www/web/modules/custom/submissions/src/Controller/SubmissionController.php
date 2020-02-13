<?php

namespace Drupal\submissions\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\submissions\Entity\SubmissionInterface;

/**
 * Class SubmissionController.
 *
 *  Returns responses for Submission routes.
 */
class SubmissionController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Submission  revision.
   *
   * @param int $submission_revision
   *   The Submission  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($submission_revision) {
    $submission = $this->entityTypeManager()->getStorage('submission')->loadRevision($submission_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('submission');

    return $view_builder->view($submission);
  }

  /**
   * Page title callback for a Submission  revision.
   *
   * @param int $submission_revision
   *   The Submission  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($submission_revision) {
    $submission = $this->entityTypeManager()->getStorage('submission')->loadRevision($submission_revision);
    return $this->t('Revision of %title from %date', ['%title' => $submission->label(), '%date' => \Drupal::service('date.formatter')->format($submission->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Submission .
   *
   * @param \Drupal\submissions\Entity\SubmissionInterface $submission
   *   A Submission  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(SubmissionInterface $submission) {
    $account = $this->currentUser();
    $langcode = $submission->language()->getId();
    $langname = $submission->language()->getName();
    $languages = $submission->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $submission_storage = $this->entityTypeManager()->getStorage('submission');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $submission->label()]) : $this->t('Revisions for %title', ['%title' => $submission->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all submission revisions") || $account->hasPermission('administer submission entities')));
    $delete_permission = (($account->hasPermission("delete all submission revisions") || $account->hasPermission('administer submission entities')));

    $rows = [];

    $vids = $submission_storage->revisionIds($submission);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\submissions\SubmissionInterface $revision */
      $revision = $submission_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $submission->getRevisionId()) {
          $link = Link::createFromRoute($date, new Url('entity.submission.revision', ['submission' => $submission->id(), 'submission_revision' => $vid]));
        }
        else {
          $link = $submission->toLink($date)->toString();
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.submission.translation_revert', ['submission' => $submission->id(), 'submission_revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.submission.revision_revert', ['submission' => $submission->id(), 'submission_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.submission.revision_delete', ['submission' => $submission->id(), 'submission_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['submission_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

  /**
   * Display thank you page to the user.
   *
   * @param \Drupal\submissions\Entity\SubmissionInterface $submission
   *   The user Submission for a given Test Round.
   *
   * @return array
   *   Render array.
   */
  public function submissionFinished(SubmissionInterface $submission) {
    // You can use the $submission object to display user answers or smth else.
    $front_page = Url::fromRoute('<front>')->toString();
    return [
      '#type' => 'markup',
      '#markup' =>
        '<div class="row">'
        . '<div class="col-12 col-lg-6 offset-lg-3 col-xl-4 offset-lg-4 text-center mb-5 mt-5">'
           . '<h2 class="t--font-primary mb-3">' . $this->t('Well done! Submission saved!') . '</h2>'
           . '<p>' . $this->t('Submission thank you page description') . '</p>'
           . '<a href="' . $front_page . '" class="btn btn-focus m-btn--pill pl-5 pr-5 mt-4">' . $this->t('Homepage') .'</a>'
        . '</div>'
      . '</div>',
    ];
  }

  /**
   * Checks if the user can access the finished page for a given submission.
   *
   * Submission entity is available via resolving the upcasting route parameter
   * in AccessArgumentsResolverFactory::getArgumentsResolver().
   *
   * @param \Drupal\submissions\Entity\SubmissionInterface $submission
   *   The submission which the user is requesting access to.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current account.
   *
   * @return mixed
   *   Returns a AccessResult.
   */
  public function checkAccessToFinishedPage(SubmissionInterface $submission, AccountInterface $account) {
    if ($submission->isFiller($account)) {
      return AccessResult::allowedIfHasPermission($account, 'view published submission entities');
    }
    if ($account->hasPermission('Administer Submission entities')) {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

}
