# dbbhInstaller
Simple PHP installation scripts.

It get [config_install.php](config_install.php) file and ask user for its parameters with `//` comments as the fileds names of the parameters.

After `submit` button is pressed, the second phase analise [config_install.php](config_install.php) file again and add the values to the variables that user enter in the form at the first phase. The result is stored to the `config.php` if it doesn't exists.

Then it execute `db.sql` replacing the `$PREFIX$` in the SQL file with `$db_prefix` from the `config.php` file.

## Samples

### [config_install.php](config_install.php) parameters

```
// Database connection parameters
$db_host = 'localhost';      // Database server address
$db_user = 'user';      // Database server username
$db_password = 'password';  // Database server password
$db_database = 'database name';  // Database name
$db_prefix = 'DBBH_';    // Database tables prefix
```

The first line `// Database connection parameters` will convert to the header **Database connection parameters**.

`$db_host = 'localhost';      // Database server address` will convert to the *Database server address* text with input text box following it. The name of the input box will be *db_host*. Other lines will convert to the input text boxes the same way.

These parameters are using for initial database connection. If they are not set in the configuration, the script may fail to run. But **if you don't need them - delete the `db.sql` file**. In this case only `config.php` will be stored and no database manipulations will be made.

### [db.sql](db.sql) file contents

This file should contain only SQL queries.

**One query - one line!**

```
create table $PREFIX$test (id int, name varchar(20))

create table $PREFIX$test2 (id int, name varchar(2))
```

You can use empty line to separate the queries.

The `$PREFIX$` will be replaced with the value of the `$db_prefix` parameter.
