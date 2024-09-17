# API stub generator

From your PHP code, generates bindings/interfaces in another language.

Only usable use case is generating PHP classes to TypeScript interfaces for now.

For example, it can generates REST API frontend TypeScript interfaces from your
PHP classes for using those into your application frontend source code.

Main use case is for you to get typings on the frontend side when a Symfony site
exposes a REST API. It can both use Doctrine ORM and API Platform configurations
for generating the API interfaces.

While a Symfony bundle is provided, the core library is standalone and allows
to generate any arbitrary class representation to another language interface.

# Installation

Install it using composer:

```sh
composer require makinacorpus/api-generator
```

# Symfony bundle

Add the bundle into your `config/bundles.php` file:

```php
<?php

return [
    // ... your other bundles.
    MakinaCorpus\ApiGenerator\Bridge\Symfony\ApiGeneratorBundle::class => ['all' => true],
];
```

Configure it using `config/packages/api_generator.yaml`:

```yaml
api_generator:
    defaults:
        namespace_prefix_input: App\Entity
        namespace_prefix_output: interfaces
        directory: "%kernel.project_dir%/assets/src"
        # Multiple configurations are possible, groups allow you to target
        # how to generate different interfaces for each PHP class in each
        # group configuration.
        groups: []
        # If "source" is an array, entities to generate will not be looked
        # up automatically, and you must specify an array of PHP classes
        # instead.
        sources:
            - App\Entity\Article
            - App\Entity\BlogPost
            - App\Entity\USer
```

Default configuration will work seamlessly and do the following:

 - Generated code is TypeScript.
 - Target generated code directory is `%kernel.project_dir%/assets/src`.
 - Entities to generated are looked up using either API Platform configuration
   or Doctrine ORM entities configuration as a fallback
   (_API Platform is not implemented yet_).
 - Considering you are using Symfony defaults, your entities are in the
   `App\Entities\` namespace, then the generated TypeScript code will be placed
   in the `%kernel.project_dir%/assets/src/interfaces/index.ts` file.

All paths, behaviour, language, are configurable.

## Doctrine ORM integration

Per default, if you do not configure the Symfony bundle, it will generate
interfaces for all entity classes. Classes that are types for properties
of those entities, or superclasses will be generated as well.

## API Platform integration

_API Platform is not implemented yet._

If present, all entities carrying the `#[ApiResource]` attribute will have
their interfaces generated, no matter those entities are managed using
Doctrine ORM or not.

**Warning: it doesn't rely upon the serializer component and other attributes yet.**
This means that generated interfaces' properties and those exposed by the REST
API might have diverging names. Hopefully, you can configure this manually.

# Exported interfaces tuning

## Groups

All attributes for attribute based configuration accept a `$groups` parameter
in their constructor signature. This property must be an array of strings
(keys are ignored).

When you run the API generator using a specific configuration, you can set
one or more groups in the configuration, only the rules that matches the
configuration groups will be taken into account.

## Change the class name

You want to give another name than the PHP class name.

Use the `#[GeneratedType(name: 'new_name')]` attribute on the class you wish
to rename.

Exemple:

```php
namespace App\Entity;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedType;

#[GeneratedType(name: 'NewShinyName')]
class SomeEntity
{
}
```

## Change the class namespace

Your namespaces on the generated side doesn't match the PHP namespace
structure, or simply you want to flatten things.

Use the `#[GeneratedType(namespace: 'foo/b ar')]` attribute on the class you
wish to change its namespace.

Exemple:

```php
namespace App\Entity;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedType;

#[GeneratedType(namespace: 'api/interface')]
class SomeEntity
{
}
```

## Ignore a class

You don't want to expose a specific class or interface, and simply want the
`any`, `mixed`, ... type exposed instead.

Use the `#[GeneratedType(ignore: true)]` attribute on the class you wish
to completely hide to the frontend.

Exemple:

```php
namespace App\Entity;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedType;

#[GeneratedType(ignore: true)]
class ThisClassWillBeAnyInGeneratedCode
{
}
```

## Change a property name

Your serialization process changes a property name, but this API didn't detect
the serializer configuration.

Use the `#[GeneratedProperty(name: 'foo')]` attribute on the property you wish
to rename.

Example:

```php
namespace App\Entity;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedProperty;

class SomeEntity
{
    #[GeneratedProperty(name: 'someSerializedPropertyName')]
    private SomeType $somePropertyName;
}
```

## Change a property type

You need to expose a different property type that the one on the PHP class, or
the type is array or iterable and the value type could not be guessed.

Use the `#[GeneratedProperty(type: 'some_type')]` attribute on the property you
wish to explicit.

Please note that once you set the `$type` argument, you will also need to set
the `$nullable` and `$collection` arguments as well, as it will fully override
the property introspection.

Example:

```php
namespace App\Entity;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedProperty;

class SomeEntity
{
    /** @var SomeType[] */
    #[GeneratedProperty(type: SomeType::class, collection: true, nullable: false)]
    private array $someProperty;
}
```

## Ignore a property

Some of your properties are internal, and you don't want to expose it into
the interface definition.

Use the `#[GeneratedProperty(ignore: true)]` attribute on the property you wish
to ignore.

Note that all other attribute constructor arguments will be ignored as well
if the property is ignored (excepted for `$groups`).

Exemple:

```php
namespace App\Entity;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedProperty;

class SomeEntity
{
    #[GeneratedProperty(ignore: true)]
    private SomeType $someInternalProperty;
}
```

## Type aliasing a class

You may want to expose a complex type as another existing one on the target
language side, for exemple an identifier class to simply `string`.

Use the `#[GeneratedTypeAlias(name: 'string')]` attribute on the class you
wish to always be exposed as a string.

The `$name` argument can have any value: a PHP class name, if you wish to
substitute the class using another one, an arbitrary type name, anything.

Exemple:

```php
namespace App\Entity;

use MakinaCorpus\ApiGenerator\Attribute\GeneratedTypeAlias;

#[GeneratedTypeAlias(name: 'string')]
class SomeId
{
    public function __construct(private Ulid $id) {}

    #[\Override]
    public function __toString():string { return (string) $this->id; }
}
```

Warning: the type must be an existing source class name, or a known
target language type name, otherwise it'll fallback to `any`.

# Standalone usage

Example usage:

```php
<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Command;

use App\Entity;
use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Generator;
use MakinaCorpus\ApiGenerator\Output\Language\TypeScriptLanguage;
use MakinaCorpus\ApiGenerator\Source\ArraySource;

$directory = \dirname(__DIR__) . '/assets/src';

$source = new ArraySource([
    Entity\BlogPost::class,
    Entity\Article::class,
    Entity\User::class,
    // ...
]);

$generator = new Generator();

$generator->generate(
    context: new Configuration(
        namespaceInputPrefix: 'App\\Entity',
        namespaceOutputPrefix: 'interfaces/api',
    ),
    directory: $directory,
    source: $source,
    language: new TypeScriptLanguage(),
);
```

Please read the `MakinaCorpus\ApiGenerator\Configuration` code for all existing options.

# Roadmap

- [ ] feature: plugin system.
- [x] type aliasing: attribute
- [ ] type aliasing: attribute groups property
- [ ] type aliasing: configuration static map
- [x] type aliasing: well-known types from `ramsey/uuid`, `symfony/uid`, etc...
- [ ] integration: API Platform.
- [x] integration: Doctrine.
- [ ] tests: Unit test group handling.