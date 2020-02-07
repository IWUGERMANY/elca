<?php
namespace Elca\Service\Import;

use Beibob\Blibs\File;
use Elca\Db\ElcaElement;
use Elca\Model\Import\Csv\ImportElement;
use Ramsey\Uuid\Uuid;

use PhpOffice\PhpSpreadsheet\IOFactory;
// use PhpOffice\PhpSpreadsheet\Reader\Exception;
// use PhpOffice\PhpSpreadsheet\Reader\Xls;
// use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

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
     * @param XLS File $file
     * @return ImportElement[]
     */
    public function elementsFromXls2CsvFile(File $file) : array
    {
		$inputFileType = 'Xls';
        
		if( preg_match('/^xlsx$/i', $file->getExtension()) ) {
			$inputFileType = 'Xlsx';
		} 
		
		$importedElements = [];
		
		try {
		    // $spreadsheet = IOFactory::load($file->getFilepath());
			
			$reader = IOFactory::createReader($inputFileType);
			$spreadsheet = $reader->load($file->getFilepath());

			$sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
			// $sheetData Array (numbered from 1 to x)	
			// first line is headline
			$header = $sheetData[1];
			$this->guardColumnCount($header);
			unset($sheetData[1]);
			
			foreach($sheetData as $sheetDataRow => $sheetDataRowArray ) 
			{
				$name = trim($sheetDataRowArray['A'] ?? '');

				if (empty($name)) {
					continue;
				}

				$din276CodeString   = trim($sheetDataRowArray['B'] ?? '');
				$quantityString     = trim($sheetDataRowArray['C'] ?? '');
				$unitString         = trim($sheetDataRowArray['D'] ?? '');

				$tplElementUuidOrId = !empty($sheetDataRowArray['E']) ? trim($sheetDataRowArray['E']) : null;
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
			
		} catch(InvalidArgumentException $e) {
			throw new \UnexpectedValueException('Error loading file: '.$e->getMessage());
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