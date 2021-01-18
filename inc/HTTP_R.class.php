<?php
  /*
		TinyMTR Web Monitor
		Version 1.5.0
		https://picotory.com
		HTTP_R.class.php
	*/

class HTTP_R
{
   public static function checkFunctions($functions)
   {
      $functions = explode('|', $functions);
      foreach($functions as $function) {
         if ( function_exists($function) ) {
            $array[] = $function;
         }
      }
      return ( (count($functions) == count($array)) ? true : false);
   }

   public static function M2($path)
   {
	  $ctx = stream_context_create(array(
	  		'http' => array(
	  			'timeout' => 5
	  		)
	  	)
	  );
      return file_get_contents($path, 0, $ctx);
   }

   public static function M3($path)
   {
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $path);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      return curl_exec($curl);
      curl_close($curl);
   }

   public static function M4($path)
   {
      return implode('', file($path));
   }

   public static function M0($path)
   {
      if ( self::checkFunctions('file_get_contents') ) {
         return self::M2($path);
      }
      else if ( self::checkFunctions('curl_init|curl_setopt|curl_exec|curl_close') ) {
        return self::M3($path);
      }
      else if ( self::checkFunctions('file|implode') ) {
         return self::M4($path);
      }
      else {
         return 'ERROR: No available request methods are enabled.';
      }
   }
}
?>
