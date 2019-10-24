<?php

namespace PHP_XML;

class Helpers {
    public static function getAnnotations($docComment): array {
        if ($docComment === false) {
            return [];
        }

        $annotations = [];

        // Strip away the docblock header and footer
        // to ease parsing of one line annotations
        $docComment = substr($docComment, 3, -2);

        $re = '/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m';
        if (preg_match_all($re, $docComment, $matches)) {
            $numMatches = count($matches[0]);

            for ($i = 0; $i < $numMatches; ++$i) {
                $annotations[$matches['name'][$i]][] = $matches['value'][$i];
            }
        }

        return $annotations;
    }

    public static function getTypeFromAnnotation(string $annotation) {
        $type = $annotation;
        $parts = explode("|", $type);
        if (count($parts) > 1) {
            $type = $parts[0];
        }
        return $type;
    }

    public static function isTypeScalar(string $type): bool {
        return in_array($type, ['string', 'int', 'float', 'double', 'bool']);
    }
}
