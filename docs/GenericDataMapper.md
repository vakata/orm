# vakata\orm\GenericDataMapper  

A generic class mapping an instance creation function to a table in the DB.

## Implements:
vakata\orm\DataMapper



## Methods

| Name | Description |
|------|-------------|
|[__construct](#genericdatamapper__construct)|Create an instance|
|[delete](#genericdatamapperdelete)|Delete an entity|
|[entity](#genericdatamapperentity)|Get an entity from an array of fields|
|[insert](#genericdatamapperinsert)|Insert an entity, returning the primary key fields and their value|
|[toArray](#genericdatamappertoarray)|Convert an entity to an array of fields, optionally including relation fields.|
|[update](#genericdatamapperupdate)|Update an entity|




### GenericDataMapper::__construct  

**Description**

```php
public __construct (\Manager $manager, \DBInterface $db, string $table, callable $create)
```

Create an instance 

 

**Parameters**

* `(\Manager) $manager`
: the manager object  
* `(\DBInterface) $db`
: the database access object  
* `(string) $table`
: the table name to query  
* `(callable) $create`
: invoked with an array of fields when a new instance needs to be created  

**Return Values**




### GenericDataMapper::delete  

**Description**

```php
public delete (mixed $entity)
```

Delete an entity 

 

**Parameters**

* `(mixed) $entity`

**Return Values**

`int`

> the number of deleted rows  




### GenericDataMapper::entity  

**Description**

```php
public entity (array $row)
```

Get an entity from an array of fields 

 

**Parameters**

* `(array) $row`

**Return Values**

`mixed`





### GenericDataMapper::insert  

**Description**

```php
public insert (mixed $entity)
```

Insert an entity, returning the primary key fields and their value 

 

**Parameters**

* `(mixed) $entity`

**Return Values**

`array`

> a key value map of the primary key columns  




### GenericDataMapper::toArray  

**Description**

```php
public toArray (mixed $entity, bool $relations)
```

Convert an entity to an array of fields, optionally including relation fields. 

 

**Parameters**

* `(mixed) $entity`
: the entity to convert  
* `(bool) $relations`
: should the 1 end of relations be included, defaults to `true`  

**Return Values**

`array`





### GenericDataMapper::update  

**Description**

```php
public update (mixed $entity)
```

Update an entity 

 

**Parameters**

* `(mixed) $entity`

**Return Values**

`int`

> the number of affected rows  



