<?Php
//***Parametere***
$inputfolder=$argv[2]; //Legg filer her
$outputfolder=$inputfolder."/../"; //Utmappe er en mappe over innmappe
$source=$argv[1]; //URL til album
$compilation='false'; //Må være string for å brukes i shell_exec
//***Parametere slutt***
if(preg_match('^playlist.([0-9a-f\-]+)^',$source,$playlistid))
	die('Bruk spilleliste.php for spillelister');
if(!preg_match('^album/([0-9]+)^',$source,$albumid)) //Sjekk om kilden er et album
	die("Ugyldig url til album\n");

$data=file_get_contents($source);	

preg_match_all ('^track/([0-9]+)".*\>(.+)\<^U',$data,$tracks); //Hent titler
preg_match('^property="og:title" content="(.+)"^U',$data,$title);
	preg_match('^artist/[0-9]+".+\>(.+)\<^U',$data,$albumartist);
	preg_match('^marginTopS"\>(.*)\<^',$data,$album); //Hent albumtittel
	$album=$album[1];

$outputfolder.=filnavn($albumartist[1].' - '.$album).'/';
if(!file_exists($outputfolder))
	mkdir($outputfolder);
	
	copy('http://varnish01.music.aspiro.com/im/im?w=700&h=700&albumid='.$albumid[1],$artwork=$outputfolder.$album.'.jpeg'); //Hent cover

function striptags(&$data,$key) //Fjern tags og spørsmålstegn
{
$data=strip_tags($data);
$data=str_replace(array('?'),null,$data);
}
array_walk($tracks[2],'striptags'); //Bruk funksjonen striptags på alle posisjonene i $tracks[2]
function filnavn($filnavn)
{
	return str_replace(array(':','?','*','|','<','>','/','\\','"'),array('-','','','','','','','',''),$filnavn); //Fjern tegn som ikke kan brukes i filnavn på windows	
}


$dir=scandir($inputfolder);
unset($dir[0],$dir[1]);

sort($dir,SORT_NUMERIC);
if(count($dir)!=$totaltracks=count($tracks[1]))
	die("Antall spor på album stemmer ikke med filer i inputmappe\n");

foreach ($dir as $i=>$file)
{
	$songdata=file_get_contents('http://wimp.no/wweb/track/'.$tracks[1][$i]);
	preg_match('^artist/([0-9]+)".*\>(.+)\<^U',$songdata,$artist); //Hent artister
	
	$songname=ucfirst(strtolower(trim(html_entity_decode($tracks[2][$i]))));
	$artist=trim(html_entity_decode($artist[2]));
	
	$track=$i+1;
	$type=substr($dir[$i],-3);
	$track=str_pad($track,2,'0',STR_PAD_LEFT);


	if ($compilation=='true') //Artist skal bare være med i filnavn hvis det er et samealbum
		$outfile="{$track} {$artist} - {$songname}.$type";
	elseif($totaltracks==1) //For singler skal navnet bestå av artist og tittel
		$outfile="{$artist} - {$songname}.$type";
	else
		$outfile="{$track} {$songname}.$type";
	
	
/*	if(PHP_OS=='WINNT')
	{
		$songname=utf8_decode($songname);
		$artist=utf8_decode($artist);
	}*/
	$outfile=$outputfolder.filnavn($outfile);
	if(file_exists($outfile))
		continue;
	$songname=str_replace('"','\\"',$songname);
	$cmd="AtomicParsley \"$inputfolder/{$dir[$i]}\" --output \"$outfile\" --artist=\"$artist\" --tracknum=$track/$totaltracks --title=\"$songname\" --compilation=$compilation --album=\"$album\"";

	
		//$cmd=utf8_decode($cmd);

	if(isset($artwork) && file_exists($artwork))
		$cmd.=" --artwork \"$artwork\"";

	$cmd.=" 2>&1";
	if(PHP_OS=='WINNT')
		utf8_decode($cmd);
	echo shell_exec($cmd);
	//print_r($tracklist[$i]);
	//die();
	echo $cmd."\n";

}
/*if(file_exists($artwork))
	unlink($artwork);*/

?>