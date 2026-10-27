<?php

namespace Jarryd\RouteNotes\Tests\Stubs;

/**
 * Handles legacy ERP exports.
 *
 * @note Owned by the integrations team
 */
class ClassNotesController
{
    /**
     * @note Nightly export only
     */
    public function export() {}

    public function undocumented() {}

    /** @note Invoked by the scheduler */
    public function __invoke() {}
}
