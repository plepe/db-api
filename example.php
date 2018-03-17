<?php include "conf.php"; /* load a local configuration */ ?>
<?php include "modulekit/loader.php"; /* loads all php-includes */ ?>
<?php
// Define database structure
$postsSpec = array(
  'id' => 'posts',
  'fields' => array(
    'id' => array(
      'type' => 'int',
      'read' => true, 'write' => true,
    ),
    'author' => array(
      'type' => 'string',
      'read' => true, 'write' => true,
    ),
    'message' => array(
      'type' => 'string',
      'read' => true, 'write' => true,
    ),
    'commentsCount' => array(
      'type' => 'int',
      'select' => 'select count(*) from comments where comments.postId=posts.id',
    ),
    'comments' => array(
      'type' => 'sub_table',
      'id' => 'comments',
      'parent_field' => 'postId',
      'fields' => array(
        'id' => array(
          'type' => 'int',
          'read' => true, 'write' => true,
        ),
        'postId' => array(
          'type' => 'int',
          'read' => true, 'write' => true,
        ),
        'author' => array(
          'type' => 'string',
          'read' => true, 'write' => true,
        ),
        'message' => array(
          'type' => 'string',
          'read' => true, 'write' => true,
        ),
      ),
    ),
  ),
);

// Initialize database
$dbconf[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
$db = new PDOext($dbconf);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Create database (you should do this only once!)
$create = <<<EOT
drop table if exists comments;
drop table if exists posts;
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
EOT;
$db->query($create);

// Initialize DBApi object
$api = new DBApi($db);
$api->addTable($postsSpec);

// Now let's work with our database
$result = $api->do(array(
  array(
    'action' => 'insert-update',
    'table' => 'posts',
    'data' => array(
      array(
        'author' => 'Alice',
        'message' => 'A first blog post',
      ),
    )
  )
));
print_r(iterator_to_array_deep($result));

// Check if our blog post exists:
$result = $api->do(array(
  array(
    'action' => 'select',
    'table' => 'posts',
  )
));
print_r(iterator_to_array_deep($result));

// We can combine several actions. Also, an 'insert-update' statement can
// insert several objects.
$result = $api->do(array(
  array(
    'action' => 'insert-update',
    'table' => 'posts',
    'data' => array(
      array(
        'author' => 'Bob',
        'message' => 'A 2nd blog post with comments',
        'comments' => array(
          array(
            'author' => 'Alice',
            'message' => 'That\'s a nice post!',
          ),
          array(
            'author' => 'Bob',
            'message' => 'My pleasure :-)',
          ),
        ),
      ),
      array(
        'author' => 'Bob',
        'message' => 'A 3rd blog post',
      ),
    ),
  ),
  array(
    'action' => 'select',
    'table' => 'posts',
    'query' => array(array('author', '=', 'Bob')),
    'fields' => array('id', 'author', 'message', 'commentsCount'),
  ),
));
print_r(iterator_to_array_deep($result));
