<?php
/**
 * @copyright Gael Abadin (elcodedocle) 2014
 * @license MIT Expat http://en.wikipedia.org/wiki/Expat_License
 * @version 0.1
 */
ini_set("display_errors",false);
require_once '../cryptosecureprng/CryptoSecurePRNG.php';
require_once '../dictionary/DictionaryInterface.php';
require_once '../dictionary/Dictionary.php';
require_once '../PasswordGeneratorAbstract.php';
require_once '../PasswordGenerator.php';
use synapp\info\tools\passwordgenerator\PasswordGenerator;

$error = array();
$variations = null;
$allcaps = isset ($_POST['allcaps'])?urldecode($_POST['allcaps']):(isset($_GET['allcaps'])?$_GET['allcaps']:null);
if ($allcaps === 'true') { $variations['allcaps'] = true; }
else if ($allcaps === 'false') { $variations['allcaps'] = false; }
else { $error['allcaps'] = 'invalid value for allcaps'; }
$capitalize = isset ($_POST['capitalize'])?urldecode($_POST['capitalize']):(isset($_GET['capitalize'])?$_GET['capitalize']:null);
if ($capitalize === 'true') { $variations['capitalize'] = true; }
else if ($capitalize === 'false') { $variations['capitalize'] = false; }
else { $error['capitalize'] = 'invalid value for capitalize'; }
$punctuate = isset ($_POST['punctuate'])?urldecode($_POST['punctuate']):(isset($_GET['punctuate'])?$_GET['punctuate']:null);
if ($punctuate === 'true') { $variations['punctuate'] = true; }
else if ($punctuate === 'false') { $variations['punctuate'] = false; }
else { $error['punctuate'] = 'invalid value for punctuate'; }
$addslashes = isset ($_POST['addslashes'])?urldecode($_POST['addslashes']):(isset($_GET['addslashes'])?$_GET['addslashes']:null);
if ($addslashes === 'true') { $variations['addslashes'] = true; }
else if ($addslashes === 'false') { $variations['addslashes'] = false; }
else { $error['addslashes'] = 'invalid value for addslashes'; }

$separators = isset ($_POST['separators'])?urldecode($_POST['separators']):(isset($_GET['separators'])?$_GET['separators']:null);
if ($separators !== null && (!is_string($separators))) { $separators = null; $error['separators'] = 'separators must be a string'; }

$minWordSize = isset ($_POST['minWordSize'])?intval(urldecode($_POST['minWordSize'])):(isset($_GET['minWordSize'])?intval($_GET['minWordSize']):null);
if ($minWordSize !== null && (!is_int($minWordSize) || $minWordSize<0)) {  $minWordSize = null; $error['minWordSize'] = 'minWordSize must be an integer > 0'; }

$level = isset ($_POST['level'])?intval(urldecode($_POST['level'])):(isset($_GET['level'])?intval($_GET['level']):null);
if ($level !== null && (!is_int($level) || $level<0 || $level>3)) { $level = null; $error['level'] = 'level must be an integer between 0 and 3'; }

$passwordGenerator = new PasswordGenerator($dictionary = null, $level, $separators, $minEntropies = null, $useVariations = true, $variations, $minWordSize, $minWordSize, $prng = null);

$password = $passwordGenerator->generatePassword($dictionary = null, $minEntropy = null, $level, $separators, $useVariations = true, $variations);

$entropy = $passwordGenerator->getEntropy($password, $dictionary = null, $variationsCount = null, $lastOrSeparator = true, $separatorsCount = null);

echo json_encode(array('password'=>$password,'entropy'=>$entropy, 'error'=>$error));
