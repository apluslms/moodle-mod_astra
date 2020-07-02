<?php

/**
 * Finnish strings for astra.
 *
 * @package    mod_astra
 * @copyright  2018 Aalto SCI CS dept.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Astra-tehtävä';
$string['modulenameplural'] = 'Astra-tehtävät';
$string['noastras'] = 'Ei Astra-tehtäviä tällä kurssilla';
$string['modulename_help'] = 'Astra-moduuli integroi <a href="https://apluslms.github.io/" target="_blank">A+-oppimisalustan</a> kurssin Moodleen.

Astra-aktiviteetteja ei pidä luoda käsin tämän aktiviteettivalikon kautta. Sen sijaan lisää kurssitilaan Astra-tehtävien asennuslohko ja tuo A+-kurssin asetukset tehtäväpalvelusta.

Aplus-manual-kurssissa on ohjeita Astran käytöstä:

* <a href="https://plus.cs.aalto.fi/aplus-manual/master/moodle_astra/introduction/" target="_blank">https://plus.cs.aalto.fi/aplus-manual/master/moodle_astra/introduction/</a>
* <a href="https://github.com/apluslms/course-templates/blob/master/moodle_astra/introduction.rst" target="_blank">https://github.com/apluslms/course-templates/blob/master/moodle_astra/introduction.rst</a>';

$string['astra'] = 'Astra';
$string['pluginadministration'] = 'Astra-tehtävien hallinta';
$string['pluginname'] = 'Astra-tehtävät'; // Used by Moodle core
// Moodle Message API
$string['messageprovider:assistant_feedback_notification'] = 'Huomautus uudesta opettajan antamasta palautteesta';

// mod_form.php
$string['deadline'] = 'Määräaika';
$string['roundname'] = 'Tehtäväkierroksen nimi';
$string['roundname_help'] = 'Tämä on käyttäjälle näytettävä kierroksen nimi. Huomaa, että nimen alussa oleva kierroksen numero päivitetään automaattisesti kurssin asetusten mukaan.';
$string['note'] = 'Huomautus';
$string['donotusemodform'] = 'Tehtävien ja kierrosten asetusten muokkaamiseen suositellaan Astran omaa asetussivua, jonne pääsee Astran asetuslohkon kautta. Tällä sivulla voi kuitenkin muokata moduulien yleisiä asetuksia ja pääsyn rajoituksia.';
$string['status'] = 'Tila';
$string['statusready'] = 'Valmis';
$string['statushidden'] = 'Piilotettu';
$string['statusmaintenance'] = 'Huoltokatko';
$string['remotekey'] = 'Tehtäväpalvelun avain';
$string['remotekey_help'] = 'Uniikki avain tehtäväpalvelussa.';
$string['pointstopass'] = 'Läpipääsyn pisteraja';
$string['pointstopass_help'] = 'Opiskelijan täytyy saada ainakin tämän verran pisteitä läpäistääkseen tehtävän.';
$string['openingtime'] = 'Avautumisaika';
$string['openingtime_help'] = 'Tehtäviä ei voi palauttaa ennen avautumista.';
$string['closingtime'] = 'Sulkeutumisaika';
$string['closingtime_help'] = 'Sulkeutumisajan jälkeen palautukset ovat myöhässä ja ne voidaan estää kokonaan tai niille annetaan pistesakkoja.';
$string['latesbmsallowed'] = 'Sallitaan myöhästyneet palautukset';
$string['latesbmsallowed_help'] = 'Myöhästyneet palautukset voidaan sallia erilliseen määräaikaan asti, jolloin niille voidaan antaa pistesakkoja.';
$string['latesbmsdl'] = 'Myöhästyneiden palautusten määräaika';
$string['latesbmsdl_help'] = 'Kun palautus tehdään sulkeutumisjan jälkeen ja ennen myöhästyneiden palautusten määräaikaa, se saa pistesakkoja.';
$string['latesbmspenalty'] = 'Myöhästyneiden palautusten pistesakko';
$string['latesbmspenalty_help'] = 'Kerroin desimaalilukuna, jolla pisteitä vähennetään. 0.1 = 10 %';
$string['ordernum'] = 'Järjestys';
$string['ordernum_help'] = 'Järjestysnumero, jonka mukaan kurssin sisältö esitetään sivulla. Pienempi järjestysnumero esitetään ennen suurempaa.';

// templates
$string['exercise'] = 'Tehtävä';
$string['passed'] = 'Suoritettu';
$string['nosubmissions'] = 'Ei palautuksia';
$string['requiredpoints'] = '{$a} pistettä vaaditaan läpäisyyn';
$string['requirednotpassed'] = 'Pakollisia tehtäviä ei ole suoritettu';
$string['opens'] = 'Avautuu';
$string['latealloweduntil'] = 'Myöhästyneet palautukset sallitaan {$a} asti';
$string['latepointsworth'] = 'mutta pisteet ovat vain {$a} % arvoisia.';
$string['pointsrequiredtopass'] = '{$a} pistettä vaaditaan moduulin läpäisyyn.';
$string['undermaintenance'] = 'Tämä moduuli on huoltotauolla.';
$string['points'] = 'Pisteet';
$string['required'] = 'Vaaditaan';
$string['coursestaff'] = 'Kurssin henkilökunta';
$string['earlyaccess'] = 'Aikainen pääsy';
$string['viewsubmissions'] = 'Katso palautuksia';
$string['notopenedyet'] = 'Tämä moduuli ei ole vielä avautunut.';
$string['exercisedescription'] = 'Tehtävän kuvaus';
$string['mysubmissions'] = 'Minun palautukseni';
$string['submissions'] = 'Palautukset';
$string['nosubmissionsyet'] = 'Ei palautuksia vielä';
$string['inspectsubmission'] = 'Tarkastele palautusta';
$string['viewallsubmissions'] = 'Katso kaikki palautukset';
$string['editexercise'] = 'Muokkaa tehtävää';
$string['editchapter'] = 'Muokkaa sisältökappaletta';
$string['earnedpoints'] = 'Ansaitut pisteet';
$string['late'] = 'Myöhässä';
$string['exerciseinfo'] = 'Tehtävän tiedot';
$string['yoursubmissions'] = 'Sinun palautuksesi';
$string['pointsrequired'] = 'Läpäisyn pisteraja';
$string['totalnumberofsubmitters'] = 'Palauttaneiden kokonaismäärä';
$string['statuserror'] = 'Virhe';
$string['statuswaiting'] = 'Odottaa arvostelua';
$string['statusinitialized'] = 'Alustettu';
$string['statusrejected'] = 'Hylätty';
$string['statusunlisted'] = 'Näkymätön sisällysluettelossa';
$string['submissionnumber'] = 'Palautus {$a}';
$string['filesinthissubmission'] = 'Tämän palautuksen tiedostot';
$string['download'] = 'Lataa';
$string['assistantfeedback'] = 'Opettajan palaute';
$string['noassistantfeedback'] = 'Tälle palautukselle ei ole opettajan palautetta.';
$string['nofeedback'] = 'Tälle palautukselle ei ole automaattisen arvostelijan tuottamaa palautetta.';
$string['submissioninfo'] = 'Palautuksen tiedot';
$string['submittedon'] = 'Palautusaika';
$string['grade'] = 'Arvosana';
$string['forstafforiginal'] = 'Henkilökunnalle: alkuperäinen';
$string['includeslatepenalty'] = 'Sisältää myöhästymissakon';
$string['submitters'] = 'Palauttaneet opiskelijat';
$string['allsubmissions'] = 'Kaikki palautukset';
$string['submitteddata'] = 'Palautuksen tiedot';
$string['submissiontime'] = 'Palautusaika';
$string['gradingtime'] = 'Arvioinnin aika';
$string['gradingtimecompleted'] = 'automaattisen arvostelun valmistumisaika';
$string['manualgrader'] = 'Arvioija (käsin arvostelu)';
$string['submittedfiles'] = 'Palautuksen tiedostot';
$string['nofiles'] = 'Ei tiedostoja';
$string['sbmsanddldeviations'] = 'Lisäpalautukset ja määräajan jatkot';
$string['userhasextrasbms'] = 'Palauttajalla {$a->submitter_name} on {$a->extra_submissions} lisäpalautus(ta) tähän tehtävään.';
$string['userhasdlextension'] = 'Palauttajalla {$a->submitter_name} on jatkettu määräaika {$a->extended_dl} asti ({$a->extra_minutes} lisäminuuttia{$a->without_late_penalty}).';
$string['submittedvalues'] = 'Palautuksen data';
$string['gradingdata'] = 'Arvostelun data';
$string['assessmanually'] = 'Arvostele tämä palautus käsin';
$string['graderfeedback'] = 'Automaattisen arvostelijan palaute';
$string['resubmittoservice'] = 'Lähetä uudelleenarvioitavaksi tehtäväpalveluun';
$string['resubmitwarning'] = 'Tätä painiketta painamalla lähetät palautuksen uudelleen arvioitavaksi tehtäväpalveluun. Tätä on tarkoitus käyttää ongelmatilanteissa, kun tehtäväpalvelu ei ole toiminut oikein, jolloin arvostelu on jäänyt virheelliseen tilaan tai tulokset ovat vääriä. Varo! Uudelleenarviointi ylikirjoittaa aiemman arvostelun tuloksen.';
$string['assesssubmission'] = 'Arvostele palautus';
$string['assessment'] = 'Arviointi';
$string['assesspoints'] = 'Pisteet';
$string['assesspoints_help'] = 'Mahdollisia pistesakkoja ei lisätä automaattisesti - tässä annetut pisteet jäävät voimaan. Tämä ylikirjoittaa automaattisesti annetut pisteet.';
$string['assessastfeedback'] = 'Opettajan palaute';
$string['assessastfeedback_help'] = 'HTML-muotoilu on sallittu. Tämä ei ylikirjoita automaattisesti annettua palautetta.';
$string['assessfeedback'] = 'Automaattisen arvostelijan palaute';
$string['assessfeedback_help'] = 'HTML-muotoilu on sallittu. Tämä ylikirjoittaa automaattisesti annetun palautteen.';
$string['feedbackto'] = 'Palaute tehtävästä {$a}';
$string['youhavenewfeedback'] = 'Sinulla on uutta henkilökohtaista palautetta tehtävästä <a href="{$a->exurl}">{$a->exname}</a>, <a href="{$a->sbmsurl}">palautus {$a->sbmscounter}</a>.';
$string['youhavenewfeedbacknosbmsurl'] = 'Sinulla on uutta henkilökohtaista palautetta tehtävästä <a href="{$a->exurl}">{$a->exname}</a>, palautus {$a->sbmscounter}.';
$string['numbersubmissions'] = '{$a} palautusta';
$string['inspect'] = 'Tarkastele';
$string['gradingsubmission'] = 'Arvostellaan palautusta...';
$string['postingsubmission'] = 'Lähetetään palautus...';
$string['loadingexercise'] = 'Ladataan tehtävää...';
$string['exerciseresults'] = 'Tehtävän tulokset';
$string['toc'] = 'Sisällysluettelo';
$string['youhaveextrasubmissions'] = 'Sinulla on {$a} ylimääräistä palautusta';
$string['withyourextension'] = 'henkilökohtaisen jatkoaikasi kanssa';
$string['close'] = 'Sulje';
$string['date'] = 'Päivämäärä';
$string['files'] = 'Tiedostot';
$string['loading'] = 'Ladataan...';
$string['submissionreceived'] = 'Palautus vastaanotettu.';
$string['gotofeedback'] = 'Siirry palautteeseen';
$string['acceptedforgrading'] = 'Palautuksesi on vastaanotettu arvosteluun.';
$string['exercisecategory'] = 'Tehtäväkategoria';
$string['statusnototal'] = 'Ei kokonaispisteitä';
$string['participants'] = 'Osallistujat';
$string['resultsof'] = 'Käyttäjän {$a} tulokset';
$string['numberofparticipants'] = 'Osallistujien lukumäärä (kaikki roolit)';
$string['numberofparticipantswithrole'] = 'Osallistujien lukumäärä (roolissa {$a})';
$string['numberofparticipantsfilter'] = 'Suodatuksen osuvien osallistujien lukumäärä';
$string['idnumber'] = 'Tunnistenumero';
$string['idnumber_help'] = 'Käyttäjän tunnistenumero (opiskelijanumero)';
$string['firstname'] = 'Etunimi';
$string['lastname'] = 'Sukunimi';
$string['email'] = 'Sähköposti';
$string['sortasc'] = 'Nouseva järjestys';
$string['sortdesc'] = 'Laskeva järjestys';
$string['searchresults'] = 'Hakutulokset';
$string['previous'] = 'Edellinen';
$string['next'] = 'Seuraava';
$string['currentparen'] = '(nykyinen)';
$string['sortby'] = 'Järjestä kentän {$a} mukaan';
$string['selectuserrole'] = 'Valitse käyttäjän rooli';
$string['resultsperpage'] = 'Tulosten lukumäärä yhdellä sivulla';
$string['searchforparticipants'] = 'Hae osallistujia';
$string['showhidesearch'] = 'Näytä/piilota haku';
$string['submittedafter'] = 'Palautettu jälkeen';
$string['submittedbefore'] = 'Palautettu ennen';
$string['gradegreq'] = 'Arvosana suurempi tai yhtä suuri kuin';
$string['gradeless'] = 'Arvosana pienempi tai yhtä suuri kuin';
$string['hasassistfeedback'] = 'Palautukset, joille on annettu opettajan palautetta';
$string['searchforsubmissions'] = 'Hae palautuksia';
$string['yesassistfeedback'] = 'On opettajan palautetta';
$string['noassistfeedback'] = 'Ei ole opettajan palautetta';
$string['anystatus'] = 'Kaikki tilat';
$string['anystatusnoterror'] = 'Kaikki tilat paitsi virhetila';
$string['numbersubmissionsmatched'] = '{$a} palautusta osui suodatukseen';
$string['courseoverview'] = 'Kurssin yleiskatsaus';
$string['intotal'] = 'Kaikkiaan';
$string['addextrasbms'] = 'Lisää lisäpalautuksia';
$string['graderoutput'] = 'tehtäväpalvelusta';
$string['extrasbmsaddedsuccess'] = 'Lisäpalautusten lisääminen onnistui.';
$string['addextrasbmsnote'] = 'Myönnä opiskelijalle {$a->userfullname} lisäpalautuksia tehtävään {$a->exercise} normaalin palautusrajan lisäksi ({$a->normallimit} palautusta). Jos opiskelijalle on jo aiemmin myönnetty lisäpalautuksia, uudet lisäpalautukset lisätään aiempien päälle.';
$string['novalues'] = 'Ei ole';
$string['gradererrors'] = 'Virheet tarkistimelta';

// teachers edit pages
$string['editcourse'] = 'Muokkaa kurssia';
$string['categoryname'] = 'Kategorien nimi';
$string['categoryname_help'] = 'Syötä kuvaava, lyhyt ja käyttäjille näkyvä nimi kategorialle.';
$string['createcategory'] = 'Luo uusi kategoria';
$string['cateditsuccess'] = 'Kategorian päivitys onnistui.';
$string['catcreatesuccess'] = 'Kategorian luominen onnistui.';
$string['catcreatefailure'] = 'Uutta kategoriaa ei voitu tallentaa tietokantaan.';
$string['automaticsetup'] = 'Automaattinen asennus';
$string['autosetup'] = 'Päivitä ja luo Astra-tehtäviä automaattisesti';
$string['autosetup_help'] = 'Tuo kurssin asetukset tehtäväpalvelun osoitteesta ja ylikirjoita kurssin sisältö ja asetukset (Astra-kierrokset, -tehtävät ja -kategoriat)';
$string['createmodule'] = 'Luo tehtäväkierros';
$string['modeditsuccess'] = 'Tehtäväkierroksen päivitys onnistui.';
$string['modcreatesuccess'] = 'Tehtäväkierroksen luominen onnistui.';
$string['modcreatefailure'] = 'Uutta tehtäväkierrosta ei voitu tallentaa tietokantaan.';
$string['modeditfailure'] = 'Tehtäväkierroksen päivittäminen epäonnistui.';
$string['createexercise'] = 'Luo uusi tehtävä';
$string['createchapter'] = 'Luo uusi sisältökappale';
$string['lobjecteditsuccess'] = 'Oppimissisällön päivitys onnistui.';
$string['lobjcreatesuccess'] = 'Oppimissisällön luominen onnistui.';
$string['lobjcreatefailure'] = 'Uutta oppimissisältöä ei voitu tallentaa tietokantaan.';
$string['exercisename'] = 'Tehtävän nimi';
$string['exercisename_help'] = 'Tehtävän nimi, joka näytetään käyttäjille.';
$string['category'] = 'Kategoria';
$string['exerciseround'] = 'Tehtäväkierros';
$string['parentexercise'] = 'Tehtävän edeltäjä (vanhempi)';
$string['parentexercise_help'] = 'Jos tämä asetetaan, tämä tehtävä listataan edeltäjän alla. Edeltäjän täytyy olla samalla kierroksella.';
$string['serviceurl'] = 'Palvelun osoite (URL)';
$string['serviceurl_help'] = 'Tämän tehtävän absoluuttinen web-osoite tehtäväpalvelussa.';
$string['maxpoints'] = 'Maksimipisteet';
$string['maxpoints_help'] = 'Maksimipisteet, jotka opiskelija voi ansaita tässä tehtävässä.';
$string['maxsubmissions'] = 'Palautusten maksimilukumäärä';
$string['maxsubmissions_help'] = 'Maksimilukumäärä palautuksia, jotka opiskelija voi tehdä tehtävässä. Arvo nolla tarkoittaa rajatonta lukumäärää. Negatiivinen arvo tarkoittaa myös rajatonta lukumäärää, mutta tehtävä säilöö vain opiskelijan N viimeisintä palautusta tehtävään. Esimerkiksi arvo -2 tarkoittaa, että kaksi viimeisintä palautusta säilötään, mutta opiskelija voi kuitenkin palauttaa useampia kertoja.';
$string['deleteexercise'] = 'Poista tehtävä';
$string['confirmobjectremoval'] = 'Vahvista kohteen {$a} poistaminen';
$string['cancel'] = 'Peruuta';
$string['learningobjectlow'] = 'oppimissisältö';
$string['learningobjectremoval'] = 'Olet poistamassa kohdetta {$a->type} {$a->name}. Oletko varma?';
$string['lobjectremovalnote'] = '<p>Jos poistat tämän oppimissisällön, <b>myös sen kaikki lapsisisällöt sekä niiden ja tämän sisällön palautukset poistetaan</b>.</p>';
$string['categoryremovalnote'] = '<p>Jos poistat tämän kategorian, <b>myös kaikki kategorian oppimissisällöt sekä niiden palautukset poistetaan</b>.</p>';
$string['roundremovalnote'] = '<p>Jos poistat tämän tehtäväkierroksen, <b>myös kaikki kierroksen oppimissisällöt sekä niiden palautukset poistetaan</b>.</p>';
$string['numsubmitters'] = '{$a} palauttajaa';
$string['notsubmittable'] = 'ei palautettava';
$string['lobjectstoremove'] = 'Poistettavat oppimissisällöt on lueteltu alla:';
$string['deleteobject'] = 'Poista kohde';
$string['categorylow'] = 'kategoria';
$string['roundlow'] = 'tehtäväkierros';
$string['deviations'] = 'Poikkeavuudet';
$string['addnewdldeviations'] = 'Lisää uusia määräajan jatkoja';
$string['dldeviations'] = 'Määräajan jatkot';
$string['submitter'] = 'Palauttaja';
$string['extraminutes'] = 'Lisäminuutit';
$string['extraminutes_help'] = 'Lisäajan määrä minuutteina.';
$string['withoutlatepenalty'] = 'Ilman myöhästymissakkoa';
$string['withoutlatepenalty_help'] = 'Jos tämä valitaan, myöhästymissakkoa ei käytetä lisäajalla (alkuperäisen määräajan ja jatketun määräajan välillä)';
$string['withoutlatepenaltysuffix'] = ' ilman myöhästymissakkoa';
$string['actions'] = 'Toiminnot';
$string['nodldeviations'] = 'Ei määräajan jatkoja.';
$string['addnewsbmslimitdeviations'] = 'Lisää uusia lisäpalautuksia';
$string['submitlimitdeviations'] = 'Lisäpalautukset';
$string['extrasubmissions'] = 'Lisäpalautusten lukumäärä';
$string['extrasubmissions_help'] = 'Sallittujen lisäpalautuskertojen lukumäärä. Tämä lukumäärä sallitaan alkuperäisen palautusrajan lisäksi.';
$string['nosbmslimitdeviations'] = 'Ei lisäpalautuksia.';
$string['search'] = 'Hae';
$string['nomatches'] = 'Ei osumia';
$string['searchfor'] = 'Hae...';
$string['deviationsexisted'] = 'Poikkeavuuksia oli jo olemassa seuraaville käyttäjille ja tehtäville. Niitä ei muokattu.';
$string['deviationscreationerror'] = 'Poikkeavuuksia ei voitu luoda seuraaville käyttäjille ja tehtäville (tietokannan virhe).';
$string['deviationscreatesuccess'] = 'Kaikkien poikkeavuuksien luominen onnistui.';
$string['back'] = 'Takaisin';
$string['adddeviationsubmitternote'] = 'Voit syöttää opiskelijat joko tekstikenttään tai valita heidät valintalaatikossa (pidä ctrl-näppäin pohjassa ja napsauta hiiren vasemmalla painikkeella). Tekstikentässä opiskelijoiden numerot tai käyttäjätunnukset syötetään pilkuilla erotettuina.';
$string['generatetoc'] = 'Generoi sisällysluettelo';
$string['generatetoc_help'] = 'Jos tämä valitaan, tehtäväkierroksen sisällysluettelo lisätään automaattisesti sisältökappaleen alkuun.';
$string['usewidecolumn'] = 'Käytä leveää saraketta';
$string['usewidecolumn_help'] = 'Poista sivun reunan tietolaatikon sarake, jolloin sisällölle jää enemmän tilaa. Tämä on suositeltavaa sisältökappaleille, mutta ei itsenäisille tehtäville, jotka tarvitsevat sarakkeen tietolaatikoita.';
$string['sbmsfilemaxsize'] = 'Palautettavan tiedoston maksimikoko (tavuissa)';
$string['sbmsfilemaxsize_help'] = 'Suurin sallittu palautettavan tiedoston koko tavuissa. Esimerkiksi, 1048576 vastaa yhtä megatavua. Poista raja käyttämällä nollaa.';
$string['allowastgrading'] = 'Salli assistentin arvostella palautuksia';
$string['allowastgrading_help'] = 'Jos tämä on valittu, assistentit (opettajat ilman muokkausoikeuksia) voivat muokata palautusten palautetekstejä ja pisteitä.';
$string['allowastviewing'] = 'Salli assistentin katsella palautuksia';
$string['allowastviewing_help'] = 'Jos tämä on valittu, assistentit (opettajat ilman muokkausoikeuksia) voivat tarkastella tehtävän palautuksia.';
$string['exportdata'] = 'Lataa kurssin dataa';
$string['exportresults'] = 'Lataa tuloksia';
$string['exportinclallexercises'] = 'Sisällytä kaikki tehtävät';
$string['exportinclallexercises_help'] = 'Valitse tällä kaikki tehtävät, tai valitse alla vain osa tehtävistä.';
$string['exportselectexercises'] = 'TAI sisällytä nämä tehtävät';
$string['exportselectcats'] = 'TAI sisällytä kaikki tehtävät näistä kategorioista';
$string['exportselectrounds'] = 'TAI sisällytä kaikki tehtävät näistä kierroksista';
$string['exportselectstudents'] = 'Sisällytä vain nämä opiskelijat';
$string['exportselectstudents_help'] = 'Sisällytä vain nämä valitut opiskelijat tai jätä tyhjäksi, jolloin kaikki kurssin opiskelijat sisällytetään.';
$string['exportsubmittedbefore'] = 'Sisällytä vain palautukset, jotka palautettiin ennen';
$string['exportsubmittedbefore_help'] = 'Sisällytä vain palautukset, jotka palautettiin ennen valittua aikaa (tai samalla ajanhetkellä). Jos päivämäärää ei oteta käyttöön, kaikki palautukset sisällytetään.';
$string['exportmustdefineexercises'] = 'Sinun täytyy määrittää, mitkä tehtävät sisällytetään. Valitse kaikki valintaruudulla tai poista valinta, jos valitset osan tehtävistä muilla valinnoilla.';
$string['exportuseonemethodtoselectexercises'] = 'Voit käyttää vain yhtä neljästä tehtävien valintamenetelmästä.';
$string['exportdescription'] = 'Lataa kurssin opiskelijoiden tehtäväpisteet JSON-tiedostona.';
$string['exportpassedlist'] = '<a href="{$a}">Lataa</a> lista opiskelijoista, jotka ovat läpäisseet kurssin tehtävien vaatimukset (jotka ansaitsivat vähintään vaaditut minimipisteet kaikissa tehtävissä, kierroksissa ja kategorioissa).';
$string['exportsubmittedfiles'] = 'Lataa palautetut tiedostot';
$string['exportsubmittedfilesdesc'] = 'Lataa palautetut tiedostot ZIP-pakettina.';
$string['exportsubmittedform'] = 'Lataa lomakkeisiin syötetyt arvot';
$string['exportsubmittedformdesc'] = 'Lataa lomakkeisiin syötetyt arvot JSON-tiedostona. Palautusten tiedostoja ei sisällytetä.';
$string['massregrading'] = 'Massauudelleenarviointi';
$string['massregradingdesc'] = 'Lähetä kerralla useita palautuksia tehtäväpalveluun uudelleenarviointia varten.';
$string['regradesubmissions'] = 'Uudelleenarvostele palautuksia';
$string['massregrinclsbms'] = 'Sisällytä palautukset';
$string['massregrinclsbms_help'] = 'Valitse sisällytettävät palautukset. Tämä noudattaa myös palautusajan valintaa.';
$string['massregrsbmserror'] = 'Vain virhetilassa olevat palautukset';
$string['massregrsbmsall'] = 'Kaikki palautukset';
$string['massregrsbmslatest'] = 'Vain viimeisin palautus jokaiselta opiskelijalta';
$string['massregrsbmsbest'] = 'Vain nykyinen paras palautus (korkeimmat pisteet) jokaiselta opiskelijalta';
$string['massregrtasksuccess'] = 'Palautukset lähetetään tehtäväpalveluun uudelleenarviointia varten mahdollisimman pian.';
$string['massregrtaskerror'] = 'Virhe: uudelleenarvostelun työtehtävää ei voitu tallentaa tietokantaan. Yhtään palautusta ei lähetetä tehtäväpalveluun.';
$string['exportindexresultsdesc'] = 'Lataa opiskelijoiden tehtävätulokset (pisteet) tai lataa lista tehtävät läpäisseistä opiskelijoista.';
$string['exportindexsubmittedfilesdesc'] = 'Lataa opiskelijoiden tehtäviin palauttamat tiedostot.';
$string['exportindexsubmittedformsdesc'] = 'Lataa opiskelijoiden tehtävälomakkeisiin syöttämät arvot. Tämä sisältää esimerkiksi tekstikenttiin kirjoitetut tekstit tai lomakkeen valitut ruudut. Ladatut tiedostot eivät sisälly tähän.';
$string['exercisessubmitted'] = 'Palautuksia saaneet tehtävät';
$string['submissionsreceived'] = '{$a} palautusta vastaanotettu';

// edit course page
$string['exercisecategories'] = 'Tehtäväkategoriat';
$string['editcategory'] = 'Muokkaa kategoriaa';
$string['remove'] = 'Poista';
$string['addnewcategory'] = 'Lisää uusi kategoria';
$string['exerciserounds'] = 'Tehtäväkierrokset';
$string['editmodule'] = 'Muokkaa tehtäväkierrosta';
$string['openround'] = 'Avaa tehtäväkierros';
$string['openexercise'] = 'Avaa tehtävä';
$string['addnewexercise'] = 'Lisää uusi tehtävä';
$string['addnewchapter'] = 'Lisää uusi sisältökappale';
$string['addnewmodule'] = 'Lisää uusi tehtäväkierros';
$string['save'] = 'Tallenna';
$string['renumerateformodules'] = 'Numeroi oppimissisällöt uudelleen jokaisessa moduulissa';
$string['renumerateignoremodules'] = 'Numeroi oppimissisällöt uudelleen välittämättä moduuleista';
$string['modulenumbering'] = 'Moduulien numerointi';
$string['contentnumbering'] = 'Sisällön numerointi';
$string['nonumbering'] = 'Ei numerointia';
$string['arabicnumbering'] = 'Arabialainen';
$string['romannumbering'] = 'Roomalainen';
$string['hiddenarabicnum'] = 'Piilotettu arabialainen';
$string['backtocourseedit'] = 'Takaisin kurssin muokkaussivulle.';
$string['clearcontentcache'] = 'Tyhjennä sisällön välimuisti';
$string['cachescleared'] = 'Tehtävien välimuisti on tyhjennetty.';

// auto setup form
$string['configurl'] = 'Kurssin asetusten osoite (URL)';
$string['configurl_help'] = 'Tästä osoitteesta ladataan kurssin sisällön asetukset.';
$string['apikey'] = 'API-avain';
$string['apikey_help'] = 'API-avain valtuuttaa pääsyn tehtäväpalveluun (ei käytössä).';
$string['sectionnum'] = 'Moodle-kurssin osion numero';
$string['sectionnum_help'] = 'Moodle-kurssitilan osion numero (0-N), jonne uudet tehtäväkierrosaktiviteetit lisätään. Osio nolla on kurssin etusivu, ja seuraava osio on numero yksi jne. (kurssisivun navigaatio listaa kaikki osiot).';
$string['apply'] = 'Aseta';
$string['backtocourse'] = 'Takaisin kurssille';
$string['autosetupsuccess'] = 'Asetukset ladattiin ja otettiin käyttöön onnistuneesti.';
$string['autosetuperror'] = 'Automaattisessa asennuksessa oli ongelmia.';

// auto setup errors
$string['configjsonparseerror'] = 'Palvelimen vastausta ei voitu jäsentää JSON-muodossa.';
$string['configcategoriesmissing'] = 'Kategoriat vaaditaan JSON-oliona.';
$string['configmodulesmissing'] = 'Moduulit (tehtäväkierrokset) vaaditaan JSON-taulukkona.';
$string['configcatnamemissing'] = 'Kategorialla pitää olla nimi.';
$string['configbadstatus'] = 'Tilalla on epäkelpo arvo: {$a}.';
$string['configbadint'] = 'Odotettiin kokonaislukua, mutta saatiin: {$a}.';
$string['configmodkeymissing'] = 'Moduuli (tehtäväkierros) vaatii avaimen.';
$string['configbadfloat'] = 'Odotettiin liukulukua, mutta saatiin: {$a}.';
$string['configbaddate'] = 'Pävämäärää ei voi jäsentää: {$a}.';
$string['configbadduration'] = 'Kestoa ei voi jäsentää: {$a}.';
$string['configexercisekeymissing'] = 'Tehtävä vaatii avaimen.';
$string['configexercisecatmissing'] = 'Tehtävä vaatii kategorian.';
$string['configexerciseunknowncat'] = 'Tehtävällä on tuntematon kategoria: {$a}.';
$string['configassistantnotfound'] = 'Käyttäjiä (assistentteja) ei löytynyt seuraavilla opiskelijanumeroilla: {$a}';
$string['configuserrolesdisallowed'] = 'Et saa muokata käyttäjärooleja kurssilla: assistentteja ei lisätty Moodle-kurssitilaan automaattisesti.';
$string['configassistantsnotarray'] = 'Assistentit täytyy määritellä taulukkona opiskelijanumeroita.';
$string['confignomanualenrol'] = 'Moodle-kurssilla ei tueta osallistujien lisäämistä käsin, joten assistentteja ei voi lisätä kurssille (he eivät saa pääsyä kurssille, vaikka heille annettaisiin opettajarooli ilman muokkausoikeutta tehtäväkierrosaktiviteeteissa.';

// plugin file area descriptions
$string['submittedfilesareadescription'] = 'Tehtäviin palautetut tiedostot';

// Errors
$string['error'] = 'Virhe';
$string['negativeerror'] = 'Tämä arvo ei voi olla negatiivinen.';
$string['closingbeforeopeningerror'] = 'Sulkeutumisajan täytyy olla myöhäisempi kuin avautumisajan.';
$string['latedlbeforeclosingerror'] = 'Myöhästyneiden palautusten määräajan täytyy olla myöhäisempi kuin sulkeutumisajan.';
$string['zerooneerror'] = 'Tämän arvon täytyy olla nollan ja yhden välillä.';
$string['mustbesetwithlate'] = 'Tämä kenttä täytyy asettaa, kun myöhästyneet palautukset on otettu käyttöön.';
$string['serviceconnectionfailed'] = 'Yhteys tehtäväpalveluun epäonnistui!';
$string['submissionfailed'] = 'Uutta palautustasi ei voitu tallentaa palvelimelle!';
$string['uploadtoservicefailed'] = 'Palautuksesi otettiin vastaan, mutta sitä ei voitu lähettää tehtäväpalveluun arvostelua varten!';
$string['youmaynotsubmit'] = 'Et voi enää palauttaa uusia ratkaisuja tähän tehtävään!';
$string['servicemalfunction'] = 'Tehtäväpalvelu ei toimi nyt. Palautus on virhetilassa.';
$string['duplicatecatname'] = 'Tämänniminen kategoria on jo olemassa.';
$string['duplicateroundremotekey'] = 'Toinen tehtäväkierros käyttää jo tätä avainta.';
$string['parentexinwronground'] = 'Edeltäjäoppimissisällön täytyy olla samalla kierroksella. Poista edeltäjän valinta, jos olet siirtämässä tätä sisältöä toiselle kierrokselle.';
$string['duplicateexerciseremotekey'] = 'Toinen oppimissisältö käyttää jo tätä avainta samalla kierroksella.';
$string['invalidobjecttype'] = 'Epäkelpo kohteen tyyppi: {$a}.';
$string['idsnotfound'] = 'Seuraavia tunnisteita ei löydetty tietokannasta: {$a}';
$string['exercisecommerror'] = 'Yhteysvirhe tehtävän kanssa.';
$string['gradingtakeslonger'] = 'Valitettavasti arvostelussa kestää kauan. Palaa myöhemmin tarkastelemaan tuloksia.';
$string['exerciselobjectexpected'] = 'Odotettiin tehtävää, mutta id vastaa toisentyyppistä oppimissisältöä.';
$string['toolargesbmsfile'] = 'Jokin ladatuista tiedostoista on liian suuri eikä palautusta tallennettu. Tiedoston kokorajoitus on {$a} tavua.';
$string['assistgradingnotallowed'] = 'Assistentit eivät voi arvostella palautuksia tässä tehtävässä.';
$string['assistviewingnotallowed'] = 'Assistentit eivät voi tarkastella palautuksia tässä tehtävässä.';
$string['exportfilesziperror'] = 'Virhe ZIP-paketin luomisessa';
$string['notenrollednosubmit'] = 'Et ole ilmoittautunut kurssille, joten et voi palauttaa tehtäviin ratkaisuja.';
$string['nosecretkeyset'] = 'Moodle-sivuston ylläpitäjä ei ole asettanut Astra-liitännäisen pakollista salaista avainta.';
$string['loadingfailed'] = 'Lataus epäonnistui!';
$string['usernotenrolled'] = 'Käyttäjä ei ole ilmoittautunut kurssille.';
$string['lowermustbeless'] = 'Alarajan täytyy olla pienempi tai yhtä suuri kuin ylärajan.';

// Events / logging
$string['eventsubmitted'] = 'Opiskelija palautti uuden ratkaisun';
$string['eventserviceconnectionfailed'] = 'Yhteys tehtäväpalveluun epäonnistui';
$string['eventexerciseservicefailed'] = 'Tehtäväpalvelu ei toimi';
$string['eventexerciseviewed'] = 'Opiskelija katsoi Astra-tehtävää';
$string['eventasyncgradingfailed'] = 'Asynkroninen arvostelu epäonnistui';

// capabilities
$string['astra:addinstance'] = 'Lisää uusi Astra-tehtävä(kierros), ja muokkaa/poista niitä';
$string['astra:view'] = 'Katsele Astra-tehtävää tai -kierrosta';
$string['astra:submit'] = 'Palauta uusi ratkaisu Astra-tehtävään';
$string['astra:viewallsubmissions'] = 'Katsele ja tarkastele kaikkia Astra-tehtävän palautuksia';
$string['astra:grademanually'] = 'Muokkaa käsin Astra-tehtävän palautuksen palautetekstiä ja arvosanaa';

// cache API
$string['cachedef_exercisedesc'] = 'Tehtäväpalvelusta noudettavien tehtäväkuvausten välimuisti';

// admin settings (settings.php)
$string['cacertheading'] = 'CA-sertifikaatit (certificate authority) tehtäväpalvelun turvallista HTTPS-yhteyttä varten';
$string['explaincacert'] = 'Jos HTTPS-yhteyksiä käytetään (eli jos minkään tehtäväpalvelun osoite alkaa https://), PHP:n libcurl-verkkoyhteyskirjaston täytyy tietää, missä Moodle-palvelin säilyttää CA-sertifikaatteja. Niillä varmennetaan vertaisen (tehtäväpalvelun) sertifikaatti. Palvelimesta riippuen libcurlin oletusasetukset saattavat toimia, jolloin nämä asetukset voi jättää tyhjiksi.';
$string['cainfopath'] = 'CA-sertifikaattipaketin tiedostopolku';
$string['cainfopath_help'] = 'CA-sertifikaatit sisältävän tiedoston absoluuttinen tiedostopolku. Jos tätä asetusta käytetään, seuraavaa asetusta (curl_capath) ei huomioida. Ubuntu Linux -koneissa tiedosto "/etc/ssl/certs/ca-certificates.crt" yleensä sisältää CA-paketin. Eri käyttöjärjestelmissä on erilaisia oletuspolkuja!';
$string['cadirpath'] = 'CA-sertifikaatit sisältävä kansio';
$string['cadirpath_help'] = 'CA-sertifikaatit sisältävän kansion absoluuttinen polku. Sertifikaattien tiedostonimien täytyy olla tiivistemuodossa (katso OpenSSL:n c_rehash-ohjelmaa). Ubuntu Linux -koneissa kansio "/etc/ssl/certs" on käyttöjärjestelmän oletuspaikka tiivistemuotoisille sertifikaateille. Jotkut käyttöjärjestelmät käyttävät mieluummin CA-pakettia kansion sijasta (katso edellistä asetusta, curl_cainfo).';
$string['asyncsecretkey'] = 'Salainen avain';
$string['asyncsecretkey_help'] = 'Salaisen avaimen avulla varmistetaan, että vain oikea tehtäväpalvelu voi lähettää arvostelun tuloksia takaisin Astraan. Toisin sanoen, avaimella lasketaan tiivistearvoja, joita muut eivät pysty toistamaan. Avaimen pitäisi olla 50-100 merkkiä pitkä ja koostua ASCII-merkeistä (a-z, A-Z, 0-9, ja erikoismerkit kuten !"#@.-). Avain ei saa vuotaa käyttäjille eikä ulkopuolisille eikä sitä säilötä myöskään tehtäväpalvelussa.';

// Privacy API
$string['privacy:metadata:core_files'] = 'Astra säilöö käyttäjien tehtäviin vastauksina palauttamat tiedostot.';
$string['privacy:metadata:core_message'] = 'Käyttäjälle lähetetään viesti, kun opettaja on kirjoittanut palautetta hänen palautukseensa.';
$string['privacy:metadata:astra_submissions'] = 'Palautukset tehtäviin sisältävät käyttäjän syöttämän datan, palautuksen arvosanan ja palautteen sekä palautusajan.';
$string['privacy:metadata:astra_submissions:submitter'] = 'Palauttaneen käyttäjän ID.';
$string['privacy:metadata:astra_submissions:submissiontime'] = 'Palautusaika, jolloin palautus ladattiin palvelimelle.';
$string['privacy:metadata:astra_submissions:exerciseid'] = 'Palautetun tehtävän ID.';
$string['privacy:metadata:astra_submissions:feedback'] = 'Palautuksen saama palaute.';
$string['privacy:metadata:astra_submissions:assistfeedback'] = 'Opettajan kirjoittama palaute.';
$string['privacy:metadata:astra_submissions:grade'] = 'Palautuksen arvosana tai pisteet.';
$string['privacy:metadata:astra_submissions:gradingtime'] = 'Palautuksen arvosteluaika.';
$string['privacy:metadata:astra_submissions:latepenaltyapplied'] = 'Palautuksen arvostelussa käytetty myöhästymissakko.';
$string['privacy:metadata:astra_submissions:servicepoints'] = 'Tehtäväpalvelun palautukselle myöntämät alkuperäiset pisteet palvelun asteikolla.';
$string['privacy:metadata:astra_submissions:submissiondata'] = 'Käyttäjän syöttämä palautuksen data, kuten lomakkeeseen kirjoitettu teksti.';
$string['privacy:metadata:astra_submissions:gradingdata'] = 'Tehtäväarvostelijan tuottama arvostelun data.';
$string['privacy:metadata:astra_dl_deviations'] = 'Henkilökohtaiset tehtävän määräajan pidennykset.';
$string['privacy:metadata:astra_dl_deviations:submitter'] = 'Käyttäjän ID, jolle jatkoaika myönnettiin.';
$string['privacy:metadata:astra_dl_deviations:exerciseid'] = 'Tehtävän ID, johon jatkoaika myönnettiin.';
$string['privacy:metadata:astra_dl_deviations:extraminutes'] = 'Jatkoajan pituus.';
$string['privacy:metadata:astra_maxsbms_devs'] = 'Henkilökohtaiset tehtävän palautuskertojen lisäykset.';
$string['privacy:metadata:astra_maxsbms_devs:submitter'] = 'Käyttäjän ID, jolle lisäpalautuskertoja myönnettiin.';
$string['privacy:metadata:astra_maxsbms_devs:exerciseid'] = 'Tehtävän ID, johon lisäpalautuskertoja myönnettiin.';
$string['privacy:metadata:astra_maxsbms_devs:extrasubmissions'] = 'Myönnettyjen lisäpalautuskertojen lukumäärä.';
$string['privacy:metadata:exerciseservice'] = 'Palautukset lähetetään tehtäväpalveluun arvosteltaviksi. Tehtäväpalvelut säilövät palautuksia vain väliaikaisesti, kunnes arvostelu on valmistunut.';
$string['privacy:metadata:exerciseservice:submissiondata'] = 'Arvosteltava palautus.';

// The gradeitem mapping hack in astra/classes/grades/gradeitems.php requires these.
$string['grade_exerciseround_name'] = 'Tehtäväkierros';
$string['grade_exercise1_name'] = 'Tehtävä 1';
$string['grade_exercise2_name'] = 'Tehtävä 2';
$string['grade_exercise3_name'] = 'Tehtävä 3';
$string['grade_exercise4_name'] = 'Tehtävä 4';
$string['grade_exercise5_name'] = 'Tehtävä 5';
$string['grade_exercise6_name'] = 'Tehtävä 6';
$string['grade_exercise7_name'] = 'Tehtävä 7';
$string['grade_exercise8_name'] = 'Tehtävä 8';
$string['grade_exercise9_name'] = 'Tehtävä 9';
$string['grade_exercise10_name'] = 'Tehtävä 10';
$string['grade_exercise11_name'] = 'Tehtävä 11';
$string['grade_exercise12_name'] = 'Tehtävä 12';
$string['grade_exercise13_name'] = 'Tehtävä 13';
$string['grade_exercise14_name'] = 'Tehtävä 14';
$string['grade_exercise15_name'] = 'Tehtävä 15';
$string['grade_exercise16_name'] = 'Tehtävä 16';
$string['grade_exercise17_name'] = 'Tehtävä 17';
$string['grade_exercise18_name'] = 'Tehtävä 18';
$string['grade_exercise19_name'] = 'Tehtävä 19';
$string['grade_exercise20_name'] = 'Tehtävä 20';
$string['grade_exercise21_name'] = 'Tehtävä 21';
$string['grade_exercise22_name'] = 'Tehtävä 22';
$string['grade_exercise23_name'] = 'Tehtävä 23';
$string['grade_exercise24_name'] = 'Tehtävä 24';
$string['grade_exercise25_name'] = 'Tehtävä 25';
$string['grade_exercise26_name'] = 'Tehtävä 26';
$string['grade_exercise27_name'] = 'Tehtävä 27';
$string['grade_exercise28_name'] = 'Tehtävä 28';
$string['grade_exercise29_name'] = 'Tehtävä 29';
$string['grade_exercise30_name'] = 'Tehtävä 30';
