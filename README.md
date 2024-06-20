# API stub generator

From your PHP code, generates bindings/interfaces in another language.

Only usable use case is generating PHP classes to TypeScript interfaces for now.

For example, it can generates REST API frontend TypeScript interfaces from your
PHP classes for using those into your application frontend source code.

# Roadmap

- [ ] feature: plugin system.
- [x] type aliasing: attribute
- [ ] type aliasing: attribute groups property
- [ ] type aliasing: configuration static map
- [x] type aliasing: well-known types from `ramsey/uuid`, `symfony/uid`, etc...
- [ ] integration: API Platform.
- [x] integration: Doctrine.
- [ ] tests: Unit test group handling.

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
        source:
            - App\Entity\Article
            - App\Entity\BlogPost
            - App\Entity\USer
```

Default configuration will work seamlessly and do the following:

 - Generated code is TypeScript.
 - Target generated code directory is `%kernel.project_dir%/assets/src`.
 - Entities to generated are looked up using either API Platform configuration
   or Doctrine ORM entities configuration as a fallback.
 - Considering you are using Symfony defaults, your entities are in the
   `App\Entities\` namespace, then the generated TypeScript code will be placed
   in the `%kernel.project_dir%/assets/src/interfaces/index.ts` file.

All paths, behaviour, language, are configurable.

# Exported interfaces configuration

@todo Document here attributes

# Standalone usage

Example usage:

```php
<?php

declare(strict_types=1);

namespace MakinaCorpus\ApiGenerator\Command;

use App\Entity;
use MakinaCorpus\ApiGenerator\Configuration;
use MakinaCorpus\ApiGenerator\Generator;
use MakinaCorpus\ApiGenerator\GeneratorContext;
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
    context: new GeneratorContext(
        configuration: new Configuration(
            namespaceInputPrefix: 'App\\Entity',
            namespaceOutputPrefix: 'interfaces/api',
        ),
    ),
    directory: $directory,
    source: $source,
    language: new TypeScriptLanguage(),
);
```

Please read the `MakinaCorpus\ApiGenerator\Configuration` code for all existing options.
