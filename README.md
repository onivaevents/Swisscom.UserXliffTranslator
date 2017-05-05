# Swisscom.UserXliffTranslator
Neos Flow package for user based Xliff translation. It requires the base functionality of the package 
[mrimann/XliffTranslator](https://github.com/mrimann/XliffTranslator) for Xliff translation. (Kudos to mrimann for the 
nice work!) Instead of physically overwriting the Xliff file, the Swisscom.UserXliffTranslator stores the modified 
Xliff files under a configurable location. The package ensures the same translation and caching as the Flow core 
functionality.

## Configuration

The base location to store the Xliff files is specified in the Settings.yaml:

    Swisscom:
      UserXliffTranslator:
        userXliffBasePath: '%FLOW_PATH_DATA%Translations'