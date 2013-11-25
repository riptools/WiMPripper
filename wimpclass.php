<?Php
class wimp
{
	public $albums;
	public $config;
	function __construct()
	{
		$this->config['outpath']='/home/wimp/';	
	}
	function getplaylist($id)
	{
		$tracklist=json_decode(json_encode(simplexml_load_file("http://wimp.no/resources/xspf/wimp_playlist.ftl?playlist={$id}")),true);
		return array('title'=>$tracklist['title'],'tracks'=>$tracklist['trackList']['track']);

	}
	function albuminfo($url)
	{
		$albumid=preg_replace('^.+album/([0-9]+).*^','$1',$url); //Hent albumid fra url

		if(isset($this->albums[$albumid]))
			return $this->albums[$albumid];
		echo "Get album $albumid\n";
		$data=file_get_contents($url);
		
		preg_match('^artist/[0-9]+".+\>(.+)\<^U',$data,$albumartist); //Finn artisten
		preg_match('^marginTopS"\>(.*)\<^',$data,$tittel); //Hent albumtittel
		//$outputfolder=$inputfolder."/../".filnavn($albumartist[1].' - '.$album).'/';
		preg_match('/([0-9]+) spor\n.([0-9:]+)/',$data,$sporinfo);


		$doc = new DOMDocument();
		@$doc->loadHTML($data);
		$tables=$doc->getElementsByTagName('table');
		
		for($i=2; $i<$sporinfo[1]+2; $i++)
		{
			$trackinfo=$tables->item($i)->getElementsByTagName('td');
			$trackid=preg_replace('^.+/([0-9]+)^','$1',$trackinfo->item(1)->getElementsByTagName('div')->item(0)->getElementsByTagName('a')->item(0)->getAttribute('href'));
			$tracklist[$trackid]=array('title'=>trim($trackinfo->item(1)->getElementsByTagName('div')->item(0)->nodeValue),
							'creator'=>trim($trackinfo->item(2)->nodeValue),
							'tracknumber'=>trim($trackinfo->item(0)->nodeValue),
							'trackid'=>$trackid);
		}

		return $this->albums[$albumid]=array('tittel'=>$tittel[1],
			'artist'=>$albumartist[1],
			'spor'=>$sporinfo[1],
			'varighet'=>$sporinfo[2],
			'id'=>$albumid,
			'cover'=>'http://varnish01.music.aspiro.com/im/im?w=700&h=700&albumid='.$albumid,
			'tracklist'=>$tracklist);
		
	}
	function trackinfo($songdata)
	{
		//$songdata=file_get_contents('http://wimp.no/wweb/track/'.$id);
		$metadata=$this->metaparser($songdata,array('artist'=>'music:musician','album'=>'music:album','bilde'=>'og:image'));
		return $metadata;
	}
	function metaparser($html,$properties=array('music:musician','music:song'))
	{
		$doc = new DOMDocument();
		@$doc->loadHTML($html);
		$meta=$doc->getElementsByTagName('meta');
		//print_r($meta);
		
		for ($i=0; $i<$meta->length; $i++)
		{
			if(!$meta->item($i)->hasAttribute('property'))
				continue;
			$property=$meta->item($i)->getAttribute('property');
			//var_dump($property);
			if(($key=array_search($property,$properties))!==false)
				$matches[$key]=$meta->item($i)->getAttribute('content');
		}
		return $matches;
	}
	function atomicparsley($infile,$outpath,$title,$artist,$album,$track,$totaltracks,$artwork=false,$compilation='false')
	{
		$title=str_replace('"','\\"',$title);
		if(PHP_OS=='WINNT')
		{
			$infile=utf8_decode($infile);
			$outfile=utf8_decode($outfile);
		}
		$extension=substr($infile,-3);
		$track=str_pad($track,2,'0',STR_PAD_LEFT);
	
		if ($compilation=='true') //Artist skal bare være med i filnavn hvis det er et samealbum
			$outfile="{$track} {$artist} - {$title}.$extension";
		elseif($totaltracks==1) //For singler skal navnet bestå av artist og tittel
			$outfile="{$artist} - {$title}.$extension";
		else
			$outfile="{$track} {$title}.$extension";
		if(substr($outpath,-1,1)!=='/')
			$outpath.='/';

		$outfile=str_replace(array(':','?','*','|','<','>','/','\\','"'),array('-','','','','','','','',''),$outfile); //Fjern tegn som ikke kan brukes i filnavn på windows
		$outfile=$outpath.$outfile;
		if(file_exists($outfile))
			return false;
		$cmd="AtomicParsley \"$infile\" --output \"$outfile\" --artist=\"$artist\" --tracknum=$track/$totaltracks --title=\"$title\" --compilation=$compilation --album=\"$album\"";
	
		if($artwork!==false && file_exists($artwork))
			$cmd.=" --artwork \"$artwork\"";
	
		$cmd.=" 2>&1";
		echo shell_exec($cmd);
		return $cmd;
	}
}