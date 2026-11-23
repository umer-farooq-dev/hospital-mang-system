<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Rows already laid out (title rows, headings, data) written as one sheet. */
class TableExport implements FromArray, ShouldAutoSize, WithTitle
{
    public function __construct(private array $rows, private string $title = 'Export')
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $this->title), 0, 31) ?: 'Export';
    }
}
