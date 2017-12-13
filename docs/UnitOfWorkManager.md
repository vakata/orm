# vakata\orm\UnitOfWorkManager  

Manager ORM class implementing Unit Of Work, so that all changes are persisted in a single transaction



## Extend:

vakata\orm\Manager

## Methods

| Name | Description |
|------|-------------|
|[save](#unitofworkmanagersave)|Save all the pending changes|

## Inherited methods

| Name | Description |
|------|-------------|
|__call|-|
|__construct|Create an instance|
|fromQuery|Get a repository from a table query|
|fromTable|Get a repository for a given table name|
|getMapper|Get a mapper for a table name, if a mapper is not found a new generic mapper is registered using \StdClass|
|hasMapper|Is a mapper available for a given table name|
|registerGenericMapper|Add a generic mapper for a table name|
|registerGenericMapperWithClassName|Add a generic mapper for a table name using a class name|
|registerMapper|Add a mapper for a specific table|



### UnitOfWorkManager::save  

**Description**

```php
public save (void)
```

Save all the pending changes 

 

**Parameters**

`This function has no parameters.`

**Return Values**

`void`




