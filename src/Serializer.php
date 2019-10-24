<?php

namespace PHP_XML;

use ReflectionClass;
use XMLWriter;

class Serializer {
    /**
     * @param object $object
     * @param string $rootKey
     * @return string
     * @throws \ReflectionException
     */
    public function write(object $object, ?string $rootKey) {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument();
        if ($rootKey !== null) {
            $writer->startElement($rootKey);
        }
        $this->process($object, $writer);
        if ($rootKey !== null) {
            $writer->endElement();
        }
        $writer->endDocument();
        return $writer->outputMemory();
    }

    /**
     * @param object $object
     * @param XMLWriter $writer
     * @throws \ReflectionException
     */
    private function process(object $object, XMLWriter $writer) {
        $reflectionClass = new ReflectionClass($object);
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $docComment = $property->getDocComment();
            $annotations = Helpers::getAnnotations($docComment);
            if (isset($annotations['serializedName']) && isset($annotations['var'])) {
                $value = $property->getValue($object);
                if (is_null($value)) {
                    continue;
                }

                $writer->startElement($annotations['serializedName'][0]);
                $type = Helpers::getTypeFromAnnotation($annotations['var'][0]);
                $isCharacterData = isset($annotations['cdata']);
                if ($type == 'DateTime') {
                    $this->writeElement($value->format('Y-m-d\TH:i:s'), $writer, $isCharacterData);
                } elseif (Helpers::isTypeScalar($type)) {
                    $this->writeElement($value, $writer, $isCharacterData);
                } elseif (isset($annotations['serializedList'])) {
                    $type = str_replace('[]', '', $type);

                    foreach ($property->getValue($object) as $item) {
                        $writer->startElement($annotations['serializedList'][0]);
                        if (Helpers::isTypeScalar($type)) {
                            $this->writeElement($item, $writer, $isCharacterData);
                        } else {
                            $this->process($item, $writer);
                        }
                        $writer->endElement();
                    }
                } elseif (!is_null($value)) {
                    $this->process($value, $writer);
                }

                $writer->endElement();
            }
        }
    }

    private function writeElement($value, XMLWriter $writer, bool $isCharacterData) {
        if ($isCharacterData) {
            $writer->startCdata();
        }
        $writer->text($value);
        if ($isCharacterData) {
            $writer->endCdata();
        }
    }
}
