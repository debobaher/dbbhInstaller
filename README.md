# dbbhInstaller
Simple PHP installation scripts.

It get [config_install.php](config_install.php) file and ask user for its parameters with `//` comments as the fileds names of the parameters.

After `submit` button is pressed, the second phase analise [config_install.php](config_install.php) file again and add the values to the variables that user enter in the form at the first phase. The result is stored to the [config.php](config.php) if it doesn't exists.

Then it execute `db.sql` replacing the `$PREFIX$` in the SQL file with `$db_prefix` from the [config.php](config.php) file.
