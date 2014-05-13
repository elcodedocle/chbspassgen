<?php
namespace synapp\info\tools\passwordgenerator\dictionary;

/**
 * Interface DictionaryInterface
 * 
 * Defines the standard methods used to interface with a dictionary
 *
 * @copyright Gael Abadin (elcodedocle) 2014
 * @license MIT Expat http://en.wikipedia.org/wiki/Expat_License
 * @version 0.1
 * 
 */
interface DictionaryInterface {
    public function getRandomWord();
    public function getWordCount();
    public function getRandomWordsArray($count);
    public function readWords($wordsDAO);
}