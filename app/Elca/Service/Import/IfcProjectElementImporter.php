<?php
namespace Elca\Service\Import;

use Beibob\Blibs\File;
use Elca\Db\ElcaElement;
use Elca\Model\Common\Unit;
use Elca\Model\Import\Ifc\ImportElement;
use Ramsey\Uuid\Uuid;


class IfcProjectElementImporter
{
    const DELIMITER = ';';
    const COLUMN_COUNT = 11;

    /**
     * @param File $file
     * @return ImportElement[]
     */
    public function elementsFromIfcFile(File $file) : array
    {
		// first line is headline
        $header = $file->getCsv(self::DELIMITER);

        $this->guardColumnCount($header);

        $importedElements = [];
		
        while ($csv = $file->getCsv(self::DELIMITER)) 
		{

            $name               = trim($csv[0] ?? '');

            if (empty($name)) {
                continue;
            }

            $din276CodeString   = trim($csv[1] ?? '');
            $din276CodeName     = trim($csv[10] ?? ''); // KG.name from external py-script KG.py, not in use, yet [2020-10-20]
            
            $ifcTypeString      = trim($csv[4] ?? '');
            $ifcFloorString     = trim($csv[5] ?? '');
            $ifcMaterialString  = trim($csv[6] ?? '');
            $ifcGUIDString     	= trim($csv[7] ?? '');
           

            $ifcPredefinedTypeString = trim($csv[8] ?? '');

            // default in python-script = only SQUARE_METRE (2020-07-08)
            $ifcUnits = ['METRE' => Unit::METER, 'SQUARE_METRE' => Unit::SQUARE_METER, 'CUBIC_METRE' => Unit::CUBIC_METER];
            $ifcUnitString = trim($csv[9] ?? '');
            
            $unitString = array_key_exists($ifcUnitString, $ifcUnits) ? $ifcUnits[$ifcUnitString] : '';
            
			$quantityString = trim($csv[2] ?? '');
			if(!empty($quantityString) && $unitString != Unit::SQUARE_METER)
			{
				$unitString = Unit::SQUARE_METER;
			}
			
			$massString 	= trim($csv[3] ?? '');
			if(!empty($massString) && $massString!='0.0' && $unitString != Unit::CUBIC_METER)
			{
				$quantityString = $massString;
				$unitString = Unit::CUBIC_METER;
			}	
			
            // check / set Unit::PIECE
            if($unitString != Unit::SQUARE_METER && $unitString != Unit::CUBIC_METER && $unitString != Unit::METER)
            {
                $unitString = Unit::PIECE;
            }    
			
			// Trick - no Bauteil-ID in ifc-file
			$tplElementUuidOrId = null;
            $tplElement = $this->findTplElement($tplElementUuidOrId);

			$importElement = ImportElement::fromCsv(
                $name, 
                $din276CodeString,
                $quantityString,
                $unitString ?: Unit::PIECE,
                $ifcTypeString,
				$ifcFloorString,
				$ifcMaterialString,
				$ifcGUIDString,
				$ifcPredefinedTypeString,
				$tplElement
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