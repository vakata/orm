# vakata\orm\Query
A database query class

## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\query__construct)|Create an instance|
|[getDefinition](#vakata\orm\querygetdefinition)|Get the table definition of the queried table|
|[filter](#vakata\orm\queryfilter)|Filter the results by a column and a value|
|[sort](#vakata\orm\querysort)|Sort by a column|
|[paginate](#vakata\orm\querypaginate)|Get a part of the data|
|[reset](#vakata\orm\queryreset)|Remove all filters, sorting, etc|
|[where](#vakata\orm\querywhere)|Apply an advanced filter (can be called multiple times)|
|[order](#vakata\orm\queryorder)|Apply advanced sorting|
|[limit](#vakata\orm\querylimit)|Apply an advanced limit|
|[count](#vakata\orm\querycount)|Get the number of records|
|[iterator](#vakata\orm\queryiterator)|Perform the actual fetch|
|[select](#vakata\orm\queryselect)|Perform the actual fetch|
|[insert](#vakata\orm\queryinsert)|Insert a new row in the table|
|[update](#vakata\orm\queryupdate)|Update the filtered rows with new data|
|[delete](#vakata\orm\querydelete)|Delete the filtered rows from the DB|
|[with](#vakata\orm\querywith)|Solve the n+1 queries problem by prefetching a relation by name|

---



### vakata\orm\Query::__construct
Create an instance  


```php
public function __construct (  
    \DatabaseInterface $db,  
    \Table $definition  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$db` | `\DatabaseInterface` | the database instance |
| `$definition` | `\Table` | the table definition of the table to query |

---


### vakata\orm\Query::getDefinition
Get the table definition of the queried table  


```php
public function getDefinition () : \Table    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `\Table` | the definition |

---


### vakata\orm\Query::filter
Filter the results by a column and a value  


```php
public function filter (  
    string $column,  
    mixed $value  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$column` | `string` | the column name to filter by (related columns can be used - for example: author.name) |
| `$value` | `mixed` | a required value, array of values or range of values (range example: ['beg'=>1,'end'=>3]) |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Query::sort
Sort by a column  


```php
public function sort (  
    string $column,  
    bool|boolean $desc  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$column` | `string` | the column name to sort by (related columns can be used - for example: author.name) |
| `$desc` | `bool`, `boolean` | should the sorting be in descending order, defaults to `false` |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Query::paginate
Get a part of the data  


```php
public function paginate (  
    int|integer $page,  
    int|integer $perPage  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$page` | `int`, `integer` | the page number to get (1-based), defaults to 1 |
| `$perPage` | `int`, `integer` | the number of records per page - defaults to 25 |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Query::reset
Remove all filters, sorting, etc  


```php
public function reset () : self    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Query::where
Apply an advanced filter (can be called multiple times)  


```php
public function where (  
    string $sql,  
    array $params  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$sql` | `string` | SQL statement to be used in the where clause |
| `$params` | `array` | parameters for the SQL statement (defaults to an empty array) |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Query::order
Apply advanced sorting  


```php
public function order (  
    string $sql,  
    array $params  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$sql` | `string` | SQL statement to use in the ORDER clause |
| `$params` | `array` | optional params for the statement (defaults to an empty array) |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Query::limit
Apply an advanced limit  


```php
public function limit (  
    int $limit,  
    int $offset  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$limit` | `int` | number of rows to return |
| `$offset` | `int` | number of rows to skip from the beginning (defaults to 0) |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Query::count
Get the number of records  


```php
public function count () : int    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `int` | the total number of records (does not respect pagination) |

---


### vakata\orm\Query::iterator
Perform the actual fetch  


```php
public function iterator (  
    array|null $fields  
) : \QueryIterator    
```

|  | Type | Description |
|-----|-----|-----|
| `$fields` | `array`, `null` | optional array of columns to select (related columns can be used too) |
|  |  |  |
| `return` | `\QueryIterator` | the query result as an iterator |

---


### vakata\orm\Query::select
Perform the actual fetch  


```php
public function select (  
    array|null $fields  
) : array    
```

|  | Type | Description |
|-----|-----|-----|
| `$fields` | `array`, `null` | optional array of columns to select (related columns can be used too) |
|  |  |  |
| `return` | `array` | the query result as an array |

---


### vakata\orm\Query::insert
Insert a new row in the table  


```php
public function insert (  
    array $data  
) : array    
```

|  | Type | Description |
|-----|-----|-----|
| `$data` | `array` | key value pairs, where each key is the column name and the value is the value to insert |
|  |  |  |
| `return` | `array` | the inserted ID where keys are column names and values are column values |

---


### vakata\orm\Query::update
Update the filtered rows with new data  


```php
public function update (  
    array $data  
) : int    
```

|  | Type | Description |
|-----|-----|-----|
| `$data` | `array` | key value pairs, where each key is the column name and the value is the value to insert |
|  |  |  |
| `return` | `int` | the number of affected rows |

---


### vakata\orm\Query::delete
Delete the filtered rows from the DB  


```php
public function delete () : int    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `int` | the number of deleted rows |

---


### vakata\orm\Query::with
Solve the n+1 queries problem by prefetching a relation by name  


```php
public function with (  
    string $relation  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$relation` | `string` | the relation name to fetch along with the data |
|  |  |  |
| `return` | `self` |  |

---

