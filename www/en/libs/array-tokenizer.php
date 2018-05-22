<?php
/*
 * array-tokenizer library
 *
 * This library contains the ArrayTokenScanner class with a simple front-end function to manage it. It can be used to convert PHP code containing only arrays into real PHP arrays
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Simple front-end function to the ArrayTokenScanner class. Will convert a PHP array string into a real PHP array without having to use evil()
 * @param string $string The PHP code string to be converted into a PHP array
 * @return array The PHP array parsed from the given PHP string
 */
function array_tokenizer($string){
    static $scanner;

    try{
        if(empty($scanner)){
            $scanner = new ArrayTokenScanner();
        }

        if(!is_string($string)){
            throw new bException(tr('array_tokenizer(): Specified variable is not a string but datatype ":type"', array(':type' => gettype($string))), 'invalid');
        }

        $string = preg_replace('/\/\/.+?\n/', '', $string);
        $string = preg_replace('/,\s+/', ', '   , $string);
        $string = preg_replace('/\s+\);/', ');' , $string);

        return $scanner->scan($string);

    }catch(Exception $e){
        throw new bException(tr('array_tokenizer(): Failed'), $e);
    }
}



/**
 * A class used convert string representations or php arrays to an array without using eval()
 * Found on https://stackoverflow.com/questions/12212796/parse-string-as-array-in-php
 * Written by Daan Biesterbos https://stackoverflow.com/users/1441149/daan-biesterbos
 * Copied into base with GPL license assumed.
 * Fixed parse() and parseAtomic() to create assoc string keys and string values without the single or double quotes
 */
class ArrayTokenScanner
{
    /** @var array  */
    protected $arrayKeys = [];

    /**
     * @param string $string   e.g. array('foo' => 123, 'bar' => [0 => 123, 1 => 12345])
     *
     * @return array
     */
    public function scan($string)
    {
        // Remove whitespace and semi colons
        $sanitized = trim($string, " \t\n\r\0\x0B;");
        if(preg_match('/^(\[|array\().*(\]|\))$/', $sanitized)) {
            if($tokens = $this->tokenize("<?php {$sanitized}")) {
                $this->initialize($tokens);
                return $this->parse($tokens);
            }
        }

        // Given array format is invalid
        throw new InvalidArgumentException("Invalid array format.");
    }

    /**
     * @param array $tokens
     */
    protected function initialize(array $tokens)
    {
        $this->arrayKeys = [];
        while($current = current($tokens)) {
            $next = next($tokens);
            if($next[0] === T_DOUBLE_ARROW) {
                $this->arrayKeys[] = $current[1];
            }
        }
    }

    /**
     * @param array $tokens
     * @return array
     */
    protected function parse(array &$tokens)
    {
        $array = [];
        $token = current($tokens);
        if(in_array($token[0], [T_ARRAY, T_BRACKET_OPEN])) {

            // Is array!
            $assoc = false;
            $index = 0;
            $discriminator = ($token[0] === T_ARRAY) ? T_ARRAY_CLOSE : T_BRACKET_CLOSE;
            while($token = $this->until($tokens, $discriminator)) {


                // Skip arrow ( => )
                if(in_array($token[0], [T_DOUBLE_ARROW])) {
                    continue;
                }

                // Reset associative array key
                if($token[0] === T_COMMA_SEPARATOR) {
                    $assoc = false;
                    continue;
                }

                // Look for array keys
                $next = next($tokens);
                prev($tokens);
                if($next[0] === T_DOUBLE_ARROW) {
                    // Is assoc key
                    $assoc = $token[1];
                    if(preg_match('/^-?(0|[1-9][0-9]*)$/', $assoc)) {
                        $index = $assoc = (int) $assoc;
                    }

                    if((substr($assoc, 0, 1) == '"') or (substr($assoc, 0, 1) == "'")){
                        $assoc = substr($assoc, 1, -1);
                    }

                    continue;
                }

                // Parse array contents recursively
                if(in_array($token[0], [T_ARRAY, T_BRACKET_OPEN])) {
                    $array[($assoc !== false) ? $assoc : $this->createKey($index)] = $this->parse($tokens);
                    continue;
                }

                // Parse atomic string
                if(in_array($token[0], [T_STRING, T_NUM_STRING, T_CONSTANT_ENCAPSED_STRING])) {
                    $array[($assoc !== false) ? $assoc : $this->createKey($index)] = $this->parseAtomic($token[1]);
                }

                // Parse atomic number
                if(in_array($token[0], [T_LNUMBER, T_DNUMBER])) {

                    // Check if number is negative
                    $prev = prev($tokens);
                    $value = $token[1];
                    if($prev[0] === T_MINUS) {
                        $value = "-{$value}";
                    }
                    next($tokens);

                    $array[($assoc !== false) ? $assoc : $this->createKey($index)] = $this->parseAtomic($value);
                }

                // Increment index unless a associative key is used. In this case we want too reuse the current value.
                if(!is_string($assoc)) {
                    $index++;
                }
            }

            return $array;
        }
    }

    /**
     * @param array $tokens
     * @param int|string $discriminator
     *
     * @return array|false
     */
    protected function until(array &$tokens, $discriminator)
    {
        $next = next($tokens);
        if($next === false or $next[0] === $discriminator) {
            return false;
        }

        return $next;
    }

    protected function createKey(&$index)
    {
        do {
            if(!in_array($index, $this->arrayKeys, true)) {
                return $index;
            }
        } while(++$index);
    }

    /**
     * @param $string
     * @return array|false
     */
    protected function tokenize($string)
    {
        $tokens = token_get_all($string);
        if(is_array($tokens)) {

            // Filter tokens
            $tokens = array_values(array_filter($tokens, [$this, 'accept']));

            // Normalize token format, make syntax characters look like tokens for consistent parsing
            return $this->normalize($tokens);

        }

        return false;
    }

    /**
     * Method used to accept or deny tokens so that we only have to deal with the allowed tokens
     *
     * @param array|string $value    A token or syntax character
     * @return bool
     */
    protected function accept($value)
    {
        if(is_string($value)) {
            // Allowed syntax characters: comma's and brackets.
            return in_array($value, [',', '[', ']', ')', '-']);
        }
        if(!in_array($value[0], [T_ARRAY, T_CONSTANT_ENCAPSED_STRING, T_DOUBLE_ARROW, T_STRING, T_NUM_STRING, T_LNUMBER, T_DNUMBER])) {
            // Token did not match requirement. The token is not listed in the collection above.
            return false;
        }
        // Token is accepted.
        return true;
    }

    /**
     * Normalize tokens so that each allowed syntax character looks like a token for consistent parsing.
     *
     * @param array $tokens
     *
     * @return array
     */
    protected function normalize(array $tokens)
    {
        // Define some constants for consistency. These characters are not "real" tokens.
        defined('T_MINUS')           ?: define('T_MINUS',           '-');
        defined('T_BRACKET_OPEN')    ?: define('T_BRACKET_OPEN',    '[');
        defined('T_BRACKET_CLOSE')   ?: define('T_BRACKET_CLOSE',   ']');
        defined('T_COMMA_SEPARATOR') ?: define('T_COMMA_SEPARATOR', ',');
        defined('T_ARRAY_CLOSE')     ?: define('T_ARRAY_CLOSE',     ')');

        // Normalize the token array
        return array_map( function($token) {

            // If the token is a syntax character ($token[0] will be string) than use the token (= $token[0]) as value (= $token[1]) as well.
            return [
                0 => $token[0],
                1 => (is_string($token[0])) ? $token[0] : $token[1]
            ];

        }, $tokens);
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    protected function parseAtomic($value)
    {
        // If the parameter type is a string than it will be enclosed with quotes
        if(preg_match('/^["\'].*["\']$/', $value)) {
            // is (already) a string
            return substr($value, 1, -1);
        }

        // Parse integer
        if(preg_match('/^-?(0|[1-9][0-9]*)$/', $value)) {
            return (int) $value;
        }

        // Parse other sorts of numeric values (floats, scientific notation etc)
        if(is_numeric($value)) {
            return  (float) $value;
        }

        // Parse bool
        if(in_array(strtolower($value), ['true', 'false'])) {
            return ($value == 'true') ? true : false;
        }

        // Parse null
        if(strtolower($value) === 'null') {
            return null;
        }

        // Use string for any remaining values.
        // For example, bitsets are not supported. 0x2,1x2 etc
        return $value;
    }
}
?>
