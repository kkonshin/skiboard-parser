<?php
namespace Parser\XlsParser;

class ReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    public function readCell($column, $row, $worksheetName = '')
    {
        // TODO: Implement readCell() method.
        if ($row == 1 || ($row >= 20 && $row <= 30)) {
            return true;
        }
        return false;
    }
}