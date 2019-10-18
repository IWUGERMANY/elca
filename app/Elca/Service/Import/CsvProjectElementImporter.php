<?php


namespace Elca\Service\Import;


use Beibob\Blibs\File;
use Elca\Db\ElcaElement;
use Elca\Model\Import\Csv\ImportElement;
use Ramsey\Uuid\Uuid;

class CsvProjectElementImporter
{
    const DELIMITER = ';';
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
            $name               = trim($csv[0] ?? '');

            if (empty($name)) {
                continue;
            }

            $din276CodeString   = trim($csv[1] ?? '');
            $quantityString     = trim($csv[2] ?? '');
            $unitString         = trim($csv[3] ?? '');

            $tplElementUuidOrId = !empty($csv[4]) ? trim($csv[4]) : null;
            $tplElement = $this->findTplElement($tplElementUuidOrId);

            $importElement = ImportElement::fromCsv(
                $name,
                $din276CodeString,
                $quantityString,
                $unitString,
                null !== $tplElement ? $tplElement->getUuid() : null
            );

            $importedElements[] = $importElement->harmonizeWithTemplateElement($tplElement);
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

    private function findTplElement($tplElementUuidOrId) : ?ElcaElement
    {
        if (null === $tplElementUuidOrId) {
            return null;
        }

        if (Uuid::isValid($tplElementUuidOrId)) {
            return ElcaElement::findByUuid($tplElementUuidOrId);
        }

        if (!\is_numeric($tplElementUuidOrId)) {
            return null;
        }

        return ElcaElement::findById($tplElementUuidOrId);
    }
}