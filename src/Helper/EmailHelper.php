<?php

namespace Combodo\iTop\Extension\EmailSynchro\Helper;


class EmailHelper
{
	public static function HumanReadableSize(int $size)
	{
		$aPrefixes = array('b', 'Kb', 'Mb', 'Gb', 'Tb', 'Pb');
		$index = 0;
		if ($size < 1024) {
			return $size.' b';
		}
		while (($size > 1023) && ($index < count($aPrefixes))) {
			$index++;
			$size = $size / 1024;
		}

		return sprintf("%.2f %s", $size, $aPrefixes[$index]);
	}
}
