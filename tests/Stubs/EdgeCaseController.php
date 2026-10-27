<?php

namespace Jarryd\RouteNotes\Tests\Stubs;

class EdgeCaseController
{
    /** @note Single line */
    public function singleLine() {}

    /**
     * Summary line mentioning @note in prose should be ignored.
     *
     * @param  string  $id
     *
     * @note After other tags
     *
     * @return void
     */
    public function mixedTags() {}

    /**
     * @noteworthy Not a note
     *
     * @note
     * @note    Padded note
     */
    public function lookalikes() {}

    /**
     * @note Contains an @ symbol and a * star
     */
    public function specialCharacters() {}

    /** @note */
    public function emptySingleLine() {}

    public function undocumented() {}

    // @note Not a docblock
    public function lineComment() {}
}
