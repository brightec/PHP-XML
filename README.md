# PHP-XML

This is a library for serialization and deserialization between XML and PHP objects. The mapping between XML tag and PHP property is
done using annotations inside PHPDoc comments.

## Basic Usage

Given the following classes:

```php
namespace App;

class Films {
    /**
     * @var \App\Film[]
     * @serializedName Films
     * @serializedList Film
     */
    public $films;
}
```

```php
namespace App;

class Film {
    /**
     * Film title
     *
     * @var string|null
     * @serializedName Title
     */
    public $title;

    /**
     * Release year (USA)
     *
     * @var int|null
     * @serializedName ReleaseYear
     */
    public $releaseYear;
}
```

### Deserializing

```php
$body = <<<EOF
<Films>
    <Film>
        <Title>Avengers: Assemble</Title>
        <ReleaseYear>2012</ReleaseYear>
    </Film>
    <Film>
        <Title>Avengers: Age of Ultron</Title>
        <ReleaseYear>2015</ReleaseYear>
    </Film>
    <Film>
        <Title>Avengers: Infinity War</Title>
        <ReleaseYear>2018</ReleaseYear>
    </Film>
    <Film>
        <Title>Avengers: Endgame</Title>
        <ReleaseYear>2019</ReleaseYear>
    </Film>
</Films>
EOF;

$deserializer = new Deserializer();
try {
    $object = $deserializer->parse($body, Films::class);
    var_dump($object);
} catch (ReflectionException $e) {
    var_dump($e);
}
```

Output
```php
object(App\Films)[7]
  public 'films' => 
    array (size=4)
      0 => 
        object(App\Film)[13]
          public 'title' => string 'Avengers: Assemble' (length=18)
          public 'releaseYear' => int 2012
      1 => 
        object(App\Film)[14]
          public 'title' => string 'Avengers: Age of Ultron' (length=23)
          public 'releaseYear' => int 2015
      2 => 
        object(App\Film)[15]
          public 'title' => string 'Avengers: Infinity War' (length=22)
          public 'releaseYear' => int 2018
      3 => 
        object(App\Film)[16]
          public 'title' => string 'Avengers: Endgame' (length=17)
          public 'releaseYear' => int 2019
```

### Serializing

```php
$films = new Films(
    [
        new Film("Guardians of the Galaxy", 2014),
        new Film("Guardians of the Galaxy Vol. 2", 2017),
    ]
);

$serializer = new Serializer();
try {
    $xml = $serializer->write($films, null);
    var_dump($xml);
} catch (ReflectionException $e) {
    var_dump($e);
}
```

Output
```xml
<?xml version="1.0"?>
<Films><Film><Title>Guardians of the Galaxy</Title><ReleaseYear>2014</ReleaseYear></Film><Film><Title>Guardians of the Galaxy Vol. 2</Title><ReleaseYear>2017</ReleaseYear></Film></Films>
```

## Annotations

- @var Defines the type of the property.
  - Note: Types must always be fully qualified e.g. ```@var \App\Film``` rather than ```@var Film```
- @serializedName Defines the XML tag for the property
- @serializedList Defines the property as an array, and specifies the XML tag of the child elements
- @cdata Specifies that the property should be wrapped in a CDATA tag (Used in serialization only)

## Types

This library currently supports the following types:

- DateTime with the format ```Y-m-d\TH:i:s```
- Scalar types: string, int, float, double, bool
- Custom classes and arrays of custom classes e.g. Foo, Foo[]

## Possible improvements

- Allow custom converters to be defined, such as int <-> bool, DateTime formats
- Use [Doctrine Annotations](https://github.com/doctrine/annotations)

## See Also

- https://github.com/runz0rd/mapper-php
- http://sabre.io/xml/
