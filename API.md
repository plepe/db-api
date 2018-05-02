== DBApi ==
=== constructor(db) - PHP ===
Creates the API. Pass an active PDOext element to it.

Example:
```php
$db = new PDOext(array(
  'type' => 'mysql',
  'dbname' => 'test',
  'username' => 'test',
  'password' => 'PASSWORD'
));
$api = new DBApi($db);
```

=== constructor(db, options, callback) - JS ===
Creates the API. Pass the URL to the PHP api.php file.

```js
let api = new DBApi('https://example.com/db/api.php', {}, function (err) {
  // err should be null
  // api is now ready ...
})
```

=== addTable(spec) - PHP only ===
Adds a table definition to the API.

Example:
```php
$db->addTable(array(
  'id' => 'post',
  'fields' => array(
    'id' => array(
      'type' => 'int'
    ),
    'author' => array(
      'type' => 'string'
    ),
    'message' => array(
      'type' => 'string'
    ),
  )
));
```

==== Table parameters ====
* id: (string, mandatory) id of the table
* fields: (array, mandatory) list of fields of this table
* id_field: (string) which field holds the primary key (default: 'id'). A primary key is necessary.
* old_id_field: (string) to detect renames with the 'insert-update' action, an old_field may supplied which holds the id again (default: '__id'). The old field will only be generated in the select action if the 'old_id' flag is set.
* table: (string) database name of the table (defaults to the value of 'id')
* order: (array) default order (see Action 'Select' for details)

==== Field parameters ====
* (string) The ID of the field is the key of the hash.
* type: (string) Type of field. The database value will be casted to this PHP/JS datatype. Available types: 'string' (default), 'boolean', 'float', 'int', 'sub_table' (see below).
* column: (string) The column name in the table (Defaults to the id of the field)
* select: (string) Use a custom SQL select statement, e.g. `substr(author, 1, 3)`.
* read: (boolean) If the column is readable (default: true)
* write: (boolean) If the column is writeable (default: false)
* include: (boolean) If the field should be included by default (default: true)

==== Sub tables ====
Sub table definitions look like table definition, but also include a 'parent_field' key which points to the foreign key to the table id.

Example:
```json
{
  "id": "post",
  "fields": {
    "id": {
      "type": "int"
    },
    "message": {
    },
    "comments": {
      "type": "sub_table",
      "parent_field": "post_id",
      "fields": {
        "id": {
          "type": "int",
        }
        "post_id": {
          "type": "int"
        }
        "message": {
        }
      }
    }
  }
```

=== getTable(id) - PHP/JS ===
Returns an instance of DBApiTable for the specified table.

=== do(actions) - PHP/JS ===
Execute a series of actions in a single transaction. JS version accepts an additional parameter callback which will be called with `(err, result)`.

Returns:

The result will be an array of the same size as the list of actions.

If an error occures an object will be returned with the following fields:
* error: error message

Available actions:
==== schema ====
Return the specification of all tables or a specific table. The result is always an array.

The following parameters are available:
* action: (string, mandatory) 'schema'
* table: (string) id of table

```js
api.do(
  [
    { action: 'schema', table: 'post' }
  ],
  function (err, result) {
    if (err) { return alert(err) }
    alert(JSON.stringify(result[0], null, '  '))
  }
)
```

Result:
```json
[
  {
    "id": "post",
    "fields": {
      "id": {
        "type": "int"
      },
      "author": {
        "type": "text"
      },
      "message": {
        "type": "text"
      }
    }
  }
]
```

=== Action 'select' ===
Executes a select query to the database and return the results. This is the default action, if no action is specified.

The following parameters are available:
* action: (string) 'select'
* table: (string, mandatory) id of table
* query: (array) only include elements which match certain criteria. See 'Query' for details.
* id: (int|string|array) return only elements which have this value or one of these values as primary key (even if the table has a different id field).
* order: (array) Order by the following fields. By default, order direction is ascending, precede by '-' to order descending. '+' forces ascending order.
* offset: (int) start at nth result (starting at 0)
* limit: (int) return only the first n results (after offset)
* fields: (array) limit list of queried fields (e.g. [ 'id', 'author' ]). Defaults to all fields which do not have `include=false`.
* old_id: (boolean) if true, include a field '__id' (or as specified in table definition 'old_id_field') with the id of the field (to detect renames) in this table and all sub tables.

Returns array of elements

=== Action 'update' ===
Update all matching elements. E.g.:
```json
{
  "action": "update",
  "update": {
    "author": "Dr. Bob"
  }
  "query": {
    [ "author", "=", "bob" ]
  }
}
```

The following parameters are available:
* action: (string, mandatory) 'update'
* update: (hash, mandatory) List of key/value pairs to update
* query: (array) only update elements which match certain criteria. See 'Query' for details.
* id: (int|string|array) return only elements which have this value or one of these values as primary key (even if the table has a different id field).
* limit: (int) update only n elements.
* offset: (int) start with the nth element.
* order: (array) Order by the specified fields.

Returns count of updated elements.

=== Action 'insert-update' ===
Insert or update an element. If an id is specified, either insert a new element or update an existing element with this element. If an old_id is specified, either update or rename an existing element.

The following parameters are available:
* action: (string, mandatory): 'insert-update'
* data: Object to update

Fields which are not specified in the data will not be changed.

Sub elements will also be updated. If a sub table field is included in the update, all sub object which are not listed will be deleted.

=== Action 'delete' ===
Delete all matching elements.

The following parameters are available:
* action: (string, mandatory) 'delete'
* query: (array) only update elements which match certain criteria. See 'Query' for details.
* id: (int|string|array) return only elements which have this value or one of these values as primary key (even if the table has a different id field).
* limit: (int) update only n elements.
* offset: (int) start with the nth element.
* order: (array) Order by the specified fields.

Returns count of deleted elements.

=== Query ===
Include only elements which match certain criteria, specified by an array of operations ,e.g.:
```json
{
  "table": "post",
  "action": "select",
  "query": [
    {
      "key": "author",
      "op": "=",
      "value": "Alice"
    },
    [ "date", ">", "2018-01-01" ]
  ]
}
```

There's a long version (first query) and a short version (second query).

The following operations are available:
* `=`: exact match (also, `null` as value will return elements with the field of value `null`)
* `>`, `<`, `>=`, `<=`: greater/lower than operations
* `in`: value is an array, matches all elements which matches any of the values., matches all elements which matches any of the values., matches all elements which matches any of the values., matches all elements which matches any of the values.
* `strsearch`: Tries to match strings in a convenient way.

In an action the parameters 'id' and 'query' can be combined, only elements which match both criteria will be selected.
