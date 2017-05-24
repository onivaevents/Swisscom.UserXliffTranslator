<?php
namespace Swisscom\UserXliffTranslator;

/*
 * This file is part of the Swisscom.UserXliffTranslator package.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\I18n;
use TYPO3\Flow\I18n\TranslationProvider\Exception\InvalidPluralFormException;
use TYPO3\Flow\I18n\Xliff\XliffModel;
use TYPO3\Flow\Utility\Files;

/**
 * The concrete implementation of TranslationProviderInterface which uses XLIFF
 * file format to store labels.
 *
 * The labels are read from the overridden translation file if existing.
 *
 * @Flow\Scope("singleton")
 */
class XliffTranslationProvider extends \TYPO3\Flow\I18n\TranslationProvider\XliffTranslationProvider
{
    /**
     * @Flow\InjectConfiguration(path="userXliffBasePath",package="Swisscom.UserXliffTranslator")
     * @var string
     */
    protected $userXliffBasePath;

    /**
     * Returns translated label of $originalLabel from a file defined by $sourceName.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $originalLabel Label used as a key in order to find translation
     * @param I18n\Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @param string $sourceName A relative path to the filename with translations (labels' catalog)
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     * @throws InvalidPluralFormException
     */
    public function getTranslationByOriginalLabel($originalLabel, I18n\Locale $locale, $pluralForm = null, $sourceName = 'Main', $packageKey = 'TYPO3.Flow')
    {
		$translation = false;
		$customModel = $this->getModel($packageKey, $sourceName, $locale);
		if (is_object($customModel)) {
			$translation = $this->getTargetBySource($customModel, $originalLabel, $locale, $pluralForm);
		}
		if ($translation === false) {
			$model = parent::getModel($packageKey, $sourceName, $locale);
            $translation = $this->getTargetBySource($model, $originalLabel, $locale, $pluralForm);
        }
        return $translation;
    }

    /**
     * @param XliffModel $model The model to read the translation from
     * @param string $originalLabel Label used as a key in order to find translation
     * @param I18n\Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @return mixed Translated label or FALSE on failure
     * @throws InvalidPluralFormException
     */
    private function getTargetBySource(XliffModel $model, $originalLabel, I18n\Locale $locale, $pluralForm = null)
    {
        if ($pluralForm !== null) {
            $pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

            if (!is_array($pluralFormsForProvidedLocale) || !in_array($pluralForm, $pluralFormsForProvidedLocale)) {
                throw new InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033386);
            }
            // We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
            $pluralFormIndex = (int)array_search($pluralForm, $pluralFormsForProvidedLocale);
        } else {
            $pluralFormIndex = 0;
        }

        return $model->getTargetBySource($originalLabel, $pluralFormIndex);
    }

    /**
     * Returns label for a key ($labelId) from a file defined by $sourceName.
     *
     * Chooses particular form of label if available and defined in $pluralForm.
     *
     * @param string $labelId Key used to find translated label
     * @param I18n\Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @param string $sourceName A relative path to the filename with translations (labels' catalog)
     * @param string $packageKey Key of the package containing the source file
     * @return mixed Translated label or FALSE on failure
     * @throws InvalidPluralFormException
     */
    public function getTranslationById($labelId, I18n\Locale $locale, $pluralForm = null, $sourceName = 'Main', $packageKey = 'TYPO3.Flow')
    {
        $translation = false;
        $customModel = $this->getModel($packageKey, $sourceName, $locale);
        if (is_object($customModel)) {
            $translation = $this->getTargetByTransUnitId($customModel, $labelId, $locale, $pluralForm);
        }
        if ($translation === false) {
            $model = parent::getModel($packageKey, $sourceName, $locale);
            $translation = $this->getTargetByTransUnitId($model, $labelId, $locale, $pluralForm);
        }
        return $translation;
    }

    /**
     * @param XliffModel $model The model to read the translation from
     * @param string $labelId Key used to find translated label
     * @param I18n\Locale $locale Locale to use
     * @param string $pluralForm One of RULE constants of PluralsReader
     * @return mixed Translated label or FALSE on failure
     * @throws InvalidPluralFormException
     */
    private function getTargetByTransUnitId(XliffModel $model, $labelId, I18n\Locale $locale, $pluralForm = null)
    {
        if ($pluralForm !== null) {
            $pluralFormsForProvidedLocale = $this->pluralsReader->getPluralForms($locale);

            if (!is_array($pluralFormsForProvidedLocale) || !in_array($pluralForm, $pluralFormsForProvidedLocale)) {
                throw new InvalidPluralFormException('There is no plural form "' . $pluralForm . '" in "' . (string)$locale . '" locale.', 1281033386);
            }
            // We need to convert plural form's string to index, as they are accessed using integers in XLIFF files
            $pluralFormIndex = (int)array_search($pluralForm, $pluralFormsForProvidedLocale);
        } else {
            $pluralFormIndex = 0;
        }

        return $model->getTargetByTransUnitId($labelId, $pluralFormIndex);
    }

    /**
     * Returns a XliffModel instance representing desired XLIFF file.
     *
     * Will return existing instance if a model for given $sourceName was already
     * requested before. Returns FALSE when $sourceName doesn't point to existing
     * file.
     *
     * @param string $packageKey Key of the package containing the source file
     * @param string $sourceName Relative path to existing CLDR file
     * @param I18n\Locale $locale Locale object
     * @return XliffModel New or existing instance
     */
    protected function getModel($packageKey, $sourceName, I18n\Locale $locale)
    {
        $sourcePath = Files::concatenatePaths([$this->userXliffBasePath, $packageKey]);
        list($sourcePath, $foundLocale) = $this->localizationService->getXliffFilenameAndPath($sourcePath, $sourceName, $locale);

        if ($sourcePath === false || $foundLocale != $locale) {
            return null;
        } else {
            if (isset($this->models[$sourcePath])) {
                return $this->models[$sourcePath];
            }
            return $this->models[$sourcePath] = new XliffModel($sourcePath, $foundLocale);
        }
    }
}
