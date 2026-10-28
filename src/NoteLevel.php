<?php

namespace Jarryd\RouteNotes;

use InvalidArgumentException;

enum NoteLevel: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Deprecated = 'deprecated';
    case Danger = 'danger';

    /**
     * Resolve a level from its name, failing loudly on typos.
     */
    public static function fromName(self|string $level): self
    {
        if ($level instanceof self) {
            return $level;
        }

        return self::tryFrom(strtolower($level)) ?? throw new InvalidArgumentException(sprintf(
            'Invalid note level [%s]. Expected one of: %s.',
            $level,
            implode(', ', array_column(self::cases(), 'value')),
        ));
    }

    /**
     * The console color used to display notes of this level.
     */
    public function color(): string
    {
        return match ($this) {
            self::Info => 'cyan',
            self::Warning => 'yellow',
            self::Deprecated => 'magenta',
            self::Danger => 'red',
        };
    }
}
