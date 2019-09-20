<?php


namespace Elca\Service\Import;


use Beibob\Blibs\File;
use Elca\Model\Import\Csv\ImportElement;

class CsvProjectElementImporter
{
    const DELIMITER = ',';
    const COLUMN_COUNT = 5;

    /**
     * @param File $file
     * @return ImportElement[]
     */
    public function elementsFromCsvFile(File $file) : array
    {
        // first line is headline
        $header = $file->getCsv(self::DELIMITER);

        $this->guardColumnCount($header);

        $importedElements = [];
        while ($csv = $file->getCsv(self::DELIMITER)) {
            $name               = trim($csv[0]);
            $din276CodeString   = trim($csv[1]);
            $quantityString     = trim($csv[2]);
            $unitString         = trim($csv[3]);

            if (empty($name)) {
                continue;
            }

            $importedElements[] = ImportElement::fromCsv(
                $name,
                $din276CodeString,
                $quantityString,
                $unitString,
                !empty($csv[4]) ? trim($csv[4]) : null
            );
        }

        return $importedElements;
    }

    /**
     * @param $header
     */
    protected function guardColumnCount($header)
    {
        if (count($header) !== self::COLUMN_COUNT) {
            throw new \UnexpectedValueException('Wrong format: column count does not match');
        }
    }
}