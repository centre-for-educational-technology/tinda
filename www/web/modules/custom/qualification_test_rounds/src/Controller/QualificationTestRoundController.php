<?php

namespace Drupal\qualification_test_rounds\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface;

/**
 * Class QualificationTestRoundController.
 *
 *  Returns responses for Qualification Test Round routes.
 */
class QualificationTestRoundController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Qualification Test Round  revision.
   *
   * @param int $revision
   *   The Qualification Test Round  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($revision) {
    $qualification_test_round = $this->entityTypeManager()->getStorage('qualification_test_round')->loadRevision($revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('qualification_test_round');

    return $view_builder->view($qualification_test_round);
  }

  /**
   * Page title callback for a Qualification Test Round  revision.
   *
   * @param int $revision
   *   The Qualification Test Round  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($revision) {
    $qualification_test_round = $this->entityTypeManager()->getStorage('qualification_test_round')->loadRevision($revision);
    return $this->t('Revision of %title from %date', ['%title' => $qualification_test_round->label(), '%date' => \Drupal::service('date.formatter')->format($qualification_test_round->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Qualification Test Round .
   *
   * @param \Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface $qualification_test_round
   *   A Qualification Test Round  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(QualificationTestRoundInterface $qualification_test_round) {
    $account = $this->currentUser();
    $langcode = $qualification_test_round->language()->getId();
    $langname = $qualification_test_round->language()->getName();
    $languages = $qualification_test_round->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $qualification_test_round_storage = $this->entityTypeManager()->getStorage('qualification_test_round');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $qualification_test_round->label()]) : $this->t('Revisions for %title', ['%title' => $qualification_test_round->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all qualification test round revisions") || $account->hasPermission('administer qualification test round entities')));
    $delete_permission = (($account->hasPermission("delete all qualification test round revisions") || $account->hasPermission('administer qualification test round entities')));

    $rows = [];

    $vids = $qualification_test_round_storage->revisionIds($qualification_test_round);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\qualification_test_rounds\QualificationTestRoundInterface $revision */
      $revision = $qualification_test_round_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $qualification_test_round->getRevisionId()) {
          $link = Link::createFromRoute($date, new Url('entity.qualification_test_round.revision', ['qualification_test_round' => $qualification_test_round->id(), 'revision' => $vid]));
        }
        else {
          $link = $qualification_test_round->toLink($date)->toString();
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
              Url::fromRoute('entity.qualification_test_round.translation_revert', ['qualification_test_round' => $qualification_test_round->id(), 'revision' => $vid, 'langcode' => $langcode]) :
              Url::fromRoute('entity.qualification_test_round.revision_revert', ['qualification_test_round' => $qualification_test_round->id(), 'revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.qualification_test_round.revision_delete', ['qualification_test_round' => $qualification_test_round->id(), 'revision' => $vid]),
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

    $build['revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
