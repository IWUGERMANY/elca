<?php


namespace Elca\Model\Import\Csv;


use Beibob\Blibs\File;
use Elca\Validator\ElcaValidator;

class Validator extends ElcaValidator
{
    public function assertImportFile($property)
    {
		if (!$this->assertTrue($property, File::uploadFileExists($property), t('Bitte geben Sie eine Importdatei an!'))) {
            return false;
        }

        if (!$this->assertTrue(
            $property,
                preg_match('/\.csv$/iu', (string)$_FILES[$property]['name']),
                t('Bitte nur CSV und XLS/XLS Dateien importieren!')
        )) {
            return false;
        }

        $this->assertTrue(
            $property,
            ((int)$_FILES[$property]['size'] > 0),
            t('Die hochgeladene CSV Datei ist leer!')
        );

        if (!$this->isValid()) {
            return false;
        }

        $csvString = file_get_contents($_FILES[$property]['tmp_name']);

        if (function_exists('mb_check_encoding')) {
            $this->assertTrue(
                $property,
                mb_check_encoding($csvString, 'UTF-8'),
                t('Bitte den UTF-8 Zeichensatz in der CSV Datei verwenden!')
            );
        }

        return $this->isValid();
    }

    public function assertValidProject(Project $project)
    {
        foreach ($project->importElements() as $index => $importElement) {
            $key = $importElement->uuid();
            $this->assertTrue('quantity['.$key.']', null !== $importElement->quantity(), t('Legen Sie bitte eine Menge für das  :index:. Bauteil fest', null, [':index:' => $index + 1]));
            $this->assertTrue('dinCode['.$key.']', (bool)$importElement->dinCode(), t('Wählen Sie bitte die DIN 276 für das  :index:. Bauteil', null, [':index:' => $index + 1]));
            $this->assertTrue('tplElementId['.$key.']', null !== $importElement->tplElementUuid(), t('Wählen Sie bitte eine Bauteilvorlage für das  :index:. Bauteil', null, [':index:' => $index + 1]));

           if (!$this->isValid()) {
               return false;
           }
        }

        return true;
    }
}