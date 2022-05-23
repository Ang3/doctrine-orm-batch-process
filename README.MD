Doctrine ORM Batch Process
==========================

[![Build Status](https://api.travis-ci.com/Ang3/doctrine-orm-batch-process.svg?branch=main)](https://app.travis-ci.com/github/Ang3/doctrine-orm-batch-process)
[![Latest Stable Version](https://poser.pugx.org/ang3/doctrine-orm-batch-process/v/stable)](https://packagist.org/packages/ang3/doctrine-orm-batch-process)
[![Latest Unstable Version](https://poser.pugx.org/ang3/doctrine-orm-batch-process/v/unstable)](https://packagist.org/packages/ang3/doctrine-orm-batch-process)
[![Total Downloads](https://poser.pugx.org/ang3/doctrine-orm-batch-process/downloads)](https://packagist.org/packages/ang3/doctrine-orm-batch-process)

This component helps you to deal with 
[batch-processing](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/batch-processing.html) 
in the context of Doctrine ORM transactions.

> The main problem with bulk operations is usually not to run out of memory and this is especially
> what the strategies presented here provide help with.
> -- *Doctrine ORM documentation*

The batch process component allows you to build bulk operations very easily with some advanced features.

Installation
------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require ang3/doctrine-orm-batch-process
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Usage
-----

A batch process is a basic **loop** using an **iterator** to do some logic with a **handler**.
The iterator is mandatory and the handler is optional.

In all cases, the entity manager is flushed and cleared when the buffer size is reached (by default: 20).

## Create a process

To create a process, use the constructor with the entity manager and an iterator, 
or use one of defined static shortcut methods as to your needs:

```php
use Ang3\Component\Doctrine\ORM\BatchProcess\BatchProcess;

$myProcess = new BatchProcess($entityManager, $myIterator, $myHandler = null, $bufferSize = 20);

// For chaining calls
$myProcess = BatchProcess::create($entityManager, $myIterator);

/*
 * SHORTCUT METHODS FOR BUILT-IN ITERATORS
 */

// Iterate from identified entities
$myProcess = BatchProcess::iterateIdentifiedEntities($entityManager, $entityFqcn, array $identifiers);

// Iterate from an ORM query builder instance
$myProcess = BatchProcess::iterateQueryBuilderResult($queryBuilder);

// Iterate from an ORM query
$myProcess = BatchProcess::iterateOrmQuery($query);

// Iterate from a callable
$myProcess = BatchProcess::iterateCallable(EntityManagerInterface $entityManager, callable $callback);

// Iterate from iterable data
$myProcess = BatchProcess::iterate($entityManager, $data);
```

To execute the process:

```php
$nbIterations = $myProcess->execute();
```

## Advanced options

The batch process provides some useful advanced options.

### Handlers

A handler is a class implementing the interface
```Ang3\Component\Doctrine\ORM\BatchProcess\Handler\ProcessHandlerInterface```. This class is called on each iteration. 
It allows you to persist/remove entities, or whatever you need with custom handlers.

This interface describes the method called by the batch process: 
```__invoke(\Ang3\Component\Doctrine\ORM\BatchProcess\ProcessIteration $iteration)```.

The component provides some useful built-in handlers.

#### Persist entity handler

This handler persists entities into database.

```php
use Ang3\Component\Doctrine\ORM\BatchProcess\Handler\PersistEntityHandler;

$myHandler = PersistEntityHandler::new()
    // Handler options...
    ->skipInsertions() // Ignore new entities (without ID)
    ->skipUpdates() // Ignore stored entities (with ID)
    ->onPrePersist($myCallable) // This callable is called BEFORE each persist.
    ->onPostPersist($myCallable) // This callable is called AFTER each persist.
;
```

Callable arguments:

1. ```object``` The entity
2. ```Ang3\Component\Doctrine\ORM\BatchProcess\ProcessIteration``` The iteration.

#### Remove entity handler

This handler removes entities into database.

```php
use Ang3\Component\Doctrine\ORM\BatchProcess\Handler\RemoveEntityHandler;

$myHandler = RemoveEntityHandler::new()
    // Handler options...
    ->onPreRemove($myCallable) // This callable is called BEFORE each removing.
    ->onPostRemove($myCallable) // This callable is called AFTER each removing.
;
```

Callable arguments:

1. ```object``` The entity
2. ```Ang3\Component\Doctrine\ORM\BatchProcess\ProcessIteration``` The iteration.

#### Callable handler

This handler holds a simple callable.

```php
use Ang3\Component\Doctrine\ORM\BatchProcess\Handler\CallableHandler;

$myHandler = CallableHandler::new($myCallable);
```

Callable arguments:

1. ```mixed``` Iterator data
2. ```Ang3\Component\Doctrine\ORM\BatchProcess\ProcessIteration``` The iteration.

#### Chain handler

This handler allows you to chain handlers in a specific order.

```php
use Ang3\Component\Doctrine\ORM\BatchProcess\Handler\ChainHandler;

$myHandler = ChainHandler::new()
    // Handler options...
    ->append($myHandler1) // Add a handler at the end of the chain
    ->prepend($myHandler2) // Add a handler at the beginning of the chain
    ->clear() // Remove all handlers from the chain
;
```

#### Custom handler

Create a class implementing the interface ```Ang3\Component\Doctrine\ORM\BatchProcess\Handler\ProcessHandlerInterface```.

Use the trait ```Ang3\Component\Doctrine\ORM\BatchProcess\Handler\ProcessHandlerTrait``` to enable options.

```php
namespace App\Doctrine\BatchProcess\Handler;

use Ang3\Component\Doctrine\ORM\BatchProcess\Handler\ProcessHandlerInterface;

final class MyHandler implements ProcessHandlerInterface
{
    public function __invoke(ProcessIteration $iteration): void
    {
        // Data from the process iterator
        $data = $iteration->getData();
        
        // You can retrieve the entity manager from the iteration.
        $em = $iteration->getEntityManager();
    }
}
```

Use the trait ```Ang3\Component\Doctrine\ORM\BatchProcess\Handler\ProcessHandlerTrait``` to enable options:

```php
// ...
final class MyHandler implements ProcessHandlerInterface
{
    // If you want to add options:
    use ProcessHandlerTrait;
    
    // Internal constants used to define options
    private const OPTION_ENABLED = 'enabled';
    
    public function __invoke(ProcessIteration $iteration): void
    {
        // Retrieves option values
        $enabled = $this->getOption(self::OPTION_ENABLED);
        
        // your logic with the option...
    }

    // Return the handler to allow chaining calls
    public function enableOption(bool $enabled = true): self
    {
       $this->setOption(self::OPTION_ENABLED, $enabled);
    
       return $this;
    }
}
```

#### Buffer size

You can modify the default buffer size:

```php
$myProcess->setBufferSize(50); // By default: 20
```

#### Disable ID generators

You can turn off the ID generator for one or more entities, useful to migrate data.

```php
$myProcess->disableIdGenerator(MyClass1::class, MyClass2::class/*, ...*/);
```

You can restore some disabled ID generators like below:

```php
$myProcess->restoreIdGenerator(MyClass1::class, MyClass2::class/*, ...*/);

// ... Or restore all directly
$myProcess->restoreAllIdGenerators();
```

#### Transactional entities

The entity manager could be cleared many times. That's why during the process, all entities loaded before 
the execution will be detached from the manager.

```php
$myLoadedEntity; // attached
$myProcess->execute();
dump($myLoadedEntity); // Probably detached - If you persist, a new entity could be created!
```

To avoid reloading your entities, this component helps you to keep your
entities in memory (by [variable reference](https://www.php.net/manual/fr/language.references.pass.php)) 
in order to reload it automatically.

```php
$myLoadedEntity; // attached
$myProcess
    ->addTransactionalEntity($myLoadedEntity) // This variable is passed by reference
    ->execute();
dump($myLoadedEntity); // reloaded by the process on each flush/clear
```

#### On flush

You can pass a callable to the process to execute some logic on each flush/clear operations.

```php
$myProcess->onFlush($myCallable);
```

Callable arguments:

1. ```Ang3\Component\Doctrine\ORM\BatchProcess\BatchProcess``` The current process
2. ```Ang3\Component\Doctrine\ORM\BatchProcess\ProcessIteration|null``` The iteration (NULL in case of last flush/clear).