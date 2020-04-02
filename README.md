`db-api` is a PHP API to easily access and modify a MySQL database via function
calls or HTTP requests by exchanging JSON objects (or PHP nested arrays).

== API ==
Define database structure:

```php
$postsSpec = array(
  'id' => 'posts',
  'fields' => array(
    'id' => array(
      'type' => 'int',
    ),
    'author' => array(
      'type' => 'string',
    ),
    'message' => array(
      'type' => 'string',
    ),
    'commentsCount' => array(
      'type' => 'int',
      'select' => 'select count(*) from comments where postId=comments.id',
    ),
    'comments' => array(
      'type' => 'sub_table',
      'id' => 'comments',
      'parent_field' => 'postId',
      'fields' => array(
        'id' => array(
          'type' => 'int',
        ),
        'postId' => array(
          'type' => 'int',
        ),
        'author' => array(
          'type' => 'string',
        ),
        'message' => array(
          'type' => 'string',
        ),
      ),
    ),
  ),
  'additional_filters' => array(
    'foo' => array(
      'compile' => function ($value, $db) {
        return 'message=' . $db->quote($value);
      }
    ),
  ),
);
```

Initialize the database:
```sql
create table posts (
  id            int             not null auto_increment,
  author        tinytext        not null,
  message       mediumtext      null,
  primary key (id)
);

create table comments (
  id            int             not null auto_increment,
  postId        int             not null,
  author        tinytext        not null,
  message       mediumtext      null,
  primary key (id),
  foreign key (postId) references posts(id) on update cascade on delete cascade
)
```

Initialize API:
```php
$dbconf = array(
  'type' => 'mysql',
  'dbname' => 'test',
  'username' => 'username',
  'password' => 'password',
  'debug' => 0,
);
$dbconf[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
$db = new PDOext($dbconf);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$api = new DBApi($db);
$api->addTable($postsSpec);
```

Add some posts to the database:
```php
$result = $api->do(array(
  array(
    'action' => 'insert-update',
    'table' => 'posts',
    'on' => array(
      // for possible events, see below
      'insert' => function ($id, $table) { ... },
    ),
    'data' => array(
      array(
        'author' => 'Alice',
        'message' => 'A first blog post',
      ),
      array(
        'author' => 'Bob',
        'message' => 'A 2nd blog post with comments',
        'comments' => array(
          array(
            'author' => 'Alice',
            'message' => 'That's a nice post!',
          ),
          array(
            'author' => 'Bob',
            'message' => 'My pleasure :-)',
          ),
        ),
      ),
    ),
  ),
  array(
    'action' => 'select',
    'table' => 'posts',
  ),
));
```

The result will include the result of each action, first the insert
statements, then the select.

result:
```json
```

=== Events ===
DBTable emits events, when an action happens. The following events are defined:
* 'insert'
* 'update'
* 'delete'

The following parameters will be passed to the event:
* ids (string array of affected ids)
* table (pointer to the DBTable instance)

Event handlers can also be set in the schema definition (parameter 'on')
