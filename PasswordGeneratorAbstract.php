<?php
namespace synapp\info\tools\passwordgenerator;

use Exception;

/**
 * Class PasswordGeneratorAbstract
 * 
 * @package synapp\info\tools\passwordgenerator
 * @copyright Gael Abadin (elcodedocle) 2014
 * @license MIT Expat http://en.wikipedia.org/wiki/Expat_License
 * @version 0.1.0-beta
 * 
 */
abstract class PasswordGeneratorAbstract {

    private $dictionary;

    /**
     * @return mixed
     */
    abstract public function getVariationsCount();

    /**
     * @return mixed
     */
    abstract public function getSeparatorsCount();

    /**
     * @param null $dictionary
     * @param null $variationsCount
     * @return mixed
     */
    abstract public function getAlphabetSize($dictionary = null, $variationsCount = null);

    /**
     * @param $password
     * @param bool $lastOrSeparator
     * @return mixed
     */
    abstract public function getSymbolCount($password, $lastOrSeparator = false);

    /**
     * @return mixed
     */
    abstract public function getMaxSymbolCount();

    /**
     * @return mixed
     */
    abstract public function getMinEntropy();

    /**
     * @param null $dictionary
     * @param null $minEntropy
     * @return mixed
     */
    abstract public function generatePassword($dictionary = null, $minEntropy = null);

    /**
     * @return mixed
     */
    public function getDictionary(){
        return $this->dictionary;
    }

    /**
     * @param $dictionary
     * @return bool
     * @throws \Exception
     */
    public function setDictionary($dictionary){
        if (is_object($dictionary) && in_array('synapp\info\tools\passwordgenerator\dictionary\DictionaryInterface', class_implements($dictionary))){
            $this->dictionary = $dictionary;
            return true;
        } else { throw new Exception("Unable to set dictionary."); }
    }

    /**
     * @param $symbolCount
     * @param null $alphabetSize
     * @return int
     * @throws \Exception
     */
    public function calculateEntropy($symbolCount, $alphabetSize = null){
        if ($alphabetSize === null){ $alphabetSize = $this->getAlphabetSize(); }
        if (!is_int($symbolCount) || !is_int($alphabetSize)) { throw new Exception("Unable to calculate entropy."); }
        return $symbolCount * log($alphabetSize,2);
    }

    /**
     * @param string $password
     * @param null|object $dictionary
     * @param null|int $variationsCount
     * @param bool|string $lastOrSeparator
     * @param null|int $separatorsCount
     * @return float
     * @throws \Exception
     */
    public function getEntropy($password = '', $dictionary = null, $variationsCount = null, $lastOrSeparator = true, $separatorsCount = null){
        if ($variationsCount === null) { $variationsCount = $this->getVariationsCount(); }
        if ($separatorsCount === null) { $separatorsCount = ($lastOrSeparator!==false)?$this->getSeparatorsCount():1; }
        else if (!is_int($variationsCount)) { throw new Exception ('$variationsCount must be an int'); }
        if ($dictionary===null) {
            $dictionary = $this->dictionary;
        } else if (!is_object($dictionary) || !in_array('DictionaryInterface', class_implements($dictionary))){
            throw new Exception("Unable to calculate password entropy for provided dictionary.");
        }
        $separatorsEntropy = log($separatorsCount,2);
        return $separatorsEntropy+$this->calculateEntropy($this->getSymbolCount($password, $lastOrSeparator), $this->getAlphabetSize($dictionary, $variationsCount));
    }

    /**
     * @param null $minEntropy
     * @param null $dictionary
     * @param null $variationsCount
     * @return int
     * @throws \Exception
     */
    public function getMinSymbolCount($minEntropy = null, $dictionary = null, $variationsCount = null){
        if ($variationsCount === null) { $variationsCount = $this->getVariationsCount(); }
        else if (!is_int($variationsCount)) { throw new Exception ('$variationsCount must be an int'); }
        if ($minEntropy === null) { $minEntropy = $this->getMinEntropy(); }
        else if (!is_numeric($minEntropy)) { throw new Exception ('$minEntropy must be numeric'); }
        if ($dictionary===null) {
            $dictionary = $this->dictionary;
        } else if (!is_object($dictionary) || !in_array('synapp\info\tools\passwordgenerator\dictionary\DictionaryInterface', class_implements($dictionary))){
            throw new Exception ('$dictionary must be an object implementing DictionaryInterface.');
        }
        $symbolCount = 0;
        $maxSymbolCount = $this->getMaxSymbolCount();
        while ($symbolCount<=$maxSymbolCount && $this->calculateEntropy(++$symbolCount, $this->getAlphabetSize($dictionary, $variationsCount))<$minEntropy){}
        if ($symbolCount>$maxSymbolCount) { throw new Exception ('$symbolcount must be > $maxSymbolCount'); }
        return $symbolCount;
    }
    
}