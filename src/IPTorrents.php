<?php

namespace TorrentScraper;

class IPTorrents {
	private $id;


	function __construct($id) {
		$this->id = $id;
	}

	function searchCategory($category) {

	    $ch = curl_init(); 

	    $id = $this->id;

	    $url = "https://iptorrents.com/t?$category=&q=$id&o=completed&o=seeders";

	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, "/app/tmp/cookies.cookie");
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	    $html = curl_exec($ch);

	    curl_close($ch);

	    return $html;
	}

	function parseHTML($html) {

	    $baseUrl = "https://iptorrents.com";


	    $dom = new \DOMDocument();
	    $dom->loadHTML($html);
	    $xpath = new \DOMXPath($dom);


	    $counter = 1;
	    $results = array();

	    while ($xpath->evaluate("string(//*[@id='torrents']/tbody/tr[$counter]/td[2]/a/text())") !== "") {

	        $torrentName = $xpath->evaluate("string(//*[@id='torrents']/tbody/tr[$counter]/td[2]/a/text())");
	        $torrentUrl = $baseUrl . $xpath->evaluate("string(//*[@id='torrents']/tbody/tr[$counter]/td[4]/a/@href)");
	        $torrentSize = $xpath->evaluate("string(//*[@id='torrents']/tbody/tr[$counter]/td[6]/text())");
	        $torrentDownloads = $xpath->evaluate("string(//*[@id='torrents']/tbody/tr[$counter]/td[7]/text())");
	        $torrentSeeders = $xpath->evaluate("string(//*[@id='torrents']/tbody/tr[$counter]/td[8]/text())");

	        

	        //syslog(LOG_INFO, "Name: ".$torrentName.", Seeders: ".$torrentSeeders);

            //removes the " GB" from the end
            if (preg_match("/GB/", $torrentSize))
                $torrentSize = substr($torrentSize, 0, -3);
            else if (preg_match("/MB/", $torrentSize))
                $torrentSize = 1;
	        

	        $results[] = array(
	            'torrentName' => $torrentName,
	            'torrentSize' => $torrentSize,
	            'torrentDownloads' => $torrentDownloads,
	            'torrentUrl' => $torrentUrl,
	            'torrentSeeders' => $torrentSeeders,
	        	);

	        $counter++;

	    }

	    return $results;
	}

	function searchTorrent ($res, $size, $minSize=0) {

	    ///////////////////////////////////
	    //// CURRENTLY ONLY FOR MOVIES ////
	    ///////////////////////////////////

	    //check in Movie/HD/Bluray
      $html = $this->searchCategory("48"); 

	    if (!preg_match("/No Torrents Found!/", $html)) {


	        $torrents = $this->parseHTML($html);


	        foreach ($torrents as $torrent) {
	        	syslog(LOG_INFO, $torrent['torrentName']);
	            if (preg_match("/$res/", $torrent['torrentName']) && $torrent['torrentSize'] < $size && $torrent['torrentSize'] > $minSize && $torrent['torrentSeeders'] > 2 && 
	            	!preg_match("/HC/", $torrent['torrentName'])) {
	                    $hash = $this->downloadTorrent($torrent['torrentUrl']);
	                    if ($hash != false) {
	                    	return $hash;
	                    }
	                }
	        }

	    }

	    //check in Movie/Web-DL
	    $html = $this->searchCategory("20"); 

	    if (!preg_match("/No Torrents Found!/", $html)) {

	        $torrents = $this->parseHTML($html);


	        foreach ($torrents as $torrent) {
	            if (preg_match("/$res/", $torrent['torrentName']) && $torrent['torrentSize'] < $size && $torrent['torrentSize'] > $minSize && $torrent['torrentSeeders'] > 2) {
	                    $hash = $this->downloadTorrent($torrent['torrentUrl']);
	                    if ($hash != false) {
	                    	return $hash;
	                    }
	                }
	        }

	    }

	    //check in most Movie Categories
        $html = $this->searchCategory("62=&100=&7=&20=&26=&7=");

	    if (!preg_match("/No Torrents Found!/", $html)) {

            $torrents = $this->parseHTML($html);

	        foreach ($torrents as $torrent) {
	            if (preg_match("/$res/", $torrent['torrentName']) && $torrent['torrentSize'] < $size && $torrent['torrentSize'] > $minSize && $torrent['torrentSeeders'] > 0) {
	            		$hash = $this->downloadTorrent($torrent['torrentUrl']);
	                    if ($hash != false) {
	                    	return $hash;
	                    }
	                }
	        }


	    }
	    return false;


	}

	function downloadTorrent ($url) {

	    $ch = curl_init();


	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, "/app/tmp/cookies.cookie");
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	    $curl = curl_exec($ch);

	    $downloadPath = "/app/tmp/$this->id.torrent";
	    $file = fopen($downloadPath, "w+");
	    fwrite($file, $curl);

	    curl_close($ch);
	    fclose($file);

	    #$hash = decodeTorrent($this->id);
		#find new way to do hash
	    return false;

	}
}