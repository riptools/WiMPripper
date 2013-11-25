WiMPripper
==========

Verktøy for å rippe fra WiMP

Start fiddler, gå til filters, trykk på actions, load filterset og last inn filter.ffx fra samme mappe som scriptene.
Gå gjennom spillelisten med fiddler åpen
Merk alle elementene i fiddler, høyreklikk og save>response>response body. Behold filnavnet den foreslår.
Legg filene i mappen spesifisert i $inputfolder i wimp.php
Spesifiser link til spilleliste eller album som første kommandolinjeparameter og mappen filene ligger i som andre:
php wimp.php <link> <mappe>
De navngitte filene havner i en mappe med albumets navn på samme nivå som kildemappen.