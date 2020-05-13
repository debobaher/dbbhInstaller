# dbbhInstaller
Simple PHP installation scripts.

It get config.php file and ask user for its parameters with `//` comments as the fileds names of the parameters.

Then it execute db.sql replacing the $PREFIX$ in the SQL file with $table_prefix from the config.php file.
