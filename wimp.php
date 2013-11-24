<?Php
//***Parametere***
$inputfolder='/home/wimp/input'; //Legg filer her
$outputfolder='/home/wimp/output'; //Her havner filer med navn og tags
$source='http://wimp.no/album/17861219'; //URL til spilleliste eller album
$compilation='false'; //Må være string for å brukes i shell_exec
//***Parametere slutt***

if(preg_match('^album/([0-9]+)^',$source,$albumid)) //Hvis det er et album, hent cover
	copy('http://varnish01.music.aspiro.com/im/im?w=700&h=700&albumid='.$albumid[1],$artwork=$albumid[1].'.jpeg');
if(!file_exists($outputfolder))
	mkdir($outputfolder);



$data=file_get_contents($source);	

preg_match_all ('^track/([0-9]+)".*\>(.+)\<^U',$data,$tracks); //Hent titler
if(strpos($source,'album')) //Sjekk om kilden er et album
{
	preg_match('^marginTopS"\>(.*)\<^',$data,$album); //Hent albumtittel
	$album=$album[1];
}
else
	$album='';

function striptags(&$data,$key) //Fjern tags og spørsmålstegn
{
$data=strip_tags($data);
$data=str_replace(array('?'),null,$data);
}
array_walk($tracks[2],'striptags'); //Bruk funksjonen striptags på alle posisjonene i $tracks[2]
print_r($tracks);

$dir=scandir($inputfolder);
unset($dir[0],$dir[1]);

sort($dir,SORT_NUMERIC);
if(count($dir)!=$totaltracks=count($tracks[1]))
	die('Antall spor på album stemmer ikke med filer i inputmappe');
for ($i=0; $i<count($dir); $i++)
{
	$songdata=file_get_contents('http://wimp.no/wweb/track/'.$tracks[1][$i]);
	preg_match('^artist/([0-9]+)".*\>(.+)\<^U',$songdata,$artist); //Hent artister
	
	$songname=trim(html_entity_decode($tracks[2][$i]));
	$artist=trim(html_entity_decode($artist[2]));
	
	$track=$i+1;
	$type=substr($dir[$i],-3);
	$track=str_pad($track,2,'0',STR_PAD_LEFT);


	if ($compilation=='true') //Artist skal bare være med i filnavn hvis det er et samealbum
		$outfile="$outputfolder/{$track} {$artist} - {$songname}.$type";
	else
		$outfile="$outputfolder/{$track} {$songname}.$type";
	
	$cmd="AtomicParsley \"$inputfolder/{$dir[$i]}\" --output \"$outfile\" --artist=\"$artist\" --tracknum=$track/$totaltracks --title=\"$songname\" --compilation=$compilation --album=\"$album\"";

	if(isset($artwork) && file_exists($artwork))
		$cmd.=" --artwork $albumid[1].jpeg";

	$cmd.=" 2>&1";
	echo shell_exec($cmd);
	echo $cmd."\n";

}
if(file_exists($artwork))
	unlink($artwork);

?>