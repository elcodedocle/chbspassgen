<?php

namespace synapp\info\tools\passwordgenerator;

use Exception;
use synapp\info\tools\passwordgenerator\cryptosecureprng\CryptoSecurePRNG;
use synapp\info\tools\passwordgenerator\dictionary\Dictionary;

/**
 * Class PasswordGenerator
 *
 * Generates an easy to remember password, difficult to guess or bruteforce (with lots of entropy).
 *
 * @package synapp\info\tools\passwordgenerator
 * @copyright Gael Abadin (elcodedocle) 2014
 * @license MIT Expat http://en.wikipedia.org/wiki/Expat_License
 * @version 0.1.0-beta
 *
 */
class PasswordGenerator extends PasswordGeneratorAbstract
{

    const SEPARATORS_MUST_BE_A_STRING = '$separators must be a string';
    private $defaultMinEntropies = array(64, 80, 112, 128); //min entropy (in bits) per level as an array of integers in ascending order
    private $defaultLevel = 2;
    private $defaultDictionaryFilename = null; // 2^13 (aprox)
    private $defaultSeparator = ' ';
    private $defaultMinWordSize = 4;
    private $maxSymbols = 20;

    private $minEntropies;
    private $level;
    private $separators;
    private $separator;

    private $prng;
    private $defaultMinReadWordsWordSize = 4;
    private $lastPasswordSymbolCount = 0;
    /**
     * @var int
     */
    private $minWordSize;
    /**
     * @var int
     */
    private $minReadWordsWordSize;

    /**
     * @param null $separators
     * @return string
     * @throws Exception
     */
    public function setSeparator($separators = null)
    {
        if ($separators === null) {
            $separators = $this->separators;
        }
        $separatorsLength = strlen($separators);
        $this->separator = $separatorsLength > 0 ? $separators[$this->prng->rand(0, $separatorsLength - 1)] : $this->defaultSeparator; //random $separator actually adds log2($separatorsLength) bits of entropy
        return $this->separator;
    }

    /**
     * @return mixed
     */
    public function getSeparator()
    {
        return $this->separator;
    }


    /**
     * @param $separators
     * @return bool
     * @throws Exception
     */
    public function setSeparators($separators)
    {
        if ($separators === null || $separators === '') {
            $this->separators = $this->defaultSeparator;
            return true;
        }
        if (is_string($separators)) {
            $this->separators = implode('', array_unique(preg_split('/(?<!^)(?!$)/u', $separators)));
            return true;
        } else {
            throw new Exception (self::SEPARATORS_MUST_BE_A_STRING);
        }
    }

    /**
     * @return mixed
     */
    public function getSeparators()
    {
        return $this->separators;
    }

    private $useVariations = true;
    private $capitalize = true;
    private $punctuate = true; //adds comma/dot, exclamation mark, question mark
    private $allcaps = true;
    private $addslashes = false;

    /**
     * @param $maxSymbols
     * @return bool
     * @throws Exception
     */
    public function setMaxSymbols($maxSymbols)
    {
        if (is_int($maxSymbols)) {
            $this->maxSymbols = $maxSymbols;
            return true;
        } else {
            throw new Exception ('$maxSymbols must be an int');
        }
    }

    /**
     * @return int
     */
    public function getMaxSymbols()
    {
        return $this->maxSymbols;
    }


    /**
     * @param $addslashes
     * @return bool
     * @throws Exception
     */
    public function setAddslashes($addslashes)
    {
        if (is_bool($addslashes)) {
            $this->addslashes = $addslashes;
            return true;
        } else {
            throw new Exception ('$addslashes must be boolean');
        }
    }

    /**
     * @param $allcaps
     * @return bool
     * @throws Exception
     */
    public function setAllcaps($allcaps)
    {
        if (is_bool($allcaps)) {
            $this->allcaps = $allcaps;
            return true;
        } else {
            throw new Exception ('$allcaps must be boolean');
        }
    }

    /**
     * @param $capitalize
     * @return bool
     * @throws Exception
     */
    public function setCapitalize($capitalize)
    {
        if (is_bool($capitalize)) {
            $this->capitalize = $capitalize;
            return true;
        } else {
            throw new Exception ('$capitalize must be boolean');
        }
    }

    /**
     * @param $defaultDictionaryFilename
     * @return bool
     * @throws Exception
     */
    public function setDefaultDictionaryFilename($defaultDictionaryFilename)
    {
        if (is_string($defaultDictionaryFilename)) {
            $this->defaultDictionaryFilename = $defaultDictionaryFilename;
            return true;
        } else {
            throw new Exception ('');
        }
    }

    /**
     * @param $defaultLevel
     * @return bool
     * @throws Exception
     */
    public function setDefaultLevel($defaultLevel)
    {
        if (is_int($defaultLevel)) {
            $this->defaultLevel = $defaultLevel;
            return true;
        } else {
            throw new Exception ('$defaultLevel must be an int');
        }
    }

    /**
     * @param $level
     * @return bool
     * @throws Exception
     */
    public function setLevel($level)
    {
        if (is_int($level)) {
            $this->level = $level;
            return true;
        } else {
            throw new Exception ('$level must be int');
        }
    }

    /**
     * @param $punctuate
     * @return bool
     * @throws Exception
     */
    public function setPunctuate($punctuate)
    {
        if (is_bool($punctuate)) {
            $this->punctuate = $punctuate;
            return true;
        } else {
            throw new Exception ('$punctuate must be boolean');
        }
    }

    /**
     * @return boolean
     */
    public function getAddslashes()
    {
        return $this->addslashes;
    }

    /**
     * @return boolean
     */
    public function getAllcaps()
    {
        return $this->allcaps;
    }

    /**
     * @return boolean
     */
    public function getCapitalize()
    {
        return $this->capitalize;
    }

    /**
     * @return string|null
     */
    public function getDefaultDictionaryFilename()
    {
        return $this->defaultDictionaryFilename;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return array
     */
    public function getMinEntropies()
    {
        return $this->minEntropies;
    }

    /**
     * @return boolean
     */
    public function getPunctuate()
    {
        return $this->punctuate;
    }

    /**
     * @return boolean
     */
    public function getUseVariations()
    {
        return $this->useVariations;
    }

    /**
     * @param $useVariations
     * @return bool
     * @throws Exception
     */
    public function setUseVariations($useVariations)
    {
        if (is_bool($useVariations)) {
            $this->useVariations = $useVariations;
            return true;
        } else {
            throw new Exception ('$useVariations must be boolean');
        }
    }

    /**
     * @return array
     */
    public function getDefaultMinEntropies()
    {
        return $this->defaultMinEntropies;
    }

    /**
     * @return int
     */
    public function getDefaultLevel()
    {
        return $this->defaultLevel;
    }

    /**
     * @param null $level
     * @param null $minEntropies
     * @return mixed
     * @throws Exception
     */
    public function getMinEntropyForLevel($level = null, $minEntropies = null)
    {
        if ($level === null) {
            $level = $this->level;
        } elseif (!is_int($level)) {
            throw new Exception ('$level must be int');
        }
        if ($minEntropies === null) {
            $minEntropies = $this->minEntropies;
        } elseif (!is_array($minEntropies)) {
            throw new Exception ('$minEntropies must be array');
        }
        return ($level < 0) ? $minEntropies[0] :
            (($level < count($minEntropies)) ? $minEntropies[$level] :
                $minEntropies[count($minEntropies)]);
    }

    /**
     * @param $minEntropies
     * @param bool $default
     * @return bool
     * @throws Exception
     */
    public function setMinEntropies($minEntropies, $default = false)
    {
        if (!is_array($minEntropies) || !isset($minEntropies[0]) || !is_int($minEntropies[0]) || $minEntropies[0] <= 0) {
            throw new Exception ('$minEntropies must be an ascending ordered array of ints > 0');
        }
        for ($i = 1; $i < count($minEntropies); $i++) {
            if (!isset($minEntropies[$i]) || !is_int($minEntropies[$i]) || $minEntropies[$i] <= $minEntropies[$i - 1]) {
                throw new Exception ('$minEntropies must be an ascending ordered array of ints > 0');
            }
        }
        if ($default) {
            $this->defaultMinEntropies = $minEntropies;
        } else {
            $this->minEntropies = $minEntropies;
        }
        return true;
    }

    /**
     * @param null $variations
     * @return int
     * @throws Exception
     */
    public function getVariationsCount($variations = null)
    {

        $variations = $this->filterVariations($variations);

        $variationsCount = 0;

        foreach ($variations as $variation) {
            if ($variation === true) {
                $variationsCount++;
            }
        }
        if (isset($variations['punctuate']) && $variations['punctuate'] === true) {
            $variationsCount += 2;
        }

        return $variationsCount;

    }

    /**
     * @param null $separators
     * @return int
     * @throws Exception
     */
    public function getSeparatorsCount($separators = null)
    {
        if ($separators === null) {
            $separators = $this->separators;
        }
        if (is_array($separators)) {
            return count($separators);
        }
        if (is_string($separators)) {
            return strlen($separators);
        }
        throw new Exception ('$separators must be array or string');
    }

    /**
     * @param null $dictionary
     * @param null $variationsCount
     * @return float|int
     * @throws Exception
     */
    public function getAlphabetSize($dictionary = null, $variationsCount = null)
    {
        if ($dictionary === null) {
            $dictionary = $this->getDictionary();
        } elseif (!is_object($dictionary) || !in_array('synapp\info\tools\passwordgenerator\dictionary\DictionaryInterface', class_implements($dictionary))) {
            throw new Exception ('$dictionary must be an object implementing DictionaryInterface');
        }
        if ($variationsCount === null) {
            $variationsCount = $this->getVariationsCount();
        } elseif (!is_int($variationsCount)) {
            throw new Exception ('$variationsCount must be an int');
        }

        if (($dictionarySize = $dictionary->getWordCount()) <= 0) {
            throw new Exception ('$dictionarySize must be int > 0');
        }

        return $dictionarySize * pow(2, $variationsCount);

    }

    /**
     * @param $password
     * @param null $lastOrSeparator
     * @return int
     */
    public function getSymbolCount($password, $lastOrSeparator = null)
    {
        if ($lastOrSeparator === null) {
            $lastOrSeparator = $this->separator;
        }
        if ($lastOrSeparator === true) {
            return $this->lastPasswordSymbolCount;
        }
        return count(explode($lastOrSeparator, $password));
    }

    /**
     * @return int
     */
    public function getMaxSymbolCount()
    {
        return $this->getMaxSymbols();
    }

    /**
     * @param null $level
     * @return mixed
     * @throws Exception
     */
    public function getMinEntropy($level = null)
    {
        if ($level === null) {
            $level = $this->level;
        } elseif (!is_int($level)) {
            throw new Exception ('$level must be an int');
        }
        return $this->getMinEntropyForLevel($level);
    }

    /**
     * @param $variations
     * @return array
     * @throws Exception
     */
    public function filterVariations($variations)
    {
        if (!isset($variations)) {

            $variations = array();
            $variations['capitalize'] = $this->capitalize;
            $variations['punctuate'] = $this->punctuate;
            $variations['allcaps'] = $this->allcaps;
            $variations['addslashes'] = $this->addslashes;

        } elseif (!is_array($variations)) {
            throw new Exception ('$variations must be an array');
        } else {

            $keys = array_keys($variations);
            $validKeys = array('capitalize', 'punctuate', 'allcaps', 'addslashes');
            foreach ($keys as $variation) {
                if (!in_array($variation, $validKeys)) {
                    throw new Exception ('$variations keys must be one of the hardcoded keys: capitalize, punctuate, allcaps or addslashes');
                }
            }

            if (!isset($variations['capitalize'])) {
                $variations['capitalize'] = $this->capitalize;
            } elseif (!is_bool($variations['capitalize'])) {
                throw new Exception ('$variations["capitalize"] must be boolean');
            }

            if (!isset($variations['punctuate'])) {
                $variations['punctuate'] = $this->punctuate;
            } elseif (!is_bool($variations['punctuate'])) {
                throw new Exception ('$variations["punctuate"] must be boolean');
            }

            if (!isset($variations['allcaps'])) {
                $variations['allcaps'] = $this->allcaps;
            } elseif (!is_bool($variations['allcaps'])) {
                throw new Exception ('$variations["allcaps"] must be boolean');
            }

            if (!isset($variations['addslashes'])) {
                $variations['addslashes'] = $this->addslashes;
            } elseif (!is_bool($variations['addslashes'])) {
                throw new Exception ('$variations["addslashes"] must be boolean');
            }

        }
        return $variations;
    }

    /**
     * @param null $variations
     * @return bool
     * @throws Exception
     */
    public function setVariations($variations = null)
    {
        $variations = $this->filterVariations($variations);
        $this->capitalize = $variations['capitalize'];
        $this->punctuate = $variations['punctuate'];
        $this->allcaps = $variations['allcaps'];
        $this->addslashes = $variations['addslashes'];
        return true;
    }

    /**
     * @return array
     */
    public function getVariations()
    {
        return array(
            'capitalize' => $this->capitalize,
            'punctuate' => $this->punctuate,
            'allcaps' => $this->allcaps,
            'addslashes' => $this->addslashes
        );
    }

    /**
     * @param $word
     * @param $index
     * @param $variations
     * @param $last
     * @param $appliedVariation
     * @return string
     */
    public function applyVariation($word, $index, $variations, $last, &$appliedVariation)
    {
        $count = 0;
        if (isset($variations['capitalize']) && $variations['capitalize'] === true) {
            if ($count < $index) {
                $count++;
            } else {
                // capitalize is not exactly capitalize, but invert case of the word's first letter
                $word[0] = (strtolower($word[0]) === $word[0]) ? strtoupper($word[0]) : strtolower($word[0]);
                $appliedVariation = 'capitalize';
                return $word;
            }
        }
        if (isset($variations['allcaps']) && $variations['allcaps'] === true) {
            if ($count < $index) {
                $count++;
            } else {
                $appliedVariation = 'allcaps';
                return strtoupper($word);
            }
        }
        if (isset($variations['punctuate']) && $variations['punctuate'] === true) {
            $appliedVariation = 'punctuate';
            if ($count < $index) {
                $count++;
            } else {
                return $word . '!';
            }
            if ($count < $index) {
                $count++;
            } else {
                return $word . '?';
            }
            if ($count < $index) {
                $count++;
            } else {
                return $word . ($last ? '.' : ',');
            }
            $appliedVariation = '';
        }
        if (isset($variations['addslashes']) && $variations['addslashes'] === true) {
            if (($count < $index) || $last) {
                return $word;
            } else {
                $appliedVariation = 'addslashes';
                return $word . '/';
            }
        }
        return $word;
    }

    /**
     * generates a dictionary based password matching the given parameters
     *
     * @param object|null $dictionary the dictionary where words are taken from (must implement DictionaryInterface)
     * @param mixed $minEntropy the minimum entropy of the generated password (may be overrided by $level)
     * @param int|bool $level strength level of the generated password as the index of the entropies array property
     * @param string|null $separators a string of zero or more characters to be randomly used as separators between words, null takes the value set on the property
     * @param bool|null $useVariations a boolean telling whether to use or ignore active variations
     * @param array|null $variations an array of booleans with variations as keys, or null for using default set in property
     * @return string a string containing the password
     * @throws Exception
     */
    public function generatePassword($dictionary = null, $minEntropy = null, $level = null, $separators = null, $useVariations = null, $variations = null)
    {

        if ($dictionary === null) {
            $dictionary = $this->getDictionary();
        } elseif (!is_object($dictionary) || !in_array('DictionaryInterface', class_implements($dictionary))) {
            throw new Exception ('$dictionary must be an object implementing DictionaryInterface');
        }

        if ($level === null) {
            $level = $this->level;
        } elseif (!is_int($level)) {
            throw new Exception ('Level must be an int');
        } else {
            $minEntropy = $this->getMinEntropy($level);
        } // explicit level overrides explicit $minEntropy

        if ($separators === null) {
            $separators = $this->separators;
        } elseif (!is_string($separators)) {
            throw new Exception (self::SEPARATORS_MUST_BE_A_STRING);
        }

        $separator = $this->setSeparator($separators);

        if ($minEntropy === null) {
            $minEntropy = $this->getMinEntropy($level);
        } elseif (!is_int($minEntropy)) {
            throw new Exception ('$minEntropy must be an int.');
        }

        if ($useVariations === null) {
            $useVariations = $this->useVariations;
        } elseif (!is_bool($useVariations)) {
            throw new Exception ('$useVariations must be a boolean');
        }

        $variations = $this->filterVariations($variations);

        $password = '';
        $variationsCount = $this->getVariationsCount($variations);
        $minWordCount = $this->getMinSymbolCount($minEntropy, $dictionary, $variationsCount);

        for ($wordCount = 1; $wordCount <= $minWordCount; $wordCount++) {
            $word = trim($dictionary->getRandomWord());
            $appliedVariation = '';
            $last = ($wordCount === $minWordCount);
            if ($useVariations) {
                $word = $this->applyVariation($word, $this->prng->rand(0, $variationsCount), $variations, $last, $appliedVariation);
            }
            $password .= $word . ((isset($appliedVariation) && $appliedVariation === 'addslashes' || $last) ? '' : $separator);
        }
        $this->lastPasswordSymbolCount = $minWordCount;
        return trim($password);

    }

    /**
     * Class Constructor
     *
     * The constructor parameters modify the default settings. None is required, every setting can be changed and also overriden at any time.
     *
     * @param mixed $dictionary a dictionary implementing DictionaryInterface, null sets the default: new Dictionary($this->defaultDictionaryFilename), which reads the words from $this->defaultDictionaryFilename.
     * @param mixed $level the level of strength as the index on the array of entropies, minEntropies
     * @param mixed $separators a string containing zero or more characters to be randomly used as separators between words
     * @param mixed $minEntropies an array of ints containing minimum entropies in ascending order, delimiting the minimum entropy per level; null sets the default $this->defaultMinEntropies
     * @param bool $useVariations whether or not to use variations, such as capitalization or punctuation which increase the amount of entropy per word, thus requiring less words to achieve the same entropy (strength)
     * @param mixed $variations array of variations to the words, null sets the default array('capitalize'=>true,'punctuate'=>true,'allcaps'=>true,'addslashes'=>false)
     * @param null|int $minWordSize minimum word length in characters (must be > 0, defaults to null -> defaultMinWordSize)
     * @param null|int $minReadWordsWordSize minimum word length in characters to be read from source on building the dictionary (must be > 0, defaults to null -> defaultMinReadWordsWordSize)
     * @param mixed $prng the pseudo random number generator object (should be crypto safe and contain a method 'rand' with same functionality as 'rand' and '$this->prng->rand' functions. Null defaults to a new instance of CryptoSecurePRNG)
     * @throws Exception generated if any of the parameters is invalid
     */
    public function __construct($dictionary = null, $level = null, $separators = ' ', $minEntropies = null, $useVariations = true, $variations = null, $minWordSize = null, $minReadWordsWordSize = null, $prng = null)
    {

        if ($minWordSize === null) {
            $this->minWordSize = $this->defaultMinWordSize;
        } elseif (is_int($minWordSize)) {
            $this->minWordSize = $minWordSize;
        } else {
            throw new Exception ('$minWordSize must be a valid integer or null.');
        }
        if ($minReadWordsWordSize === null) {
            $this->minReadWordsWordSize = $this->defaultMinReadWordsWordSize;
        } elseif (is_int($minReadWordsWordSize)) {
            $this->minReadWordsWordSize = $minReadWordsWordSize;
        } else {
            throw new Exception ('$minReadWordsWordSize must be a valid integer or null.');
        }
        if ($prng === null) {
            $this->prng = new CryptoSecurePRNG();
        } else {
            if (!is_object($prng) || !method_exists($prng, 'rand')) {
                throw new Exception('$prng must be an object having a rand method');
            } else {
                $this->prng = $prng;
            }
        }
        if ($dictionary === null) {
            $dictionary = new Dictionary($this->defaultDictionaryFilename, $minReadWordsWordSize, 'filename');
        }
        if ($this->setDictionary($dictionary) !== true) {
            throw new Exception ('Invalid Dictionary');
        }

        if ($level === null) {
            $this->level = $this->defaultLevel;
        } elseif (!$this->setLevel($level)) {
            throw new Exception ('$level must be an integer');
        }

        if ($this->setSeparators($separators) !== true) {
            throw new Exception (self::SEPARATORS_MUST_BE_A_STRING);
        }

        if ($minEntropies === null) {
            $this->minEntropies = $this->defaultMinEntropies;
        } elseif ($this->setMinEntropies($minEntropies) !== true) {
            throw new Exception ('$minEntropies must be an array of integers');
        }

        if (!$this->setUseVariations($useVariations)) {
            throw new Exception ('$useVariations must be a boolean');
        }

        if ($this->setVariations($variations) !== true) {
            throw new Exception ('$variations must be an array of keys containing valid variations boolean values');
        }

    }

    /**
     * @return int
     */
    public function getMinWordSize()
    {
        return $this->minWordSize;
    }

    /**
     * @return int
     */
    public function getMinReadWordsWordSize()
    {
        return $this->minReadWordsWordSize;
    }


}
