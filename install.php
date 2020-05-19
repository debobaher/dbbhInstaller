<html>
  <head>
    <link rel="stylesheet" href="install.css">
  </head>
  <body>
<?php
/*
 * dbbhInstaller installation script.
 * Please prepare config_install.php and db.sql files to perform the installation.
 * Installation act with two phases:
 * 1. The config_install.php parameters configuration.
 * 2. Executing the db.sql script to fill the initial structure of the database.
*/

// PHP 7.4 have this function. For earler versions it is rewrited manualy
if(! function_exists('mb_str_split'))
  include 'mb_str_split.php';

/* functions ******************************************************************/

// Function will search for $needle character in the $haystack from the end to beginning
function dbbh_chrrpos($haystack , $needle, $offset = FALSE) {
  if($offset !== FALSE) // If we need the part of the string to analize - truncate it
    $haystack = mb_substr($haystack, 0, $offset);

  // Loop for reverse lookup of the character in the $haystack
  for($i = mb_strlen($haystack) - 1; $i>=0; $i--)
    if(mb_substr($haystack, $i, 1) == $needle)
      return $i; // When we found it - just return it and exit the function

  return FALSE; // We didn't find the $needle
}

// This function will find the first appearance of the one of the characters in the string
function dbbh_findchar($haystack , $needle, $offset = 0) {
  // $needle - the string with characters to find
  // $haystack - the string where to look for them
  // $offset - start character
  $chars = mb_str_split($needle); // Split the string to the chars
  $res = FALSE;

  foreach ($chars as $char) {
    $pos = mb_strpos($haystack, $char, $offset);
    if((($res !== FALSE) && ($pos !== FALSE) && ($pos < $res)) || ($res === FALSE))
      $res = $pos; // We find the position of the character or FALSE. Anyway it is safe to store the result
  }

  return $res;
}

// This function will find the last appearance of the one of the characters in the string
function dbbh_findchar_reverse($haystack , $needle, $offset = FALSE) {
  $chars = mb_str_split($needle); // Split the string to the chars
  $res = FALSE;
  if($offset !== FALSE)
    $haystack = mb_substr($haystack, 0, $offset);

  foreach ($chars as $char) {
    $pos = dbbh_chrrpos($haystack, $char);
    if((($res !== FALSE) && ($pos > $res)) || ($res === FALSE))
      $res = $pos; // We find the position of the character or FALSE. Anyway it is safe to store the result
  }

  return $res;
}

// Fucntion to analize the variable string
// It return the array {'name', 'value'} or FALSE
function analizeVar($line, $com_pos = FALSE) {
  if(($var_pos = mb_strpos($line, '$')) !== FALSE) {//
    if(($com_pos === FALSE) || ($var_pos < $com_pos)) {/// // If the variable name before the comment start - get it as variable
      $var_name = mb_substr($line, $var_pos + 1, dbbh_findchar($line, '= ', $var_pos) - $var_pos - 1);
      $pos = mb_strpos($line, '=', $var_pos); // for use several times
      // Starting to search the variable value block
      $tmp1 = mb_strpos($line, ';', $pos);
      $tmp2 = dbbh_findchar($line, "'\"", $pos);

      if(($com_pos !== FALSE) && ($tmp2 !== FALSE) && ($tmp2 > $com_pos)) // The variable value without quotes because the quote char in the comment
        $var_value = trim(mb_substr($line, $pos + 1, $tmp1 - $pos - 2));
      else {
        $tmp3 = dbbh_findchar_reverse($line, "'\"", $com_pos);
        if($tmp3 == ($tmp2 + 1))
          $var_value = '';
        else
          $var_value = mb_substr($line, $tmp2 + 1, $tmp3 - $tmp2 - 1);
      }

      return array(
        'name' => $var_name,
        'value' => $var_value
      );
    }///
  }//

  return FALSE;
}
/* functions ******************************************************************/

//Check config.php exsitence
if(file_exists('./config.php')) {
  printf('The <tt>config.php</tt> file exist. Please delete it before starting this script. You need <tt>config_install.php</tt> for initial configurtion.');
  exit();
}

// Lets read the config_install.php file to the array of the lines
if (($configR = file('./config_install.php')) === FALSE) {
  printf('The <tt>config_install.php</tt> file can\'t be readen.');
  exit();
}

/// The first phase of the script (if we got no POST data to analize)
if(isset($_POST['dbbhInstaller']))
  $phase1 = FALSE;
else
  $phase1 = TRUE;

if($phase1) {
  // The form header
  ?>
<form action="install.php" method="POST">
  <input type=hidden name="dbbhInstaller" />
  <table>
  <?php
}
  $in_comment = FALSE; // We arn't in the /* */ comment now
  $config = "<?php\n"; // Initialisation of the config file content
  foreach ($configR as $line) { // We will take line by line to analize the contentn of the config_install.php
    // We can get 3 variations of the line content:
    // 1. /* comment or its part.
    // 2. // comment (it will go to the header)
    // 3. $variable and // comment after it

    /* 1 */
    if($in_comment) { // We are inside /* */ comment
      if(mb_strpos($line, '*/') !== FALSE)  // If we found the end of the comment
        $in_comment = FALSE;                // disable the comment area flag

      $config .= $line;
      continue; // Don't analize this line anymore. It is just a comment
    }

    if(($pos = mb_strpos($line, '/*')) !== FALSE) { // We found the comment opening tag
      $in_comment = TRUE;
      if(mb_strpos($line, '*/', $pos + 1) !== FALSE) // Checking the one-line /* */ comment
        $in_comment = FALSE; // We found the end of the comment

      $config .= $line;
      continue; // Go to the next line
    }
    /* 1 */

    /* 2 */
    if(($com_pos = mb_strpos($line, '//')) !== FALSE)
      $comment = mb_substr($line, $com_pos + 2); // Getting the comment
    else
      $comment = '';
    /* 2 */

    /* 3 */
    $var_ar = analizeVar($line, $com_pos); // Array {name, value} or FALSE
    /* 3 */
    if($var_ar !== FALSE) {
      if($comment == '')
        $comment = $var_ar['name'];
        
      if($phase1) // Printig the HTML from
        printf('<tr><td>' . $comment . '</td><td><input type=text name="' . $var_ar['name'] . '" value="' . $var_ar['value'] . '" /></td></tr>');
      else // Storing the content of the config.php file
        $config .= '$' . $var_ar['name'] . ' = "' . addslashes($_POST[$var_ar['name']]) . '"; // ' . $comment . "\n";
    }
    elseif($comment !== '') {
      if($phase1)
        printf('<tr><td colspan=2><h1>' . $comment .'</h1></td></tr>');
      else
        $config .= '// ' . $comment . "\n";
    }
  }

  if($phase1)// The end of the FORM
    printf('<tr><td colspan=2><input type=submit value="Install"></td></tr></table></form>');
  else {
    // Here we got prepared configuration
    // We save it and then use to manipulate the SQL script
    $config .= ' ?>';
    if(file_put_contents('./config.php', $config) !== FALSE) {
      printf('<h1>Saving of the configuration</h1><p>The new configuration file was stored. Its content:<br /><pre>' . htmlspecialchars($config) . '</pre><br />');
      require './config.php'; // Apply the database connection configuration

      // Now we will connect to the SQL server and execute the database initialisation script
      if (($sql = file('./db.sql')) === FALSE)
        printf('The <tt>db.sql</tt> file can\'t be readen. No SQL initialisation is made.');
      else {
        $mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);

        if ($mysqli->connect_errno)
          printf('Can\'t connect to the database server. Here is the error: ' . $mysqli->connect_error . '(' . $mysqli->connect_errno . ')');
        else { // We successfully connect to the database server
          printf('Starting the sql initialisation script queries:<br /><pre>');
          foreach ($sql as $query) { // Now execute sql queries one by one
            if(mb_strlen($query) <= 2)
              continue; // Ignoring empy lines

            $query = mb_ereg_replace('\$PREFIX\$' , $db_prefix , $query); // Replace the pattern
            printf(htmlspecialchars($query) . "\n");
            if (! $mysqli->query($query)) { // In the case of error, the SQL script will stop
              printf('Error in query: ' .  $mysqli->error . ' (' . $mysqli->errno . ')');
              break;
            }
          }
        printf('</pre><br />');
        }
      }
      printf('<h1>Everything done. Please delete the installation files.</h1>');
    }
    else
      exit('Error while saving the config.php file.');
  }

 ?>
</body>
</html>
