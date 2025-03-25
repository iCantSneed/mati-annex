<?php

namespace Mati\Rumble;

final class RumbleException extends \Exception
{
  /**
   * @param array<string,mixed> $context
   */
  public function __construct(
    string $message,
    public readonly array $context = [],
    public readonly bool $isWarning = false,
  ) {
    parent::__construct($message);
  }
}
