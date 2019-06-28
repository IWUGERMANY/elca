<?php
namespace ImportAssistant\Model;

use ImportAssistant\Model\Import\Element;
use Ramsey\Uuid\Uuid;

class SchemaImporterV2
{
    protected function importElementNode(\DOMElement $elementNode) {
        $uuid     = $this->getAttribute($elementNode, 'uuid', (string)Uuid::uuid4(), true);
        $dinCode  = $this->getAttribute($elementNode, 'din276Code');
        $quantity = $this->getAttribute($elementNode, 'quantity', 1);
        $refUnit  = $this->getAttribute($elementNode, 'refUnit');

        $elementInfoNode = $this->getNode('x:elementInfo', $elementNode);
        $dto             = $this->getObjectProperties(
            $elementInfoNode,
            [
                'name',
                'description',
            ]
        );

        $element = new Element(
            $uuid,
            $dinCode,
            $dto->name,
            $this->roundOrDefault($quantity, 8, 1),
            $refUnit,
            $dto->description
        );

        $componentNodes = $this->getList('x:layerComponents/x:component', $elementNode, true);
        foreach ($componentNodes as $componentNode) {
            $this->importComponent($componentNode, $element);
        }

        $componentNodes = $this->getList('x:layerComponents/x:siblings/x:component', $elementNode, true);
        if ($componentNodes && $componentNodes->length % 2 == 0) {
            for ($index = 0; $index < $componentNodes->length; $index += 2) {

                $this->importLayerSiblings($componentNodes->item($index), $componentNodes->item($index + 1), $element);
            }
        }

        $componentNodes = $this->getList('x:miscComponents/x:component', $elementNode, true);
        foreach ($componentNodes as $componentNode) {
            $this->importComponent($componentNode, $element);
        }

        $attributes = $this->getAttributes($elementNode);

        foreach ($attributes as $attribute) {
            $element->addAttribute(
                $attribute
            );
        }

        return $element;
    }
}