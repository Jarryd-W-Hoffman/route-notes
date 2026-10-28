<?php

namespace Jarryd\RouteNotes\Tests\Stubs;

use Jarryd\RouteNotes\Note;
use Jarryd\RouteNotes\NoteLevel;

/**
 * @note Class docblock
 */
#[Note('Class attribute')]
class AttributeController
{
    #[Note('Writes to ledger')]
    #[Note('Remove after migration', level: NoteLevel::Deprecated)]
    public function store() {}

    /**
     * @note Method docblock
     */
    #[Note('Method attribute', 'warning')]
    public function update() {}

    #[Note('Invokable attribute')]
    public function __invoke() {}
}
