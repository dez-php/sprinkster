<?php

namespace Core\Net;

class IP {

	public static function compare($ip1, $ip2)
	{
		$ip1_bin = inet_pton($ip1);
		$ip2_bin = inet_pton($ip2);

		if(FALSE === $ip1_bin || FALSE === $ip2_bin)
			return FALSE;
		// Known prefix
		$v4mapped_prefix_hex = '00000000000000000000ffff';
		// $v4mapped_prefix_bin = pack("H*", $v4mapped_prefix_hex);

		// Or more readable when using PHP >= 5.4
		$v4mapped_prefix_bin = hex2bin($v4mapped_prefix_hex); 

		// Check prefix
		if( substr($ip1_bin, 0, strlen($v4mapped_prefix_bin)) == $v4mapped_prefix_bin) {
		// Strip prefix
			$ip1_bin = substr($ip1_bin, strlen($v4mapped_prefix_bin));
		}

		// Check prefix
		if( substr($ip2_bin, 0, strlen($v4mapped_prefix_bin)) == $v4mapped_prefix_bin) {
		// Strip prefix
			$ip2_bin = substr($ip2_bin, strlen($v4mapped_prefix_bin));
		}

		// Convert back to printable address in canonical form
		$ip1 = inet_ntop($ip1_bin);
		$ip2 = inet_ntop($ip2_bin);

		return $ip1 === $ip2;
	}

	public static function expand_ip_address($addr_str)
	{
		$addr_bin = @inet_pton($addr_str);
		if ($addr_bin === FALSE) 
			return FALSE;

		$addr_hex = bin2hex($addr_bin);

		/* See if this is an IPv4-Compatible IPv6 address (deprecated) or an
		IPv4-Mapped IPv6 Address (used when IPv4 connections are mapped to
		an IPv6 sockets and convert it to a normal IPv4 address */
		if (strlen($addr_bin) == 16 &&  substr($addr_hex, 0, 20) == str_repeat('0', 20))
			if (substr($addr_hex, 20, 4) == '0000' ||  substr($addr_hex, 20, 4) == 'ffff')
				$addr_bin = substr($addr_hex, 12);

		/* Then differentiate between IPv4 and IPv6 */
		if (strlen($addr_bin) == 4)
		{
			/* IPv4: print each byte as 3 digits and add dots between them */
			$ipv4_bytes = str_split($addr_bin);
			$ipv4_ints = array_map('ord', $ipv4_bytes);
			return vsprintf('%03d.%03d.%03d.%03d', $ipv4_ints);
		}

		/* IPv6: print as hex and add colons between each group of 4 hex digits */
		return implode(':', str_split($addr_hex, 4));
	}

}