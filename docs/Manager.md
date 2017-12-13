# vakata\orm\Manager  

Manager ORM class





## Methods

| Name | Description |
|------|-------------|
|[__call](#manager__call)||
|[__construct](#manager__construct)|Create an instance|
|[fromQuery](#managerfromquery)|Get a repository from a table query|
|[fromTable](#managerfromtable)|Get a repository for a given table name|
|[getMapper](#managergetmapper)|Get a mapper for a table name, if a mapper is not found a new generic mapper is registered using \StdClass|
|[hasMapper](#managerhasmapper)|Is a mapper available for a given table name|
|[registerGenericMapper](#managerregistergenericmapper)|Add a generic mapper for a table name|
|[registerGenericMapperWithClassName](#managerregistergenericmapperwithclassname)|Add a generic mapper for a table name using a class name|
|[registerMapper](#managerregistermapper)|Add a mapper for a specific table|




### Manager::__call  

**Description**

```php
public __call (void)
```

 

 

**Parameters**

`This function has no parameters.`

**Return Values**




### Manager::__construct  

**Description**

```php
public __construct (\DBInterface $db)
```

Create an instance 

 

**Parameters**

* `(\DBInterface) $db`
: the database schema  

**Return Values**




### Manager::fromQuery  

**Description**

```php
public fromQuery (\TableQuery $query)
```

Get a repository from a table query 

 

**Parameters**

* `(\TableQuery) $query`

**Return Values**

`\Repository`





### Manager::fromTable  

**Description**

```php
public fromTable (string $table)
```

Get a repository for a given table name 

 

**Parameters**

* `(string) $table`

**Return Values**

`\Repository`





### Manager::getMapper  

**Description**

```php
public getMapper (string $table)
```

Get a mapper for a table name, if a mapper is not found a new generic mapper is registered using \StdClass 

 

**Parameters**

* `(string) $table`

**Return Values**

`\DataMapper`





### Manager::hasMapper  

**Description**

```php
public hasMapper (string $table)
```

Is a mapper available for a given table name 

 

**Parameters**

* `(string) $table`
: the table name  

**Return Values**

`boolean`





### Manager::registerGenericMapper  

**Description**

```php
public registerGenericMapper (string $table, callable $creator)
```

Add a generic mapper for a table name 

 

**Parameters**

* `(string) $table`
: the table name  
* `(callable) $creator`
: a callable to invoke when a new instance is needed  

**Return Values**

`$this`





### Manager::registerGenericMapperWithClassName  

**Description**

```php
public registerGenericMapperWithClassName (string $table, string $class)
```

Add a generic mapper for a table name using a class name 

 

**Parameters**

* `(string) $table`
: the table name  
* `(string) $class`
: the class name to use when creating new instances  

**Return Values**

`$this`





### Manager::registerMapper  

**Description**

```php
public registerMapper (string $table, \DataMapper $mapper)
```

Add a mapper for a specific table 

 

**Parameters**

* `(string) $table`
: the table name  
* `(\DataMapper) $mapper`
: the mapper instance  

**Return Values**

`$this`




