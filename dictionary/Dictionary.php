<?php
namespace synapp\info\tools\passwordgenerator\dictionary;
use synapp\info\tools\passwordgenerator\cryptosecureprng\CryptoSecurePRNG;
use Exception;
use PDO;

/**
 * Class Dictionary
 * 
 * Implements a Dictionary, with methods for reading words from different sources (DAOs)
 * Main methods defined on the DictionaryInterface
 *
 * @copyright Gael Abadin (elcodedocle) 2014
 * @license MIT Expat http://en.wikipedia.org/wiki/Expat_License
 * @version 0.1.0-beta
 * 
 */
class Dictionary implements DictionaryInterface {
    private $words = array();
    private $prng;
    
    // default parameters //
    
    // all inputs
    private $defaultFormat = 'filename';
    private $defaultAdd = true;
    private $defaultMinWordSize = 4;
    private $defaultUnique = false; // be aware unique = true invalidates countWordsOn(password) * log2(countWordsOn(Dictionary)) as a valid measure of the entropy in bits!
    // file input
    private $defaultFilename;
    private $defaultSplitPattern = '/[\s,]+/';
    // pdo dbh input
    private $defaultDbh;
    private $defaultTable = 'words';
    private $defaultColumn = '*'; //will request all columns and pick the first one
    private $minWordSize;
    private $wordCount;
    private $minReadWordsWordSize;

    // end default parameters //
    /**
     * @param null $words
     * @throws \Exception
     * @return bool
     */
    public function getRandomWord($words = null){
        
        if ($words === null) { 
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->words[$this->prng->rand(0,count($this->words)-1)]; 
        }
        if (!array($words)) { throw new Exception('$words must be array'); }
        /** @noinspection PhpUndefinedMethodInspection */
        return $words[$this->prng->rand(0,count($this->words)-1)];
    }

    /**
     * @return int
     */
    public function getWordCount(){
        return $this->wordCount;
    }

    private function minLengthFilter($element){
        return (is_string($element)&&strlen($element)<$this->minWordSize);
    }

    /**
     * @param null $words
     * @param null $minWordSize
     * @param null $minReadWordsWordSize
     * @throws \Exception
     * @return int
     */
    public function countWords($words = null, $minWordSize = null, $minReadWordsWordSize = null){
        if ($words === null) { $words = $this->words; }
        if ($minWordSize === null) { $minWordSize = $this->minWordSize; }
        if ($minReadWordsWordSize === null) { $minReadWordsWordSize = $this->minReadWordsWordSize; }
        if (!array($words)) { throw new Exception('$words must be an array'); }
        if ($minReadWordsWordSize >= $minWordSize) {
            return count($words);
        } else {
            return count(array_filter($words, array($this,'minLengthFilter')));
        }
    }

    /**
     * @param null $words
     * @throws \Exception
     * @return int
     */
    public function setWordCount($words = null){
        if ($words === null) { $words = $this->words; }
        if (!array($words)) { throw new Exception('$words must be an array'); }
        return $this->wordCount = $this->countWords($words);
    }

    /**
     * @param null $count
     * @param null $unique
     * @throws \Exception
     * @return array|bool
     */
    public function getRandomWordsArray($count = null, $unique = null){
        if ($unique === null) { $unique = $this->defaultUnique; }
        // be aware unique = true invalidates 
        // countWordsOn(password) * log2(countWordsOn(Dictionary)) 
        // as a valid measure of the entropy in bits!
        if (!is_int($count)||$count<1||!is_bool($unique)) throw new Exception('invalid input parameters.');
        $randomWords = array();
        for ($i=0;$i<$count;$i++){
            $randomWord=$this->getRandomWord();
            if (!$unique){
                $randomWords[]=$randomWord;
            } else {
                if (!in_array($randomWord, $randomWords)){
                    $randomWords[]=$randomWord;
                } else {
                    if (count($randomWords)<count($this->words)){
                        while (in_array($randomWord, $randomWords)){
                            $randomWord = $this->getRandomWord();
                        }
                        $randomWords[]=$randomWord;
                    } else {
                        throw new Exception('Not enough unique words in dictionary.');
                    }
                }
            }
        }
        return $randomWords;
    }

    /**
     * @param $newWords
     * @param null $add
     * @param null $minWordSize
     * @throws \Exception
     * @return bool
     */
    public function readWordsFromArray($newWords, $add = null, $minWordSize = null){
        if ($add === null) { $add = $this->defaultAdd; }
        if ($minWordSize === null) { $minWordSize = $this->defaultMinWordSize; }
        if (
            !is_bool($add)
        ) { throw new Exception('Invalid input parameters: $add must be boolean'); }
        if (
            !is_int($minWordSize)
        ) { throw new Exception('Invalid input parameters: $minWordSize must be an integer'); }
        if (
            $minWordSize<=0
        ) { throw new Exception('Invalid input parameters: $minWordSize must be > 0'); }
        if (
            !is_array($newWords)
        ) { throw new Exception('Invalid input parameters: $newWords must be an array'); }
        foreach ($newWords as $index=>$word){
            if (strlen($newWords[$index]=trim($newWords[$index]))<$minWordSize) { unset($newWords[$index]); }
        }
        if ($add){
            $this->words = array_unique (array_merge ($this->words, $newWords));
        } else {
            $this->words = array_unique ($newWords);
        }
        $this->setWordCount();
        return true;
    }

    /**
     * reads words from input text file
     *
     * @param $filename string specifying input text file name. Defaults to null, which will use $this->defaultFilename
     * @param null $splitPattern string specifying regex pattern used to split the words on the input text file. Defaults to null, which will use $this->defaultSplitPattern
     * @param mixed $add boolean specifying whether to add/replace the words to/of the current dictionary. Defaults to null, which will use default behaviour defined in $this->defaultAdd
     * @param null $minWordSize an extra param specifying the minimum word length in characters. Defaults to null, in which case $this->defaultMinWordSize will be used instead.
     * @throws \Exception
     * @return bool true on success (on error an Exception will be thrown, though, so this doesn't make much sense...)
     */
    public function readWordsFromFile($filename = null, $splitPattern = null, $add = null, $minWordSize = null){
        if ($filename === null) { $filename = $this->defaultFilename; }
        if ($splitPattern === null) { $splitPattern = $this->defaultSplitPattern; }
        if ($add === null) { $add = $this->defaultAdd; }
        if ($minWordSize === null) { $minWordSize = $this->defaultMinWordSize; }
        if (
            !is_string($filename)
        ) { throw new Exception('Invalid input parameters: $filename is not a string'); }
        if (
            !file_exists($filename)
        ) { throw new Exception('Invalid input parameters: '.$filename.' does not exist'); }
        if (
            !is_string($splitPattern)
        ) { throw new Exception('Invalid input parameters: $splitPattern is not a string'); }
        if (
            !is_bool($add)
        ) { throw new Exception('Invalid input parameters: $add is not boolean'); }
        if (
            !is_int($minWordSize)
        ) { throw new Exception('Invalid input parameters: $minWordSize is not an int'); }
        if (
            $minWordSize<=0
        ) { throw new Exception('Invalid input parameters: $minWordSize is not > 0'); }
        if (
            (($filestring = file_get_contents($filename)) === false)
        ) { throw new Exception('Cannot read input file '.$filename); }
        if (
            ($words = preg_split($splitPattern, $filestring)) === false
        ) { throw new Exception('Invalid input parameters Cannot split words in '.$filename.' using pattern '.$splitPattern); }
        // this is readWordsFromArray repeated for efficiency (passing $words by reference should match the efficiency but we don't want to modify the array there)
        foreach ($words as $index=>$word){
            if (strlen($words[$index]=trim($word))<$minWordSize) { unset($words[$index]); }
        }
        if ($add){
            $this->words = array_unique (array_merge ($this->words, $words));
        } else {
            $this->words = array_unique ($words);
        }
        $this->setWordCount();
        return true;
    }

    /**
     * @param $minWordSize
     * @param bool $recount
     * @throws \Exception
     */
    public function setMinWordSize($minWordSize, $recount = true){
        if (!is_int($minWordSize||$minWordSize<0)) { throw new Exception('$minWordSize must be int>=0'); }
        if ($recount){
            $this->wordCount = $this->setWordCount();
        }
    }

    /**
     * reads words from SQL database table column using a provided PDO database handler
     *
     *
     * @param null $dbh PDO database handler to use. Defaults to null, which means use $this->defaultDbh
     * @param null $table string containing name of the table to select. Defaults to null, which means use $this->defaultTable
     * @param null $column string containing name of the column to select. Defaults to null, which means use $this->defaultColumn
     * @param mixed $add boolean specifying whether to add/replace the words to/of the current dictionary. Defaults to null, which means use default behaviour defined in $this->defaultAdd
     * @param null $minWordSize an extra param specifying the minimum word length in characters. Defaults to null, in which case $this->defaultMinWordSize will be used instead.
     * @throws \Exception
     * @return bool true on success, (on error an exception will be thrown though, so this doesn't make much sense...)
     */
    public function readWordsFromPDO($dbh = null, $table = null, $column = null, $add = null, $minWordSize = null){
        if ($dbh === null) { $dbh = $this->defaultDbh; }
        if ($table === null) { $table = $this->defaultTable; } 
        if ($column === null) { $column = $this->defaultColumn; }
        if ($add === null) { $add = $this->defaultAdd; }
        if ($minWordSize === null) { $minWordSize = $this->defaultMinWordSize; }
        $this->minWordSize = $minWordSize;
        if (
            !($dbh instanceof \PDO) ||
            !is_bool($add)
        ) { throw new Exception('Invalid input parameters.'); }
        $sql = "SELECT :col FROM :table WHERE CHAR_LENGTH(:recol) > :minWordSize";
        $sth = $dbh->prepare($sql);
        $sth->execute(array(':col' => $column, ':table' => $table, ':recol' => $column, ':minWordSize'=>$minWordSize));
        $result = $sth->fetchAll(PDO::FETCH_NUM);

        $words = array();
        foreach ($result as $row){
            if (strlen($word = trim($row[0]))>=$minWordSize){
                $words[]=$word;
            }
        }
        if ($add){
            $this->words = array_unique (array_merge ($this->words, $words));
        } else {
            $this->words = array_unique ($words);
        }
        $this->setWordCount();
        return true;
    }

    /**
     * wrapper for reading words from different sources (array, PDO MySQL DB handler, )
     * 
     * TODO: move reading methods to different DAOs and work with them instead
     * 
     * @param null $input input source (array / string containing an input filename / PDO database handler)
     * @param null $format string explicitly specifying the source: 'array', 'filename', 'pdo_handler'
     * @param array $params array of params for the current source. Unset params will be replaced by defaults. Defaults to array() (empty array)
     * @param null $minWordSize an extra param specifying the minimum word length in characters. Defaults to null, in which case $this->defaultMinWordSize will be used instead. 
     * @return bool true on success (errors will throw exceptions with messages describing them)
     * @throws Exception
     */
    public function readWords($input = null, $format = null, $params = array(), $minWordSize = null){
        
        if ($format === null) { $format = $this->defaultFormat; }        
        if (!is_array($params)) { throw new Exception ('$params must be array'); }
        if (isset($params['add'])){
            if (!is_bool($params['add'])) { throw new Exception ('$params["add"] must be boolean'); }
            $add = $params['add']; 
        } else { $add = $this->defaultAdd; }
        if ($minWordSize === null) { $minWordSize = $this->defaultMinWordSize; }

        switch ($format){
            case 'filename':
                if ($input === null) { $input = $this->defaultFilename; }
                $splitPattern = isset($params['splitPattern'])?$params['splitPattern']:$this->defaultSplitPattern;
                if (!is_string($input)) {
                    throw new Exception ('$input must be a string containing an existing text file name (path/rel. path included)');
                }
                $this->readWordsFromFile($input, $splitPattern, $add, $minWordSize);
                break;
            case 'array':
                if (!is_array($input)){
                    throw new Exception ('$input must be array');
                }
                $this->readWordsFromArray($input, $add, $minWordSize);
                break;
            case 'pdo_handler':
                if ($input === null) { $input = $this->defaultDbh; }
                if (!($input instanceof \PDO)) { throw new Exception ('$input for this format must be a PDO instance'); }
                $table = isset($params['table'])?$params['table']:$this->defaultTable;
                $column = isset($params['column'])?$params['column']:$this->defaultColumn;
                $this->readWordsFromPDO($input, $table, $column, $add, $minWordSize);
                break;
            default: throw new Exception ('invalid $format');
        }
        return true;
    }
    public function __construct($input = null, $minWordSize = null, $format = null, $params = array(), $prng = null){

        $this->defaultFilename = realpath(dirname(__FILE__)).'/top10000.txt';

        if ($minWordSize===null) { $minWordSize = $this->defaultMinWordSize; } else { $this->defaultMinWordSize = $minWordSize; }
        if (!is_int($minWordSize)||$minWordSize<=0) { throw new Exception ('$minWordSize must be a positive (>0) integer'); }
        
        if ($format===null) {$format = $this->defaultFormat; } else { $this->defaultFormat = $format;}
        if (!is_string($format)) { throw new Exception ('$format must be string'); }

        if (!is_array($params)) { throw new Exception ('$params must be array'); }
        if (isset($params['add'])&&is_bool($params['add'])){ $this->defaultAdd = $params['add']; }
        if (isset($params['splitPattern'])) { $this->defaultSplitPattern = $params['splitPattern']; }
        if (isset($params['table'])) { $this->defaultTable=$params['table']; }
        if (isset($params['column'])) { $this->defaultColumn=$params['column']; }
        
        if ($prng === null) { $this->prng = new CryptoSecurePRNG(); } else {
            if (!is_object($prng)||!method_exists($prng,'rand')) {
                // TODO: this is what interfaces and 'implements' are for...
                throw new Exception('$prng must contain a rand method'); 
            }
            $this->prng = $prng; 
        }

        switch ($format){
            case 'filename':
                if ($input === null) { $input = $this->defaultFilename; }
                if (!is_string($input)) {
                    throw new Exception ('$input must be a string containing an existing text file name (path/rel. path included)');
                }
                $this->defaultFilename = $input;
                break;
            case 'array':
                if (!is_array($input)){
                    throw new Exception ('$input must be array');
                }
                $this->readWordsFromArray($input); //instead of $this->readWords($input) to maximize performance
                return;
            case 'pdo_handler':
                if (!($input instanceof \PDO)) { throw new Exception ('$input for this format must be a PDO instance'); }
                $this->defaultDbh = $input;
                break;
            default: throw new Exception ('invalid $format');
        }
        
        $this->readWords();
        
    }
} 