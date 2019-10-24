<?php

namespace PHP_XML;

use DateTime;
use ReflectionClass;
use ReflectionException;
use XMLReader;

class Deserializer {
    /**
     * @var XMLReader
     */
    private $reader;

    /**
     * @param string $xml
     * @param $class
     * @return mixed
     * @throws ReflectionException
     */
    public function parse(string $xml, $class) {
        $this->reader = new XMLReader();
        $this->reader->xml($xml);
        return $this->process($this->reader, $class);
    }

    /**
     * @param XMLReader $reader
     * @param string $root Class or primitive type to process
     * @param array $tags
     * @param mixed $array
     * @return mixed
     * @throws ReflectionException
     */
    private function process(XMLReader $reader, $root, $tags = [], &$array = null) {
        if (Helpers::isTypeScalar($root)) {
            $propertyMap = [];
        } else {
            $reflectionClass = new ReflectionClass($root);
            $properties = $reflectionClass->getProperties();
            $propertyMap = $this->createPropertyMap($properties);
            $object = $reflectionClass->newInstanceWithoutConstructor();
        }

        $targetTagDepth = max(0, count($tags) - 1);

        while ($reader->read()) {
            $xmlProperty = $propertyMap[$reader->name] ?? null;

            if ($reader->nodeType == XMLReader::ELEMENT && !$reader->isEmptyElement) {
                $tags[] = $reader->name;

                if (!is_null($xmlProperty)) {
                    if (isset($xmlProperty->annotations['var'])) {
                        $type = Helpers::getTypeFromAnnotation($xmlProperty->annotations['var'][0]);

                        if ($type == 'DateTime') {
                            $value = DateTime::createFromFormat('Y-m-d\TH:i:s', strtok($reader->readString(), '.'));
                            if (!$value) {
                                $value = DateTime::createFromFormat('Y-m-d\TH:i:sP', strtok($reader->readString(), '.'));
                            }
                        } elseif (Helpers::isTypeScalar($type)) {
                            $value = $reader->readString();
                            settype($value, $type);
                        } else {
                            $type = str_replace('[]', '', $type);

                            if (isset($xmlProperty->annotations['serializedList'])) {
                                $value = [];
                                $this->process($reader, $type, ['array'], $value);
                                $this->processEndTag($xmlProperty, $object, $value, $tags);
                            } else {
                                $value = $this->process($reader, $type, $tags);
                                $this->processEndTag($xmlProperty, $object, $value, $tags);
                            }
                        }
                    }
                } elseif (Helpers::isTypeScalar($root)) {
                    $value = $reader->readString();
                    settype($value, $root);
                }
            } elseif ($reader->nodeType == XMLReader::END_ELEMENT) {
                $this->processEndTag($xmlProperty, $object ?? null, $value ?? null, $tags);

                if (!is_null($array) && count($tags) == $targetTagDepth + 1) {
                    if (isset($object)) {
                        $array[] = $object;
                        $object = $reflectionClass->newInstanceWithoutConstructor();
                    } else {
                        $array[] = $value;
                    }
                }

                if (count($tags) == $targetTagDepth) {
                    break;
                }
            }
        }

        return $object ?? null;
    }

    private function processEndTag($xmlProperty, $object, $value, &$tags) {
        if (!is_null($xmlProperty)) {
            $xmlProperty->property->setValue($object, $value);
        }

        array_pop($tags);
    }

    /**
     * @param \ReflectionProperty[] $properties
     * @return array
     */
    private function createPropertyMap(array $properties): array {
        $propertyMap = [];
        foreach ($properties as $property) {
            $docComment = $property->getDocComment();
            $annotations = Helpers::getAnnotations($docComment);
            $property->setAccessible(true);
            if (isset($annotations['serializedName']) && isset($annotations['var'])) {
                $xmlProperty = new XmlProperty();
                $xmlProperty->property = $property;
                $xmlProperty->annotations = $annotations;

                $xmlKey = $annotations['serializedName'][0];
                $propertyMap[$xmlKey] = $xmlProperty;
            }
        }
        return $propertyMap;
    }
}
