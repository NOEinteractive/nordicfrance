nordicfrance
============

Little PHP class to parse NordicFrance XML feed

Example :
```php
$station = new NordicFrance($XMLFileNameWithoutExtension, $pathToCacheFolder, $cacheTimeInSeconds);
$success = $station->fetchData();

if($success) {
  echo $station->nom;
}
```

To be completed with all NordicFrance fields ...
