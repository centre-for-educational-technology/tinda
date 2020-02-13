<?php

namespace Drupal\submissions\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a form for deleting Submission entities.
 *
 * @ingroup submissions
 */
class SubmissionDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   *
   * We need to override this, because we are using views for list builder.
   */
  public function getRedirectUrl() {
    return Url::fromRoute('view.submissions.submission_collection');
  }

  /**
   * {@inheritdoc}
   *
   * We need to override this, because we are using views for list builder.
   */
  public function getCancelUrl() {
    return $this->getRedirectUrl();
  }

}
