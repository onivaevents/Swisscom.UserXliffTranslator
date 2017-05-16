<?php
namespace Swisscom\UserXliffTranslator;

/*
 * This file is part of the Swisscom.UserXliffTranslator package.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Files;

/**
 * A Service which contains the main functionality of the XliffTranslator
 *
 * @Flow\Scope("singleton")
 */
class XliffTranslatorService extends \Mrimann\XliffTranslator\XliffTranslatorService
{
    /**
     * @Flow\InjectConfiguration(path="userXliffBasePath", package="Swisscom.UserXliffTranslator")
     * @var string
     */
    protected $userXliffBasePath;

    /**
     * Reads a particular Xliff file and returns it's translation units as array entries
     *
     * @param string $packageKey package key
     * @param string $sourceName source name (e.g. filename)
     * @param \TYPO3\Flow\I18n\Locale the locale
     *
     * @return array
     */
    protected function getXliffDataAsArray($packageKey, $sourceName, \TYPO3\Flow\I18n\Locale $locale) {
        $xliff = parent::getXliffDataAsArray($packageKey, $sourceName, $locale);
        $userSourcePath = Files::concatenatePaths([$this->userXliffBasePath, $packageKey]);
        list($userSourcePath, $foundLocale) = $this->localizationService->getXliffFilenameAndPath($userSourcePath, $sourceName, $locale);

        if ($userSourcePath === false || $foundLocale != $locale) {
            return $xliff;
        } else {
            $userXliff = $this->xliffParser->getParsedData($userSourcePath);
            $userXliff['translationUnits'] = array_replace($xliff['translationUnits'], array_intersect_key($userXliff['translationUnits'], $xliff['translationUnits']));
            return $userXliff;
        }
    }

	/**
	 * Gets the full filesystem path to an Xliff file of a specified language within
	 * a specific package.
	 *
	 * @param string $packageKey
	 * @param string $language
     * @param string $sourceName
	 * @return string
	 */
	protected function getFilePath($packageKey, $language, $sourceName) {
        return Files::concatenatePaths([$this->userXliffBasePath, $packageKey, $language, $sourceName . '.xlf']);
	}
}
