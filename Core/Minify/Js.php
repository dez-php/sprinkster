<?php

namespace Core\Minify;

class Js extends \Core\Minify\AbstractMinify {
	
	/**
	 * Extract comments & strings from source code (and replace them with a
	 * placeholder)
	 * This fancy parsing is neccessary because comments can contain string
	 * demarcators and vice versa, and both can
	 * contain content that is very similar to the rest of the code.
	 *
	 * @param string $content
	 *        	The file/content to extract comments & strings for.
	 * @return array An array containing the (manipulated) content, the strings
	 *         & the comments.
	 */
	protected function extract($content) {
		// load the content
		$content = $this->load ( $content );
		
		// initialize array that will contain all strings found in the code
		$strings = array ();
		$comments = array ();
		
		// loop all characters
		for($i = 0; $i < strlen ( $content ); $i ++) {
			$character = $content [$i];
			
			switch ($content [$i]) {
				// string demarcation: ' or "
				case '\'' :
				case '"' :
					$stringOpener = $character;
					$string = $character;
					
					// process through content until we find the end of the
					// string
					for($j = $i + 1; $j < strlen ( $content ); $j ++) {
						$character = $content [$j];
						$previousCharacter = isset ( $content [$j - 1] ) ? $content [$j - 1] : '';
						
						/*
						 * Find end of string: - string started with double
						 * quotes ends in double quotes, likewise for single
						 * quotes. - unterminated string ends at newline (bad
						 * code), unless newline is escaped (though nobody knows
						 * this.)
						 */
						if (($stringOpener == $character && $previousCharacter != '\\') || (in_array ( $character, array (
								"\r",
								"\n" 
						) ) && $previousCharacter != '\\')) {
							// save string
							$replacement = '[MINIFY-STRING-' . count ( $strings ) . ']';
							$strings [$replacement] = substr ( $content, $i, $j - $i + 1 );
							
							// replace string by stub
							$content = substr_replace ( $content, $replacement, $i, $j - $i + 1 );
							
							// reset pointer to the end of this string
							$i += strlen ( $replacement );
							
							break;
						}
					}
					break;
				
				// comment demarcation: // or /*
				case '/' :
					$commentOpener = $character . (isset ( $content [$i + 1] ) ? $content [$i + 1] : '');
					
					/*
					 * Both comment opening tags are 2 characters, so grab the
					 * next character and verify we're really opening a comment
					 * here.
					 */
					if (in_array ( $commentOpener, array (
							'//',
							'/*' 
					) )) {
						// process through content until we find the end of the
						// comment
						for($j = $i + 1; $j < strlen ( $content ); $j ++) {
							$character = $content [$j];
							$previousCharacter = isset ( $content [$j - 1] ) ? $content [$j - 1] : '';
							
							/*
							 * Find end of comment: - // single line comments
							 * end at newline. - /* multiline comments and at
							 * their respective closing tag, which I can't use
							 * here or it'd end this very comment.
							 */
							if (($commentOpener == '//' && in_array ( $character, array (
									"\r",
									"\n" 
							) )) || ($commentOpener == '/*' && $previousCharacter . $character == '*/')) {
								// save comment
								$replacement = '[MINIFY-COMMENT-' . count ( $comments ) . ']';
								$comments [$replacement] = substr ( $content, $i, $j - $i + 1 );
								
								// replace comment by stub
								$content = substr_replace ( $content, $replacement, $i, $j - $i + 1 );
								
								// reset pointer to the end of this string
								$i += strlen ( $replacement );
								
								break;
							}
						}
					}
					break;
			}
		}
		
		return array (
				$content,
				$strings,
				$comments 
		);
	}
	
	/**
	 * Minify the data.
	 * Perform JS optimizations.
	 *
	 * @param string[optional] $path
	 *        	The path the data should be written to.
	 * @param bool[optional] $stripComments
	 *        	Should comments be stripped?
	 * @param bool[optional] $stripWhitespace
	 *        	Should whitespace be stripped?
	 * @return string The minified data.
	 */
	public function minify($path = false, $stripComments = true, $stripWhitespace = true) {
		$content = '';
		
		// loop files
		foreach ( $this->data as $source => $js ) {
			// combine js
			$content .= $js;
		}
		
		// extract comments & strings from content
		list ( $content, $strings, $comments ) = $this->extract ( $content );
		
		// minify
		if ($stripComments)
			$content = $this->stripComments ( $content, false, $comments );
			
			// strip whitespace
		if ($stripWhitespace)
			$content = $this->stripWhitespace ( $content, false, $strings, $comments );
			
			// reset strings
		$content = str_replace ( array_keys ( $strings ), array_values ( $strings ), $content );
		
		// save to path
		if ($path !== false)
			$this->save ( $content, $path );
		
		return $content;
	}
	
	/**
	 * Strip comments from source code.
	 *
	 * @param string $content
	 *        	The file/content to strip the comments for.
	 * @param string[optional] $path
	 *        	The path the data should be written to.
	 * @return string
	 */
	public function stripComments($content, $path = false) {
		// little "hack" for internal use
		$comments = @func_get_arg ( 2 );
		
		// load the content
		$content = $this->load ( $content );
		
		// content has not been parsed before, do so now
		if ($comments === false) {
			// extract strings & comments
			list ( $content, $strings, $comments ) = $this->extract ( $content );
			
			// reset strings
			$content = str_replace ( array_keys ( $strings ), array_values ( $strings ), $content );
		}
		
		// strip comments
		if (count ( $comments ) > 0) {
			$content = str_replace ( array_keys ( $comments ), array_fill ( 0, count ( $comments ), '' ), $content );
		}
		
		// save to path (not for internal use!)
		if (@func_get_arg ( 2 ) === false)
			$this->save ( $content, $path );
		
		return $content;
	}
	
	/**
	 * Strip whitespace.
	 *
	 * @param string $content
	 *        	The file/content to strip the whitespace for.
	 * @param string[optional] $path
	 *        	The path the data should be written to.
	 * @return string
	 */
	public function stripWhitespace($content, $path = false) {
		// little "hack" for internal use
		$strings = @func_get_arg ( 2 );
		$comments = @func_get_arg ( 3 );
		
		// load the content
		$content = $this->load ( $content );
		
		// content has not been parsed before, do so now
		if ($strings === false || $comments === false) {
			// extract strings & comments
			list ( $content, $strings, $comments ) = $this->extract ( $content );
		}
		
		// newlines > linefeed
		$content = str_replace ( array (
				"\r\n",
				"\r",
				"\n" 
		), "\n", $content );
		
		// empty lines > collapse
		$content = preg_replace ( '/^[ \t]*|[ \t]*$/m', '', $content );
		$content = preg_replace ( '/\n+/m', "\n", $content );
		$content = trim ( $content );
		
		// redundant whitespace > remove
		$content = preg_replace ( '/(?<=[{}\[\]\(\)=><&\|;:,\?!\+-])[ \t]*|[ \t]*(?=[{}\[\]\(\)=><&\|;:,\?!\+-])/i', '', $content );
		$content = preg_replace ( '/[ \t]+/', ' ', $content );
		
		// redundant semicolons (followed by another semicolon or closing curly
		// bracket) > remove
		$content = preg_replace ( '/;\s*(?=[;}])/s', '', $content );
		
		/*
		 * @todo: we could remove all line feeds, but then we have to be certain
		 * that all statements are properly terminated with a semi-colon. So
		 * we'd first have to parse the statements to see which require a
		 * semi-colon, add it if it's not present, and then remove the line
		 * feeds. The semi-colon just before a closing curly bracket can then
		 * also be omitted.
		 */
		
		// reset data if this function has not been called upon through internal
		// methods
		if (@func_get_arg ( 2 ) === false || @func_get_arg ( 3 ) === false) {
			// reset strings & comments
			$content = str_replace ( array_keys ( $strings ), array_values ( $strings ), $content );
			$content = str_replace ( array_keys ( $comments ), array_values ( $comments ), $content );
			
			// save to path
			if ($path !== false)
				$this->save ( $content, $path );
		}
		
		return $content;
	}
}