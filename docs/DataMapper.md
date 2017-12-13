# vakata\orm\DataMapper  







## Methods

| Name | Description |
|------|-------------|
|[delete](#datamapperdelete)|Delete an entity|
|[entity](#datamapperentity)|Get an entity from an array of fields|
|[insert](#datamapperinsert)|Insert an entity, returning the primary key fields and their value|
|[toArray](#datamappertoarray)|Convert an entity to an array of fields, optionally including relation fields.|
|[update](#datamapperupdate)|Update an entity|




### DataMapper::delete  

**Description**

```php
public delete (mixed $entity)
```

Delete an entity 

 

**Parameters**

* `(mixed) $entity`

**Return Values**

`array`

> a key value map of the primary key columns  




### DataMapper::entity  

**Description**

```php
public entity (array $row)
```

Get an entity from an array of fields 

 

**Parameters**

* `(array) $row`

**Return Values**

`mixed`





### DataMapper::insert  

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




### DataMapper::toArray  

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





### DataMapper::update  

**Description**

```php
public update (mixed $entity)
```

Update an entity 

 

**Parameters**

* `(mixed) $entity`

**Return Values**

`array`

> a key value map of the primary key columns  



