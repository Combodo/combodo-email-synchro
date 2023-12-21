<?php
/*
 * @copyright   Copyright (C) 2023 Combodo SARL
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Test\UnitTest\CombodoEmailSynchro;


use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use EmailBackgroundProcess;

class EmailBackgroundProcessTest extends ItopDataTestCase
{
	/**
	 * @dataProvider HumanReadableSizeProvider
	 * @covers       \(EmailBackgroundProcess::HumanReadableSize()
	 */
	public function testHumanReadableSize(int $iSize, string $sExpectedFormattedSize)
	{
		$this->RequireOnceItopFile('env-' . $this->GetTestEnvironment() . '/combodo-email-synchro/classes/autoload.php');
		$sFormattedSize = $this->InvokeNonPublicStaticMethod(EmailBackgroundProcess::class, 'HumanReadableSize', [$iSize]);
		$this->assertEquals($sExpectedFormattedSize, $sFormattedSize);
	}
	
	public function HumanReadableSizeProvider(): array
	{
		return [
			'0 bytes' => [ 0, '0 b' ],
			'546 bytes' => [ 546, '546 b' ],
			'1023 bytes' => [ 1023, '1023 b' ],
			'1.00 Kbytes' => [ 1024, '1.00 Kb' ],
			'1.01 Kbytes' => [ 1035, '1.01 Kb' ],
			'2.50 Kbytes' => [ (int)(1024*2.5), '2.50 Kb' ],
			'1.00 Mbytes' => [ 1024*1024, '1.00 Mb' ],
			'3.373 Mbytes' => [ (int)(1024*1024*3.373), '3.37 Mb' ],
			'45 Mbytes' => [ (int)(1024*1024*45), '45.00 Mb' ],
			'1.57 Gbytes' => [ (int)(1024*1024*1024*1.57), '1.57 Gb' ],
		];
	}
}