<?php

namespace Jarryd\RouteNotes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Note
{
    public readonly NoteLevel $level;

    public function __construct(
        public readonly string $text,
        NoteLevel|string $level = NoteLevel::Info,
    ) {
        $this->level = NoteLevel::fromName($level);
    }

    /**
     * Restore a note from the plain array stored in the route action.
     *
     * @param  string|array{text: string, level?: string}  $note
     */
    public static function fromArray(string|array $note): self
    {
        if (is_string($note)) {
            return new self($note);
        }

        return new self($note['text'], $note['level'] ?? NoteLevel::Info);
    }

    /**
     * Notes are stored as plain arrays so they can be exported by route:cache.
     *
     * @return array{text: string, level: string}
     */
    public function toArray(): array
    {
        return ['text' => $this->text, 'level' => $this->level->value];
    }
}
