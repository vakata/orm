# vakata\orm\UnitOfWork  







## Methods

| Name | Description |
|------|-------------|
|[__construct](#unitofwork__construct)|Create an instance|
|[append](#unitofworkappend)|Add a new entity (mark for insertion)|
|[change](#unitofworkchange)|Mark an entity as changed|
|[register](#unitofworkregister)|Register an entity in the unit (will be checked for changes when save is called)|
|[remove](#unitofworkremove)|Mark an entity for removal|
|[save](#unitofworksave)|Persist all changes|




### UnitOfWork::__construct  

**Description**

```php
public __construct (\DBInterface $db)
```

Create an instance 

 

**Parameters**

* `(\DBInterface) $db`
: the database object (a transaction will be used when calling save)  

**Return Values**




### UnitOfWork::append  

**Description**

```php
public append (mixed $entity, \Repository $repository)
```

Add a new entity (mark for insertion) 

 

**Parameters**

* `(mixed) $entity`
* `(\Repository) $repository`
: the entity's repository  

**Return Values**

`string`

> the entity's hash  




### UnitOfWork::change  

**Description**

```php
public change (mixed $entity, \Repository $repository)
```

Mark an entity as changed 

 

**Parameters**

* `(mixed) $entity`
* `(\Repository) $repository`
: the entity's repository  

**Return Values**

`string`

> the entity's hash  




### UnitOfWork::register  

**Description**

```php
public register (mixed $entity, \Repository $repository)
```

Register an entity in the unit (will be checked for changes when save is called) 

 

**Parameters**

* `(mixed) $entity`
* `(\Repository) $repository`
: the entity's repository  

**Return Values**

`string`

> the entity's hash  




### UnitOfWork::remove  

**Description**

```php
public remove (mixed $entity, \Repository $repository)
```

Mark an entity for removal 

 

**Parameters**

* `(mixed) $entity`
* `(\Repository) $repository`
: the entity's repository  

**Return Values**

`string`

> the entity's hash  




### UnitOfWork::save  

**Description**

```php
public save (void)
```

Persist all changes 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`




