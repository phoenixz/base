<?php



/*
 * Expects $data array from email pool fuction
 */
function get_gmail_vcode($data){
  /*
   * Attemps to find google verification codes
   */
  if (strpos($data['from'],'forwarding-noreply@google.com') !== false) {

    $matches_code;
    $matches_from;
    preg_match_all("/: \d{9}/", $data['text'], $matches_code);
    preg_match_all("/[A-Za-z0-9_%+-\.]{3,}@[\.a-z0-9-]{3,}\.[a-z]{2,}/", $data['subject'], $matches_from);

    /*
     * Returns code and email address
     */
    $retval = array('code' => substr($matches_code[0][0], 2),
               'from' => $matches_from[0][0]);
    return $retval;

  }

  return null;

}

?>