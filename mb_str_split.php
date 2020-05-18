<?php
/* for old PHP versions
*/
function mb_str_split($string) {
  $res = array();
  for($i = 0; $i < mb_strlen($string); $i++)
    $res[] = mb_substr($string, $i, 1);

  return $res;
}
?>
