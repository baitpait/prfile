<?php

namespace App\Models\Concerns;

/**
 * Business Purpose: Consistent payment label on client/supplier account statements.
 */
trait HasStatementPaymentReference
{
    public function statementReference(): string
    {
        $bankRef = trim((string) ($this->bank_reference ?? ''));
        if ($bankRef !== '') {
            return $bankRef;
        }

        $notes = trim((string) ($this->notes ?? ''));
        if ($notes !== '') {
            return $notes;
        }

        return '#'.$this->id;
    }

    public function statementSubNotes(): ?string
    {
        $bankRef = trim((string) ($this->bank_reference ?? ''));
        $notes = trim((string) ($this->notes ?? ''));

        if ($bankRef !== '' && $notes !== '' && $notes !== $bankRef) {
            return $notes;
        }

        return null;
    }
}
