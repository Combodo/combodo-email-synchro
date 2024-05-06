<?php
/*
 * @copyright   Copyright (C) 2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Test\UnitTest\CombodoEmailSynchro;

use Combodo\iTop\Test\UnitTest\ItopDataTestCase;
use RawEmailMessage;

class EmailMessageTest extends ItopDataTestCase
{
	public function setUp(): void
	{
		parent::setUp();
		//$this->RequireOnceItopFile('env-production/combodo-email-synchro/classes/emailmessage.class.inc.php');
		require_once(dirname(__FILE__, 4).'/classes/emailmessage.class.inc.php');
	}
	
	/**
	 * @dataProvider GetTestBodyProvider
	 * @param string $sTestBody
	 */
	public function testGetNewPartOutlook(string $sTestBody)
	{
		$oEmailMessage = new \EmailMessage(
			'some-unique-uild',
			'some-unique-message-id',
			'Test message',
			'foo@demo.com',
			'Foo Bar',
			'inbox@demo.com',
			[],
			'',
			$sTestBody,
			'text/html',
			[],
			null,
			[],
			''
		);
		
		$sNewPart = $oEmailMessage->GetNewPartHTML();
		
		echo $sNewPart."\n";
	}
	
	public function GetTestBodyProvider()
	{
		return
		[ 'customer 1' => [
<<<HTML
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="Generator" content="Microsoft Word 15 (filtered medium)">
<!--[if !mso]><style>v\:* {behavior:url(#default#VML);}
o\:* {behavior:url(#default#VML);}
w\:* {behavior:url(#default#VML);}
.shape {behavior:url(#default#VML);}
</style><![endif]--><style><!--
/* Font Definitions */
@font-face
	{font-family:"Cambria Math";
	panose-1:2 4 5 3 5 4 6 3 2 4;}
@font-face
	{font-family:Calibri;
	panose-1:2 15 5 2 2 2 4 3 2 4;}
/* Style Definitions */
p.MsoNormal, li.MsoNormal, div.MsoNormal
	{margin:0cm;
	font-size:11.0pt;
	font-family:"Calibri",sans-serif;}
a:link, span.MsoHyperlink
	{mso-style-priority:99;
	color:blue;
	text-decoration:underline;}
span.object-ref
	{mso-style-name:object-ref;}
span.EmailStyle21
	{mso-style-type:personal-reply;
	font-family:"Calibri",sans-serif;
	color:windowtext;}
.MsoChpDefault
	{mso-style-type:export-only;
	font-size:10.0pt;
	mso-ligatures:none;}
@page WordSection1
	{size:612.0pt 792.0pt;
	margin:70.85pt 70.85pt 70.85pt 70.85pt;}
div.WordSection1
	{page:WordSection1;}
--></style><!--[if gte mso 9]><xml>
<o:shapedefaults v:ext="edit" spidmax="1026" />
</xml><![endif]--><!--[if gte mso 9]><xml>
<o:shapelayout v:ext="edit">
<o:idmap v:ext="edit" data="1" />
</o:shapelayout></xml><![endif]-->
</head>
<body lang="FR" link="blue" vlink="purple" style="word-wrap:break-word">
<div class="WordSection1">
<p class="MsoNormal"><span style="mso-fareast-language:EN-US">Sébastien,<o:p></o:p></span></p>
<p class="MsoNormal"><span style="mso-fareast-language:EN-US">Il n’existe pas de famille de préparation de prédilection puis quand on a plus de travail sur sa famille de préparation, le système oriente vers une nouvelle famille&nbsp;?
<o:p></o:p></span></p>
<p class="MsoNormal"><span style="mso-fareast-language:EN-US">Edouard<o:p></o:p></span></p>
<p class="MsoNormal"><span style="mso-fareast-language:EN-US"><o:p>&nbsp;</o:p></span></p>
<div>
<div style="border:none;border-top:solid #E1E1E1 1.0pt;padding:3.0pt 0cm 0cm 0cm">
<p class="MsoNormal"><b>De&nbsp;:</b> Support Avenir Logistic Conseil &lt;support-avenirlogistic@itop-saas.com&gt;
<br>
<b>Envoyé&nbsp;:</b> mardi 12 septembre 2023 11:13<br>
<b>À&nbsp;:</b> Edouard COCQUEMPOT &lt;edouard.cocquempot@logistisud.re&gt;<br>
<b>Objet&nbsp;:</b> Le ticket R-000141 a été mis à jour.<o:p></o:p></p>
</div>
</div>
<p class="MsoNormal"><o:p>&nbsp;</o:p></p>
<div align="center">
<table class="MsoNormalTable" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="padding:2.5pt 2.5pt 2.5pt 2.5pt">
<p class="MsoNormal"><strong><u><span style="font-family:&quot;Calibri&quot;,sans-serif">SUPPORT AVENIR LOGISTIC CONSEIL</span></u></strong><o:p></o:p></p>
</td>
</tr>
<tr>
<td style="padding:2.5pt 2.5pt 2.5pt 2.5pt">
<p>Le ticket <span class="object-ref"><a href="https://avenir-logistic-conseil.itop-saas.com/pages/exec.php/object/edit/UserRequest/129?exec_module=itop-portal-base&amp;exec_page=index.php&amp;exec_env=production&amp;portal_id=itop-portal">R-000141</a></span> a été mis
 à jour<o:p></o:p></p>
<p>Sébastien WEECXSTEEN a écrit:<o:p></o:p></p>
<p>«<o:p></o:p></p>
<p>Bonjour,<o:p></o:p></p>
<p>&nbsp;<o:p></o:p></p>
<p>Pour affecter des préparateurs à des zones, il faut paramétrer l’équipe.<o:p></o:p></p>
<p>&nbsp;<o:p></o:p></p>
<p>Menu «&nbsp;Equipes&nbsp;»&nbsp;- «&nbsp;Informations autorisées&nbsp;»<o:p></o:p></p>
<p><img border="0" width="1600" height="900" style="width:16.6666in;height:9.375in" id="Image_x0020_1" src="cid:image001.png@01D9E587.50279FA0"><o:p></o:p></p>
<p>&nbsp;<o:p></o:p></p>
<p>Sélectionner la ligne ci-dessous <o:p></o:p></p>
<p><img border="0" width="1600" height="900" style="width:16.6666in;height:9.375in" id="Image_x0020_2" src="cid:image002.png@01D9E587.50279FA0"><o:p></o:p></p>
<p>&nbsp;<o:p></o:p></p>
<p>Associer à l’équipe les zones auxquelles elle doit être affectée<o:p></o:p></p>
<p><img border="0" width="711" height="489" style="width:7.4097in;height:5.0972in" id="Image_x0020_3" src="cid:image003.png@01D9E587.50279FA0"><o:p></o:p></p>
<p>&nbsp;<o:p></o:p></p>
<p>Exemple&nbsp;: il y a une mission en zone de préparation X&nbsp;: <o:p></o:p></p>
<p><img border="0" width="632" height="239" style="width:6.5833in;height:2.493in" id="Image_x0020_4" src="cid:image004.png@01D9E587.50279FA0"><o:p></o:p></p>
<p>&nbsp;<o:p></o:p></p>
<p>&nbsp;<o:p></o:p></p>
<p>L’opérateur se voit attribué la mission lorsqu’il appelle une nouvelle mission&nbsp;:
<o:p></o:p></p>
<p><img border="0" width="659" height="486" style="width:6.868in;height:5.0625in" id="Image_x0020_5" src="cid:image005.png@01D9E587.50279FA0"><o:p></o:p></p>
<p>&nbsp;<o:p></o:p></p>
<p>En revanche, si il tente de saisir un numéro de mission d’une autre zone que celle autorisée, alors message d’erreur&nbsp;:<o:p></o:p></p>
<p><img border="0" width="671" height="488" style="width:6.993in;height:5.0833in" id="Image_x0020_6" src="cid:image006.png@01D9E587.50279FA0"><o:p></o:p></p>
<p>&nbsp;<o:p></o:p></p>
<p class="MsoNormal">» <o:p></o:p></p>
</td>
</tr>
<tr>
<td style="padding:2.5pt 2.5pt 2.5pt 2.5pt">
<div class="MsoNormal" align="center" style="text-align:center">
<hr size="2" width="100%" align="center">
</div>
<p>Titre du ticket:<o:p></o:p></p>
<p>Affectation des utilisateurs Reflex à des zones de préparation en automatique<o:p></o:p></p>
</td>
</tr>
<tr>
<td style="padding:2.5pt 2.5pt 2.5pt 2.5pt">
<div align="center">
<table class="MsoNormalTable" border="0" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="padding:2.5pt 2.5pt 2.5pt 2.5pt">
<p class="MsoNormal"><img border="0" width="596" height="146" style="width:6.2083in;height:1.5208in" id="Image_x0020_8" src="cid:image007.png@01D9E587.50279FA0"><o:p></o:p></p>
</td>
</tr>
</tbody>
</table>
</div>
</td>
</tr>
</tbody>
</table>
</div>
<p class="MsoNormal"><o:p>&nbsp;</o:p></p>
</div>
</body>
</html>
HTML
		]
		,
		'internal 1' => [
<<<HTML
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" xmlns="http://www.w3.org/TR/REC-html40"><head><meta http-equiv=Content-Type content="text/html; charset=iso-8859-1"><meta name=Generator content="Microsoft Word 15 (filtered medium)"><!--[if !mso]><style>v\:* {behavior:url(#default#VML);}
o\:* {behavior:url(#default#VML);}
w\:* {behavior:url(#default#VML);}
.shape {behavior:url(#default#VML);}
</style><![endif]--><style><!--
/* Font Definitions */
@font-face
	{font-family:Wingdings;
	panose-1:5 0 0 0 0 0 0 0 0 0;}
@font-face
	{font-family:"Cambria Math";
	panose-1:2 4 5 3 5 4 6 3 2 4;}
@font-face
	{font-family:Calibri;
	panose-1:2 15 5 2 2 2 4 3 2 4;}
/* Style Definitions */
p.MsoNormal, li.MsoNormal, div.MsoNormal
	{margin:0cm;
	font-size:11.0pt;
	font-family:"Calibri",sans-serif;
	mso-ligatures:standardcontextual;
	mso-fareast-language:EN-US;}
a:link, span.MsoHyperlink
	{mso-style-priority:99;
	color:#0563C1;
	text-decoration:underline;}
p.MsoListParagraph, li.MsoListParagraph, div.MsoListParagraph
	{mso-style-priority:34;
	margin-top:0cm;
	margin-right:0cm;
	margin-bottom:0cm;
	margin-left:36.0pt;
	font-size:11.0pt;
	font-family:"Calibri",sans-serif;
	mso-ligatures:standardcontextual;
	mso-fareast-language:EN-US;}
span.EmailStyle21
	{mso-style-type:personal-compose;
	font-family:"Calibri",sans-serif;
	color:windowtext;}
.MsoChpDefault
	{mso-style-type:export-only;
	font-size:10.0pt;
	mso-ligatures:none;}
@page WordSection1
	{size:612.0pt 792.0pt;
	margin:70.85pt 70.85pt 70.85pt 70.85pt;}
div.WordSection1
	{page:WordSection1;}
/* List Definitions */
@list l0
	{mso-list-id:46689365;
	mso-list-template-ids:-2125677188;}
@list l0:level1
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:36.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l0:level2
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:72.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l0:level3
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:108.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l0:level4
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:144.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l0:level5
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:180.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l0:level6
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:216.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l0:level7
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:252.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l0:level8
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:288.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l0:level9
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:324.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l1
	{mso-list-id:269163450;
	mso-list-type:hybrid;
	mso-list-template-ids:-616811072 67895297 67895299 67895301 67895297 67895299 67895301 67895297 67895299 67895301;}
@list l1:level1
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:none;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	font-family:Symbol;}
@list l1:level2
	{mso-level-number-format:bullet;
	mso-level-text:o;
	mso-level-tab-stop:none;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	font-family:"Courier New";}
@list l1:level3
	{mso-level-number-format:bullet;
	mso-level-text:\F0A7;
	mso-level-tab-stop:none;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	font-family:Wingdings;}
@list l1:level4
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:none;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	font-family:Symbol;}
@list l1:level5
	{mso-level-number-format:bullet;
	mso-level-text:o;
	mso-level-tab-stop:none;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	font-family:"Courier New";}
@list l1:level6
	{mso-level-number-format:bullet;
	mso-level-text:\F0A7;
	mso-level-tab-stop:none;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	font-family:Wingdings;}
@list l1:level7
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:none;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	font-family:Symbol;}
@list l1:level8
	{mso-level-number-format:bullet;
	mso-level-text:o;
	mso-level-tab-stop:none;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	font-family:"Courier New";}
@list l1:level9
	{mso-level-number-format:bullet;
	mso-level-text:\F0A7;
	mso-level-tab-stop:none;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	font-family:Wingdings;}
@list l2
	{mso-list-id:408886737;
	mso-list-template-ids:96086498;}
@list l2:level1
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:36.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l2:level2
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:72.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l2:level3
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:108.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l2:level4
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:144.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l2:level5
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:180.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l2:level6
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:216.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l2:level7
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:252.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l2:level8
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:288.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l2:level9
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:324.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l3
	{mso-list-id:597833665;
	mso-list-template-ids:1699759514;}
@list l3:level1
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:36.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l3:level2
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:72.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l3:level3
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:108.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l3:level4
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:144.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l3:level5
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:180.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l3:level6
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:216.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l3:level7
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:252.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l3:level8
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:288.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l3:level9
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:324.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l4
	{mso-list-id:811681702;
	mso-list-template-ids:-226434262;}
@list l4:level1
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:36.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l4:level2
	{mso-level-number-format:bullet;
	mso-level-text:o;
	mso-level-tab-stop:72.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:"Courier New";
	mso-bidi-font-family:"Times New Roman";}
@list l4:level3
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:108.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l4:level4
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:144.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l4:level5
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:180.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l4:level6
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:216.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l4:level7
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:252.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l4:level8
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:288.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l4:level9
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:324.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l5
	{mso-list-id:1426611181;
	mso-list-template-ids:-1411593026;}
@list l5:level1
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:36.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l5:level2
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:72.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l5:level3
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:108.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l5:level4
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:144.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l5:level5
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:180.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l5:level6
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:216.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l5:level7
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:252.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l5:level8
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:288.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l5:level9
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:324.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l6
	{mso-list-id:1469738661;
	mso-list-template-ids:280780192;}
@list l6:level1
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:36.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l6:level2
	{mso-level-number-format:bullet;
	mso-level-text:o;
	mso-level-tab-stop:72.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:"Courier New";
	mso-bidi-font-family:"Times New Roman";}
@list l6:level3
	{mso-level-number-format:bullet;
	mso-level-text:\F0A7;
	mso-level-tab-stop:108.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Wingdings;}
@list l6:level4
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:144.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l6:level5
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:180.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l6:level6
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:216.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l6:level7
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:252.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l6:level8
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:288.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
@list l6:level9
	{mso-level-number-format:bullet;
	mso-level-text:\F0B7;
	mso-level-tab-stop:324.0pt;
	mso-level-number-position:left;
	text-indent:-18.0pt;
	mso-ansi-font-size:10.0pt;
	font-family:Symbol;}
ol
	{margin-bottom:0cm;}
ul
	{margin-bottom:0cm;}
--></style><!--[if gte mso 9]><xml>
<o:shapedefaults v:ext="edit" spidmax="1026" />
</xml><![endif]--><!--[if gte mso 9]><xml>
<o:shapelayout v:ext="edit">
<o:idmap v:ext="edit" data="1" />
</o:shapelayout></xml><![endif]--></head><body lang=FR link="#0563C1" vlink="#954F72" style='word-wrap:break-word'><div class=WordSection1><p class=MsoNormal>Pour info, ci-dessous l&#8217;état des projets que j&#8217;ai laissé en partant&#8230;<o:p></o:p></p><p class=MsoNormal><o:p>&nbsp;</o:p></p><div><div style='border:none;border-top:solid #E1E1E1 1.0pt;padding:3.0pt 0cm 0cm 0cm'><p class=MsoNormal><b><span style='mso-ligatures:none;mso-fareast-language:FR'>De&nbsp;:</span></b><span style='mso-ligatures:none;mso-fareast-language:FR'> Vincent Dumas <br><b>Envoyé&nbsp;:</b> jeudi 21 septembre 2023 18:24<br><b>À&nbsp;:</b> Alexandre Zana &lt;alexandre.zana@combodo.com&gt;; Erwan Taloc &lt;erwan.taloc@combodo.com&gt;<br><b>Objet&nbsp;:</b> Etat d'avancement de mes projets avant congés<o:p></o:p></span></p></div></div><p class=MsoNormal><o:p>&nbsp;</o:p></p><p class=MsoNormal><b>Ce qu&#8217;il vous faudra gérer en mon absence&nbsp;:</b> <o:p></o:p></p><p class=MsoNormal><o:p>&nbsp;</o:p></p><p class=MsoNormal>Publication <a href="https://support.combodo.com/pages/UI.php?operation=details&amp;class=TargetMilestone&amp;id=1705&amp;c%5bmenu%5d=TargetOverview#ObjectProperties=tab_UIPropertiesTab">Hub 2023 October 3.1 compat</a><o:p></o:p></p><ul style='margin-top:0cm' type=disc><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level1 lfo3'><a href="https://support.combodo.com/pages/UI.php?operation=details&amp;class=TargetMilestone&amp;id=1705&amp;c%5bmenu%5d=TargetOverview#ObjectProperties=tab_ClassTargetMilestoneAttributeworkorders_list">Taches à faire</a>, <b><span style='color:red'>prendre les miennes</span></b>, sauf celles du 3 octobre, que je pourrai faire moi-même<o:p></o:p></li></ul><p class=MsoNormal><o:p>&nbsp;</o:p></p><p class=MsoNormal><o:p>&nbsp;</o:p></p><p class=MsoNormal><i>Pour le reste, je vous mets les infos, mais vous ne devriez pas avoir besoin de vous en occuper&nbsp;:<o:p></o:p></i></p><div class=MsoNormal align=center style='text-align:center'><hr size=2 width="100%" align=center></div><p class=MsoNormal><o:p>&nbsp;</o:p></p><p class=MsoNormal><b><u><span lang=EN-US><a href="https://factory.combodo.com/itop-build/application/index.php?extension=itop-stock-mgmt">Simple Stock Mgmt</a><o:p></o:p></span></u></b></p><ul style='margin-top:0cm' type=disc><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level1 lfo3'>La version 1.1.1-dev est prête à être testé sur une 2.7, 3.0 et 3.1, mais sa release <b><span style='color:red'>peut attendre mon retour</span></b>. <o:p></o:p></li><ul style='margin-top:0cm' type=circle><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level2 lfo3'>Elle n&#8217;est pas indispensable pour pour qu&#8217;une 3.1 fonctionne, ça ne plantera pas, mais on ne pourra pas gérer les stocks avec l&#8217;édition en popup des liens apportée par la 3.1, il faudra le faire à l&#8217;ancienne.<o:p></o:p></li><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level2 lfo3'>Elle apporte pleins de fonctionnalités nouvelles&nbsp;:<o:p></o:p></li><ul style='margin-top:0cm' type=square><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level3 lfo3'><span lang=EN-US>Gestion des stocks sans Ticketing, <o:p></o:p></span></li><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level3 lfo3'>Gestions des stocks depuis un Ticket, <o:p></o:p></li><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level3 lfo3'>Etat du stock mis à jour en temps réel<o:p></o:p></li></ul><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level2 lfo3'>Fix les erreurs de quantité de stock pas à jour, si on modifie un lien lnkStokElementToTicket indépendemment de l&#8217;objet Stock (en 2.7, 3.0 et 3.1)<o:p></o:p></li></ul><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level1 lfo3'>La version 1.2.0-dev n&#8217;est pas compatible avec une 2.7, 3.0.x minimum, sa release est <b><span style='color:red'>encore moins urgente que l&#8217;autre, donc peut attendre mon retour</span></b><o:p></o:p></li><ul style='margin-top:0cm' type=circle><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level2 lfo3'>Elle apporte des icones et des couleurs sur stock en fonction du status (3.0 min)<o:p></o:p></li></ul></ul><p class=MsoNormal><o:p>&nbsp;</o:p></p><p class=MsoNormal><b><u><span lang=EN-US>Communications to the Customers<o:p></o:p></span></u></b></p><ul style='margin-top:0cm' type=disc><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level1 lfo3'><a href="https://factory.combodo.com/itop-build/application/index.php?extension=itop-communications">La version 1.3.3-dev</a> est prête à être testé pour le <a href="https://support.combodo.com/pages/UI.php?operation=details&amp;class=Bug&amp;id=6760&amp;#ObjectProperties=tab_UIPropertiesTab">bug</a> 6760 - <b><span style='color:red'>peut attendre mon retour</span></b><o:p></o:p></li><ul style='margin-top:0cm' type=circle><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level2 lfo3'>SaaS Mérieux est impacté, ça attendrera mon retour, pour créer le tag de l&#8217;extension et mettre à jour la target de SaaS tailored 3.1.0&#8230;<o:p></o:p></li><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level2 lfo3'>Il me semble que le client de Fabrice Vincent peut attendre la Pro 3.1.1, et c&#8217;est ce que je lui ai écrit<o:p></o:p></li><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level2 lfo3'>La version 1.3.3 quand elle sera créée peut néanmoins être fournie à un client en 3.x si besoin / urgence / pression.<o:p></o:p></li><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level2 lfo3'><i>AC semble avoir vu un bug sur l&#8217;extension en batman, mais aucun bug créé encore, à faire en même temps ou pas (on n&#8217;a pas compris son scénario de reproduction, mais ça ne veut pas dire qu&#8217;il n&#8217;y a pas de problème)<o:p></o:p></i></li><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level2 lfo3'><i>J&#8217;ai aussi vu un loup, sans savoir comment j&#8217;étais arrivé là, ni sans savoir reproduire, sur une 3.1.1-dev, lors de la création d&#8217;une communication en étant Admin, à un moment donné, après quelques saisies de champs et navigation entre onglets, le bouton Create ne faisait plus rien, ni le Clore d&#8217;ailleurs, j&#8217;ai dû repartir de zéro une nouvelle création. Ca me rappelle un autre cas similaire en 3.1.0, sur notre iTop et je clonais l&#8217;onglet ainsi bloqué, pour ne pas perdre mes données, et là les boutons récupéraient leur fonctionnement&#8230; Un script dont le chargement a été interrompu&nbsp;?</i><o:p></o:p></li></ul></ul><p class=MsoNormal><o:p>&nbsp;</o:p></p><p class=MsoNormal><b>SaaS Tailored</b> &#8211; Nouveau build - <b><span style='color:red'>peut attendre mon retour</span></b><o:p></o:p></p><ul style='margin-top:0cm' type=disc><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level1 lfo3'><a href="https://factory.combodo.com/itop-build/application/index.php?extension=combodo-modules-config-editor">Module Configurateur</a> -&gt; il y a une nouvelle version 1.0.1 qui arrive avec un bug fixé, et la target est en théorie à jour, pointant sur le nouveau tag 1.0.1<o:p></o:p></li><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level1 lfo3'>Communication cf ci-dessus, la target sera à mettre à jour avec le tag 1.3.3, quand il sera créé.<o:p></o:p></li><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level1 lfo3'>3.1.0-3 incluse&nbsp;: ce sera automatique dès qu&#8217; on rebuild la Target SaaS tailored<o:p></o:p></li></ul><p class=MsoNormal><o:p>&nbsp;</o:p></p><p class=MsoNormal><b>Release Management <o:p></o:p></b></p><ul style='margin-top:0cm' type=disc><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level1 lfo3'><b><span style='color:red'>Mail du Summit&nbsp;: c&#8217;est géré avec Sophie </span></b>on a corrigé leur licence dans le Designer, elle fera un MTP avec eux<o:p></o:p></li><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level1 lfo3'>L&#8217;intégration de leur retours dans l&#8217;extension <b><span style='color:red'>attendra mon retour</span></b><o:p></o:p></li></ul><p class=MsoNormal><o:p>&nbsp;</o:p></p><p class=MsoNormal><b>Professionnal 3.1.1<o:p></o:p></b></p><ul style='margin-top:0cm' type=disc><li class=MsoListParagraph style='margin-left:0cm;mso-list:l1 level1 lfo3'>Il n&#8217;y a plus de Bugs chez moi à tester<o:p></o:p></li></ul><p class=MsoNormal><o:p>&nbsp;</o:p></p><p class=MsoNormal>Bonne chance<o:p></o:p></p><p class=MsoNormal>Vincent<o:p></o:p></p></div></body></html>
HTML
			],
		];
	}
}