<?Php
//***Parametere***
$inputfolder=$argv[2]; //Legg filer her
$source=$argv[1]; //URL til spilleliste
$albumfolders=true;
//***Parametere slutt***

require 'wimpclass.php';
$wimp=new wimp;
$playlistid=preg_replace('^.+playlist.([0-9a-f\-]+).*^','$1',$source);
$playlist=$wimp->getplaylist($playlistid);

$inputfolder.='/';
$baseoutdir=$wimp->config['outpath'].$playlist['title'].'/';
$dir=scandir($inputfolder);
unset($dir[0],$dir[1]);

sort($dir,SORT_NUMERIC);
if(count($dir)!=$totaltracks=count($playlist['tracks']))
	die('Antall spor på spilleliste stemmer ikke med filer i inputmappe');


foreach($playlist['tracks'] as $key=>$track)
{
	//print_r($track);
	$trackinfo=$wimp->trackinfo(file_get_contents($track['info'])); //Hent informasjon om sporet på spillelisten
	$trackid=preg_replace('^.+track/([0-9]+).*^','$1',$track['info']); //Hent sporets id

	$albuminfo=$wimp->albuminfo($trackinfo['album']);
	$albumtrackinfo=$albuminfo['tracklist'][$trackid]; //Hent informasjon om sporet på albumet
	if($albumfolders===true)
		$outdir=$baseoutdir.$albuminfo['tittel'].'/';
	else
		$outdir=$baseoutdir;
	if(!file_exists($outdir))
		mkdir($outdir,0777,true);
	if(!file_exists($cover=$outdir.$albuminfo['tittel'].'.jpeg'))
		copy($albuminfo['cover'],$cover);
	echo $wimp->atomicparsley($inputfolder.$dir[$key],$outdir,$track['title'],$track['creator'],$track['album'],$albumtrackinfo['tracknumber'],$albuminfo['spor'],$cover)."\n";
}