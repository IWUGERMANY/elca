<?php
namespace ImportAssistant\Model;

use ImportAssistant\Model\Import\Project;

interface SchemaImporter
{
    const NAMESPACE = 'https://www.bauteileditor.de/EnEV/2017';

    public function assertVersion(): bool;

    public function isValid(string $xsdBasePath): bool;

    public function importProjectNode(array $materialMappingInfos, $processDbId): Project;

    public function schema(): string;

    public function schemaVersion(): int;
}