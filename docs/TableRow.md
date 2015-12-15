# vakata\orm\TableRow
Used in conjunction with \vakata\orm\Table
This class should not be instantiated manually - the table class will create instances as needed.

When iterating a table what you get are instances of this class. Columns are available as properties.
## Methods

| Name | Description |
|------|-------------|
|[getID](#vakata\orm\tablerowgetid)|Get the primary key for the row, always an array in a "column"=>"value" format.|
|[toArray](#vakata\orm\tablerowtoarray)|Get the row as an array.|
|[fromArray](#vakata\orm\tablerowfromarray)|Merge new data in the current row.|
|[save](#vakata\orm\tablerowsave)|Persist changes to DB.|
|[delete](#vakata\orm\tablerowdelete)|Delete current row from the DB.|

---



### vakata\orm\TableRow::getID
Get the primary key for the row, always an array in a "column"=>"value" format.  


```php
public function getID () : array    
```

|  | Type | Description |
|-----|-----|-----|
|  |  |  |
| `return` | `array` | the primary key columns and their values |

---


### vakata\orm\TableRow::toArray
Get the row as an array.  


```php
public function toArray (  
    boolean $full  
) : array    
```

|  | Type | Description |
|-----|-----|-----|
| `$full` | `boolean` | should relations be included (defaults to true) |
|  |  |  |
| `return` | `array` | the row as an array |

---


### vakata\orm\TableRow::fromArray
Merge new data in the current row.  


```php
public function fromArray (  
    array $data  
)   
```

|  | Type | Description |
|-----|-----|-----|
| `$data` | `array` | data to merge in "column"=>"value" format |

---


### vakata\orm\TableRow::save
Persist changes to DB.  


```php
public function save ()   
```


---


### vakata\orm\TableRow::delete
Delete current row from the DB.  


```php
public function delete ()   
```


---

