<?php
/**
 * Class NordicFrance
 * @author clement@noe-interactive.com
*/

class NordicFrance {
    
    protected $_feedUrl = 'http://www.nordicfrance.fr/stations/stations/';
    
    /**
     * XML file name
     * @var string
     */
    protected $_fileName;
    
    /**
     * path to cache folder
     * @var string
     */
    protected $_cacheFolder;
    
    /**
     * cache time in seconds
     * @var int
     */
    protected $_cacheTime;
    
    /**
     * XML object
     * @var SimpleXMLElement
     */
    protected $_xml;
    
    public $nom;
    public $ouverture;
    public $fermeture;
    public $altitudeBas;
    public $altitudeHaut;
    public $imagePrincipale;
    
    /**
     * @var NordicFranceMeteo
     */
    public $meteoAujourdhuiMatin;
    
    /**
     * @var NordicFranceMeteo
     */
    public $meteoAujourdhuiAm;
    
    /**
     * @var NordicFranceMeteo
     */
    public $meteoDemainMatin;
    
    /**
     * @var NordicFranceMeteo
     */
    public $meteoDemainAm;
    
    /**
     * @var NordicFranceNeige
     */
    public $neigeAujourdhui;
    
    /**
     * @var NordicFranceNeige
     */
    public $neigeDemain;
    
    /**
     *
     * @var NordicFrancePiste[]
     */
    public $pistes;
    
    /**
     * @param string $fileName name of XML file without extension
     * @param string $cacheFolder path to cache folder
     * @param int $cacheTime cache time in seconds
     */
    public function __construct($fileName, $cacheFolder = '', $cacheTime = 0) {
        $this->_fileName = $fileName;
        $this->_cacheFolder = $cacheFolder;
        $this->_cacheTime = $cacheTime;
    }
    
    /**
     * fetch data from online XML or cache folder
     * @return boolean
     */
    public function fetchData() {
        $success = $this->_fetchFromCache() ? true : $this->_fetchOnline();
        if($success) $this->_mapData();
        return $success;
    }
    
    /**
     * fetch data from cache folder
     * @return boolean
     */
    protected function _fetchFromCache() {
        $result = false;
        if(!empty($this->_cacheFolder) && is_dir($this->_cacheFolder)) {
            $filePath = $this->_cacheFolder.'/'.$this->_fileName.'.xml';
            if(is_file($filePath) && (time() - filemtime($filePath) <= $this->_cacheTime)) {
                $this->_xml = simplexml_load_file($filePath);
                $result = $this->_xml !== false;
            }
        }
        return $result;
    }
    
    /**
     * fetch data from online XML
     * @return boolean
     */
    protected function _fetchOnline() {
        $this->_xml = simplexml_load_file($this->_feedUrl.$this->_fileName.'.xml');
        if($this->_xml !== false && !empty($this->_cacheFolder)) {
            $filePath = $this->_cacheFolder.'/'.$this->_fileName.'.xml';
            if(!is_dir($this->_cacheFolder)) mkdir($this->_cacheFolder);
            $this->_xml->asXML($filePath);
        }
        return $this->_xml !== false;
    }
    
    /**
     * map data from xml
     */
    protected function _mapData() {
        $this->nom = (string)$this->_xml->station->infos->nom;
        $this->ouverture = (string)$this->_xml->station->infos->ouverture;
        $this->fermeture = (string)$this->_xml->station->infos->fermeture;
        $this->altitudeBas = (int)$this->_xml->station->infos->altitude_bas;
        $this->altitudeHaut = (int)$this->_xml->station->infos->altitude_haut;
        
        $attr = $this->_xml->station->infos->media->image_principale->attributes();
        $this->imagePrincipale = (string)$attr['url'];
        
        $this->meteoAujourdhuiMatin = new NordicFranceMeteo();
        $this->meteoAujourdhuiMatin->map($this->_xml->station->meteo->meteo_du_jour);
        
        $this->meteoAujourdhuiAm = new NordicFranceMeteo();
        $this->meteoAujourdhuiAm->map($this->_xml->station->meteo->meteo_du_jour, false);
        
        $this->meteoDemainMatin = new NordicFranceMeteo();
        $this->meteoDemainMatin->map($this->_xml->station->meteo->meteo_demain);
        
        $this->meteoDemainAm = new NordicFranceMeteo();
        $this->meteoDemainAm->map($this->_xml->station->meteo->meteo_demain, false);
        
        $this->neigeAujourdhui = new NordicFranceNeige();
        $this->neigeAujourdhui->map($this->_xml->station->enneigement->enneigement_du_jour);
        
        $this->neigeDemain = new NordicFranceNeige();
        $this->neigeDemain->map($this->_xml->station->enneigement->enneigement_demain);
        
        $this->pistes = array();
        foreach($this->_xml->station->pistes_itineraires->piste as $pisteXML) {
            $piste = new NordicFrancePiste();
            $piste->map($pisteXML);
            $this->pistes[] = $piste;
        }
    }
    
}

class NordicFranceMeteo {
    
    public $date;
    public $temps;
    public $tempMin;
    public $tempMax;
    public $vent;
    public $picto;
    
    public function map($xmlInfos, $matin = true) {
        $sufix = $matin ? '_matin' : '_am';
        $this->date = (string)$xmlInfos->date;
        $this->temps = (string)$xmlInfos->{'temps'.$sufix}->libelle;
        $this->picto = (string)$xmlInfos->{'temps'.$sufix}->picto;
        $this->tempMin = (int)$xmlInfos->temperature->{'temp_min'.$sufix};
        $this->tempMax = (int)$xmlInfos->temperature->{'temp_max'.$sufix};
        $this->vent = (string)$xmlInfos->vent->{'vent'.$sufix};
    }
    
}

class NordicFranceNeige {
    
    public $date;
    public $risqueAvalanche;
    public $qualiteBas;
    public $qualiteHaut;
    public $hauteurBas;
    public $hauteurHaut;
    public $chuteBas;
    public $chuteHaut;
    
    public function map($xmlInfos) {
        $this->date = (string)$xmlInfos->date;
        $this->risqueAvalanche = (string)$xmlInfos->risque_avalanche;
        $this->qualiteBas = (string)$xmlInfos->neige->qualite_neige_bas;
        $this->qualiteHaut = (string)$xmlInfos->neige->qualite_neige_haut;
        $this->hauteurBas = (int)$xmlInfos->neige->hauteur_neige_bas;
        $this->hauteurHaut = (int)$xmlInfos->neige->hauteur_neige_haut;
        $this->chuteBas = (int)$xmlInfos->neige->chute_neige_bas;
        $this->chuteHaut = (int)$xmlInfos->neige->chute_neige_haut;
    }
    
}

class NordicFrancePiste {
    
    public $date;
    public $nom;
    public $ouverture;
    public $kmTotal;
    public $pratiques;
    public $difficulte;
    public $commentaire;
    public $coordGPS;
    
    public function map($xmlInfos) {
        $this->date = (string)$xmlInfos->date;
        $this->nom = (string)$xmlInfos->nom;
        $this->ouverture = (string)$xmlInfos->ouverture;
        $this->kmTotal = (int)$xmlInfos->km_total;
        $this->pratiques = (string)$xmlInfos->pratiques;
        $this->difficulte = (string)$xmlInfos->difficulte;
        $this->commentaire = (string)$xmlInfos->commentaire;
        $this->coordGPS = (string)$xmlInfos->coord_GPS;
    }
    
}
