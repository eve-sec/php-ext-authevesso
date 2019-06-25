<?php

use \snitch\authevesso\AUTHEVESSO_LOG;

class EVEHELPERS {

    public static function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        if (function_exists('random_int')) {
            for ($i = 0; $i < $length; ++$i) {
                $str .= $keyspace[random_int(0, $max)];
            }
        } else {
            for ($i = 0; $i < $length; ++$i) {
                $str .= $keyspace[rand(0, $max)];
            }
        }
        return $str;
    }

    public static function xml2array ( $xmlObject, $out = array () )
    {
        foreach ( (array) $xmlObject as $index => $node )
            $out[$index] = ( is_object ( $node ) ) ? self::xml2array ( $node ) : $node;

        return $out;
    }


    private static function flatten(array $array) {
        $return = array();
        array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
        return $return;
    }

    public static function esiIdsToNames($ids) {
        if (!count((array)$ids)) {
            return array();
        }
        $log = new AUTHEVESSO_LOG();
        $lookup = array();
        foreach($ids as $key=>$val) {
            $lookup[$val] = true;
        }
        $lookup = array_keys($lookup);
        $esiapi = new ESIAPI();
        $universeapi = $esiapi->getApi('Universe');
        $dict = array();
        try {
            $results = $universeapi->postUniverseNames(json_encode($lookup), 'tranquility');
            foreach($results as $r) {
                $dict[$r->getId()] = $r->getName();
            }
        } catch (Exception $e) {
            $log->add($e->getMessage().' POST params: ['.implode(", ",$lookup)."]");
            $promise = array();
            foreach ($lookup as $l) {
                $promise[] = $universeapi->postUniverseNamesAsync(json_encode(array($l)), 'tranquility');
                $responses = GuzzleHttp\Promise\settle($promise)->wait();
                foreach ($responses as $response) {
                    if ($response['state'] == 'fulfilled') {
                        foreach ($response['value'] as $r) {
                            $dict[$r->getId()] = $r->getName();
                        }
                    }
                }
            }
        }
        return $dict;
    }

   public static function esiMailIdsToNames($mailids) {
        $log = new AUTHEVESSO_LOG();
        $dict = array();
        foreach($mailids as $cat => $_ids) {
            try {
                $esiapi = new ESIAPI();
                switch($cat) {
                    case 'alliance':
                        $allianceapi = $esiapi->getApi('Alliance');
                        foreach (array_chunk($_ids, 80) as $ids) {
                            $promise[] = $allianceapi->getAlliancesNamesAsync($ids, 'tranquility');
                        }
                        break;
                    case 'corporation':
                        $universeapi = $esiapi->getApi('Universe');
                        foreach (array_chunk(array_unique($_ids), 80) as $ids) {
                            $promise[] = $universeapi->postUniverseNamesAsync(json_encode($ids), 'tranquility');
                        }
                        break;
                    case 'character':
                        $universeapi = $esiapi->getApi('Universe');
                        foreach (array_chunk(array_unique($_ids), 80) as $ids) {
                            $promise[] = $universeapi->postUniverseNamesAsync(json_encode($ids), 'tranquility');
                        }
                        break;
                }
            } catch (Exception $e) {
                $log->add($e->getMessage()); 
            }
        }
        $responses = GuzzleHttp\Promise\settle($promise)->wait();
        foreach ($responses as $response) {
            if ($response['state'] == 'fulfilled') {
                foreach ($response['value'] as $r) {
                    switch(get_class($r)) {
                        case 'Swagger\Client\Model\GetAlliancesNames200Ok':
                            $dict[$r->getAllianceId()] = $r->getAllianceName();
                            break;
                        case 'Swagger\Client\Model\GetCorporationsNames200Ok':
                            $dict[$r->getCorporationId()] = $r->getCorporationName();
                            break;
                        case 'Swagger\Client\Model\GetCharactersNames200Ok':
                            $dict[$r->getCharacterId()] = $r->getCharacterName();
                            break;
                    }
                }
            } elseif ($response['state'] == 'rejected') {
                $log->add($response['reason']);
            }
        }
        return $dict;
    }

    public static function esiMailIdsLookup($ids) {
        if (!count((array)$ids)) {
            return array();
        }
        $log = new AUTHEVESSO_LOG();
        $lookup = array();
        foreach($ids as $key=>$val) {
            $lookup[$val] = true;
        }
        $dict = array();
        $esiapi = new ESIAPI();
        try {
            if (count($lookup)) {
                $charapi = $esiapi->getApi('Character');
                $results = $charapi->getCharactersNames(array_keys($lookup), 'tranquility');
                foreach($results as $result) {
                    $dict[$result->getCharacterId()] = array('name' => $result->getCharacterName(), 'cat' => 'character');
                    unset($lookup[$result->getCharacterId()]);
                }
            }
            if (count($lookup)) {
                $corpapi = $esiapi->getApi('Corporation');
                $results = $corpapi->getCorporationsNames(array_keys($lookup), 'tranquility');
                foreach($results as $result) {
                    $dict[$result->getCorporationId()] = array('name' => $result->getCorporationName(), 'cat' => 'corporation');
                    unset($lookup[$result->getCorporationId()]);
                }
            }
            if (count($lookup)) {
                $allianceapi = $esiapi->getApi('Alliance');
                $results = $allianceapi->getAlliancesNames(array_keys($lookup), 'tranquility');
                foreach($results as $result) {
                    $dict[$result->getAllianceId()] = array('name' => $result->getAllianceName(), 'cat' => 'alliance');
                    unset($lookup[$result->getCorporationId()]);
                }
            }
        } catch (Exception $e) {
            $log->add($e->getMessage());
        }
        return $dict;
    }

    public static function esiIdsLookup($ids) {
        if (!count((array)$ids)) {
            return array();
        }
        $log = new AUTHEVESSO_LOG();
        $lookup = array();
        foreach($ids as $key=>$val) {
            $lookup[$val] = true;
        }
        $lookup = array_keys($lookup);
        $esiapi = new ESIAPI();
        $universeapi = $esiapi->getApi('Universe');
        try {
            $results = $universeapi->postUniverseNames(json_encode($lookup), 'tranquility');
        } catch (Exception $e) {
            $log->add($e->getMessage().' POST params: ['.implode(", ",$lookup)."]");
            return null;
        }
        $dict = array();
        foreach($results as $r) {
            $dict[$r->getId()] = array('name' => $r->getName(), 'cat' => $r->getCategory());
        }
        return $dict;
    }

    public static function getCorpForChar($characterID) {
        $log = new AUTHEVESSO_LOG();
        $esiapi = new ESIAPI();
        $charapi = $esiapi->getApi('Character');
        try {
            $charinfo = json_decode($charapi->getCharactersCharacterId($characterID, 'tranquility'));
            $corpID = $charinfo->corporation_id;
        } catch (Exception $e) {
            $log->add($e->getMessage());
            $corpID = null;
        }
        return $corpID;
    }

    public static function getCorpInfo($corpID) {
        $log = new AUTHEVESSO_LOG();
        $esiapi = new ESIAPI();
        $corpapi = $esiapi->getApi('Corporation');
        try {
            $corpinfo = $corpapi->getCorporationsCorporationId($corpID, 'tranquility');
        } catch (Exception $e) {
            $log->add($e->getMessage());
            $corpinfo = null;
        }
        return $corpinfo;
    }


    public static function getAllyForCorp($corpID) {
        $log = new AUTHEVESSO_LOG();
        $esiapi = new ESIAPI();
        $corpapi = $esiapi->getApi('Corporation');
        try {
            $corpinfo = json_decode($corpapi->getCorporationsCorporationId($corpID, 'tranquility'));
            if (isset($corpinfo->alliance_id)) {
                $allyID = $corpinfo->alliance_id;
            } else {
                $allyID = null;
            }
        } catch (Exception $e) {
            $log->add($e->getMessage());
            $allyID = null;
        }
        return $allyID;
    }

    public static function getAllyInfo($allyID) {
        $log = new AUTHEVESSO_LOG();
        $esiapi = new ESIAPI();
        $allyapi = $esiapi->getApi('Alliance');
        try {
            $allyinfo = $allyapi->getAlliancesAllianceId($allyID, 'tranquility');
        } catch (Exception $e) {
            $log->add($e->getMessage());
            $allyinfo = null;
        }
        return $allyinfo;
    }

    public static function getAllyHistory($corpid) {
        $log = new AUTHEVESSO_LOG();
        $esiapi = new ESIAPI();
        $corpapi = $esiapi->getApi('Corporation');
        $allys = array();
        $lookup = array();
        try {
            $allyHist = ($corpapi->getCorporationsCorporationIdAlliancehistory($corpid, 'tranquility'));
            if (count($allyHist)) {
                foreach($allyHist as $ally) {
                    $temp=array();
                    $temp['id'] = $ally->getAllianceId();
                    $temp['joined'] = date_format($ally->getStartDate(), 'Y-m-d h:i:s');
                    if (!empty($temp['id']) && !is_null($temp['id'])) {
                        $lookup[$ally->getAllianceId()] = null;
                        $allys[]=$temp;
                    }
                }
            }
            if (count($lookup)) {
                $allyapi = $esiapi->getApi('Alliance');
                $results = $allyapi->getAlliancesNames(array_keys($lookup), 'tranquility');
                foreach($results as $result) {
                    $lookup[$result->getAllianceId()] = $result->getAllianceName();
                }
                foreach($allys as $i => $ally) {
                    if (isset($ally['id'])) {
                        $allys[$i]['name'] = $lookup[$ally['id']];
                    }
                }
            }
        } catch (Exception $e) {
            $log->add($e);
            $allys = null;
        }
        return $allys;
    }

    public static function mailparse($html) {
        $html = str_replace(array('color="#bfffffff"', 'color="#ffffa600"', 'size="12"'), '', $html);
        //$html = str_replace('href="fitting:', 'target="_blank" href="fitting.php?dna=', $html);
        $html = preg_replace('/<a href="killReport:(\d+):(\w+)">/', '<a href="https://zkillboard.com/kill/\1/#\2" target="_blank">', $html);
        $html = str_replace(array('href="showinfo:1380//', 'href="showinfo:1379//', 'href="showinfo:1385//', 'href="showinfo:1375//','href="showinfo:1378//', 'href="showinfo:1377//'), 'target="_blank" href="https://zkillboard.com/character/', $html);
        $html = str_replace('href="showinfo:2//', 'target="_blank" href="https://zkillboard.com/corporation/', $html);
        $html = str_replace('href="showinfo:16159//', 'target="_blank" href="https://zkillboard.com/alliance/', $html);
        $html = str_replace('href="showinfo:5//', 'target="_blank" href="http://evemaps.dotlan.net/system/', $html);
        $html = preg_replace("/<a(.*?)>/", "<a$1 target=\"_blank\">", $html);
        $html = preg_replace('$(?<=\s|^|br\s\/>|br>|br\/>|div>|<p>)(https?:\/\/[a-z0-9_./?=&-]+)(?![^<>]*>)$i', ' <a href="$1" target="_blank">$1</a> ', $html." ");
        $html = preg_replace('/<a href="fitting:([0-9:;._]*)"[a-zA-Z0-9\=\s_:;"]*>/', '<a href="#" onclick="showfit(this, \'\1\'); return false;">', $html);
        $html = preg_replace('/size="[^"]*[^"]"/', "", $html);
        $html = preg_replace('/(color="#)[a-f0-9]{2}([a-f0-9]{6}")/', '\1\2', $html);
        return $html;
    }

}
?>
