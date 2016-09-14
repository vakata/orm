# vakata\orm\Collection
A collection class - created automatically by the manager.

## Methods

| Name | Description |
|------|-------------|
|[__construct](#vakata\orm\collection__construct)|Create a collection instance|
|[count](#vakata\orm\collectioncount)|Get the count of items in the collection|
|[reset](#vakata\orm\collectionreset)|Reset the collection - useful to remove applied filters, orders, etc.|
|[with](#vakata\orm\collectionwith)|Make sure the collection will also contain some related data without requiring a new query|
|[filter](#vakata\orm\collectionfilter)|Filter a collection by a column and a value|
|[sort](#vakata\orm\collectionsort)|Sort by a column|
|[paginate](#vakata\orm\collectionpaginate)|Get a part of the data|
|[where](#vakata\orm\collectionwhere)|Apply an advanced filter on the collection (can be called multiple times)|
|[order](#vakata\orm\collectionorder)|Apply advanced sorting to the collection|
|[limit](#vakata\orm\collectionlimit)|Apply an advanced limit|
|[get](#vakata\orm\collectionget)|Get the whole object either as an array (if `single` is `false`) or the single resulting object|

---



### vakata\orm\Collection::__construct
Create a collection instance  


```php
public function __construct (  
    \Query $query,  
    \Manager $manager,  
    string $class,  
    array|null $current,  
    bool|boolean $single  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$query` | `\Query` | a query to populate this collection with |
| `$manager` | `\Manager` | the manager to which this collection belongs |
| `$class` | `string` | the class name to use when creating items |
| `$current` | `array`, `null` | optional prepopulated query result |
| `$single` | `bool`, `boolean` | optional flag indicating if this collection should only contain a single element |

---


### vakata\orm\Collection::count
Get the count of items in the collection  


```php
public function count () : int    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `int` | the number of items in the collection |

---


### vakata\orm\Collection::reset
Reset the collection - useful to remove applied filters, orders, etc.  


```php
public function reset () : self    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Collection::with
Make sure the collection will also contain some related data without requiring a new query  


```php
public function with (  
    string $relation  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$relation` | `string` | [description] |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Collection::filter
Filter a collection by a column and a value  


```php
public function filter (  
    string $column,  
    mixed $value  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$column` | `string` | the column to filter by |
| `$value` | `mixed` | the required value of the column |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Collection::sort
Sort by a column  


```php
public function sort (  
    string $column,  
    bool|boolean $desc  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$column` | `string` | the column name to sort by |
| `$desc` | `bool`, `boolean` | should the sort be in descending order, defaults to `false` |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Collection::paginate
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


### vakata\orm\Collection::where
Apply an advanced filter on the collection (can be called multiple times)  


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


### vakata\orm\Collection::order
Apply advanced sorting to the collection  


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


### vakata\orm\Collection::limit
Apply an advanced limit  


```php
public function limit (  
    int $limit,  
    int|integer $offset  
) : self    
```

|  | Type | Description |
|-----|-----|-----|
| `$limit` | `int` | number of rows to return |
| `$offset` | `int`, `integer` | number of rows to skip from the beginning |
|  |  |  |
| `return` | `self` |  |

---


### vakata\orm\Collection::get
Get the whole object either as an array (if `single` is `false`) or the single resulting object  


```php
public function get () : mixed    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `mixed` |  |

---

