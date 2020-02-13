<?php

namespace Drupal\anonymous_status_messages\Plugin\Block;

use Drupal\Core\Block\MessagesBlockPluginInterface;
use Drupal\system\Plugin\Block\SystemMessagesBlock;

/**
 * Provides a clone of Messages block.
 *
 * @see \Drupal\Core\Messenger\MessengerInterface
 *
 * @Block(
 *   id = "anon_system_messages_block",
 *   admin_label = @Translation("Anon Status Messages")
 * )
 */
class AnonymousSystemMessagesBlock extends SystemMessagesBlock implements MessagesBlockPluginInterface {

}
