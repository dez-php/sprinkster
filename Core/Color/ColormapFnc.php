<?php

namespace Core\Color;

class ColormapFnc {

	/**
	 * Returns diff between c1 and c2 using the CIEDE2000 algorithm
	 * @return {float}   Difference between c1 and c2
	 */
	public static function ciede2000($c1,$c2) {
		/**
		 * Implemented as in "The CIEDE2000 Color-Difference Formula:
		 * Implementation Notes, Supplementary Test Data, and Mathematical Observations"
		 * by Gaurav Sharma, Wencheng Wu and Edul N. Dalal.
		 */
	
		// Get L,a,b values for color 1
		$L1 = $c1['l'];
		$a1 = $c1['a'];
		$b1 = $c1['b'];
	
		// Get L,a,b values for color 2
		$L2 = $c2['l'];
		$a2 = $c2['a'];
		$b2 = $c2['b'];
	
		// Weight factors
		$kL = 1;
		$kC = 1;
		$kH = 1;
	
		/**
		 * Step 1: Calculate C1p, C2p, h1p, h2p
		 */
		$C1 = sqrt(pow($a1, 2) + pow($b1, 2)); //(2)
		$C2 = sqrt(pow($a2, 2) + pow($b2, 2)); //(2)
	
		$a_C1_C2 = ($C1+$C2)/2.0;             //(3)
	
		$G = 0.5 * (1 - sqrt(pow($a_C1_C2 , 7.0) / (pow($a_C1_C2, 7.0) + pow(25.0, 7.0)))); //(4)
	
		$a1p = (1.0 + $G) * $a1; //(5)
		$a2p = (1.0 + $G) * $a2; //(5)
	
		$C1p = sqrt(pow($a1p, 2) + pow($b1, 2)); //(6)
		$C2p = sqrt(pow($a2p, 2) + pow($b2, 2)); //(6)
	
	
	
		$h1p = self::hp_f($b1, $a1p); //(7)
		$h2p = self::hp_f($b2, $a2p); //(7)
	
		/**
		 * Step 2: Calculate dLp, dCp, dHp
		*/
		$dLp = $L2 - $L1; //(8)
		$dCp = $C2p - $C1p; //(9)
	
	
		$dhp = self::dhp_f($C1,$C2, $h1p, $h2p); //(10)
		$dHp = 2*sqrt($C1p*$C2p)*sin(self::radians($dhp)/2.0); //(11)
	
		/**
		 * Step 3: Calculate CIEDE2000 Color-Difference
		*/
		$a_L = ($L1 + $L2) / 2.0; //(12)
		$a_Cp = ($C1p + $C2p) / 2.0; //(13)
	
		 
	
		$a_hp = self::a_hp_f($C1,$C2,$h1p,$h2p); //(14)
	
		$T = 1-0.17*cos(self::radians($a_hp-30))+0.24*cos(self::radians(2*$a_hp))+0.32*cos(self::radians(3*$a_hp+6))-0.20*cos(self::radians(4*$a_hp-63)); //(15)
		$d_ro = 30 * exp(-(pow(($a_hp-275)/25,2))); //(16)
		$RC = sqrt((pow($a_Cp, 7.0)) / (pow($a_Cp, 7.0) + pow(25.0, 7.0)));//(17)
		$SL = 1 + ((0.015 * pow($a_L - 50, 2)) / sqrt(20 + pow($a_L - 50, 2.0)));//(18)
		$SC = 1 + 0.045 * $a_Cp;//(19)
		$SH = 1 + 0.015 * $a_Cp * $T;//(20)
		$RT = -2 * $RC * sin(self::radians(2 * $d_ro));//(21)
		$dE = sqrt(pow($dLp /($SL * $kL), 2) + pow($dCp /($SC * $kC), 2) + pow($dHp /($SH * $kH), 2) + $RT * ($dCp /($SC * $kC)) * ($dHp / ($SH * $kH))); //(22)
		return $dE;
	}
	
	public static function hp_f($x,$y) //(7)
	{
		if($x== 0 && $y == 0) return 0;
		else{
			$tmphp = self::degrees(atan2($x,$y));
			if($tmphp >= 0) return $tmphp;
			else           return $tmphp + 360;
		}
	}
	public static function dhp_f($C1, $C2, $h1p, $h2p) //(10)
	{
		if($C1*$C2 == 0)               return 0;
		else if(abs($h2p-$h1p) <= 180) return $h2p-$h1p;
		else if(($h2p-$h1p) > 180)     return ($h2p-$h1p)-360;
		else if(($h2p-$h1p) < -180)    return ($h2p-$h1p)+360;
		else                         throw(error);
	}
	public static function a_hp_f($C1, $C2, $h1p, $h2p) { //(14)
		if($C1*$C2 == 0)                                      return $h1p+$h2p;
		else if(abs($h1p-$h2p)<= 180)                         return ($h1p+$h2p)/2.0;
		else if((abs($h1p-$h2p) > 180) && (($h1p+$h2p) < 360))  return ($h1p+$h2p+360)/2.0;
		else if((abs($h1p-$h2p) > 180) && (($h1p+$h2p) >= 360)) return ($h1p+$h2p-360)/2.0;
		else                                                throw( new \Exception( 'd' ));
	}
	
	/**
	 * INTERNAL FUNCTIONS
	 */
	public static function degrees($n) { return $n*(180/pi()); }
	public static function radians($n) { return $n*(pi()/180); }
	
}