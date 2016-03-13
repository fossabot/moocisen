<?php
session_start();

$meschoix=$_POST["dataForm"]; //Mes choix
$solutions=$_POST["dataForm2"]; //Les solutions
$idm=$_POST["dataidm"]; //id mooc
$idc=$_POST["dataidc"]; //id chap
$ide=$_POST["dataide"]; //id exo
$tabide=$_POST["datatabide"]; //id tab des partie exo

/*
$meschoix='["Monsieur Jeson Dupont","Jean Nicolas"]';
$solutions='["Olivier Garnier,Olivier SCHULTZ,Victor Gerard,PERRICHON Guillaume",""]';
*/
//var_dump($saisie);
/*$meschoix=str_replace('"',"",$meschoix);
$solutions=str_replace('"',"",$solutions);
$solutions=str_replace(',]',"]",$solutions);
*/

//Insert le score sur l'exercice (score basé sur l'algorithme de levenshtein)
//Remarque : une fois le score rentré impossible de réécrire dessus avec cette fonction
function insertFaitToBDD($score,$id_user,$id_exercice){
	include 'connect.inc.php'; //connexion bdd
	try { 
		$requete_prepare= $bdd->prepare("INSERT INTO faire(score,id_user,id_exercice) VALUES('$score', '$id_user', '$id_exercice')"); // on prépare notre requête
		$requete_prepare->execute();
		//var_dump($requete_prepare);
		echo "->Sauvegarde du score<br>";

	} catch (Exception $e) { 
  		echo $e->errorMessage();
  		echo "->erreur<br>";
	}
}

//Insert le score sur l'exercice (score basé sur l'algorithme de levenshtein)
//Remarque : update le score si il est déjà présent
function insertFaitToBDDwithUpdate($score,$id_user,$id_exercice){
	include 'connect.inc.php'; //connexion bdd
	try { 
		$requete_prepare= $bdd->prepare("INSERT INTO faire(score,id_user,id_exercice) VALUES('$score', '$id_user', '$id_exercice') ON DUPLICATE KEY UPDATE score='$score',id_user='$id_user',id_exercice='$id_exercice'"); // on prépare notre requête
		$requete_prepare->execute();
		//var_dump($requete_prepare);
		echo "->Sauvegarde du score<br>";
	} catch (Exception $e) { 
  		echo $e->errorMessage();
  		echo "->erreur<br>";
	}
}


//Fonction permettant de calculer la similitude entre 2 chaines (algorithme de levenshtein)
//Attention il y une limite pour la longueur de la chaine
function similaire($str1, $str2) {
    $strlen1=strlen($str1);
    $strlen2=strlen($str2);
    $max=max($strlen1, $strlen2);
    $splitSize=250;
    if($max>$splitSize)
    {
        $lev=0;
        for($cont=0;$cont<$max;$cont+=$splitSize)
        {
            if($strlen1<=$cont || $strlen2<=$cont)
            {
                $lev=$lev/($max/min($strlen1,$strlen2));
                break;
            }
            $lev+=levenshtein(substr($str1,$cont,$splitSize), substr($str2,$cont,$splitSize));
        }
    }
    else
    	$lev=levenshtein($str1, $str2);
    $porcentage= -100*$lev/$max+100;
    /*if($porcentage>75)	
    	similar_text($str1,$str2,$porcentage);*/
 	return $porcentage;
}


//compare 2 chaine au format json
function compareTab($strChoix, $strSoluce){
    $strJsonChoix = json_decode($strChoix);
    $strJsonSoluce= json_decode($strSoluce);
    $compteur = 0;
    $sizeArraySoluce = count($strJsonSoluce);
    //echo '<br>taille'.$sizeArraySoluce;

    foreach($strJsonChoix as $keyChoix){
        //echo '+'.$keyChoix.'+';
        foreach($strJsonSoluce as $keySoluce){
            if($keyChoix==$keySoluce){
                $compteur = $compteur + 1;
            }
        }
    }
    if($sizeArraySoluce==$compteur){
        echo '100% juste';
    }else if($sizeArraySoluce>$compteur){
       // echo (intval($compteur/$sizeArraySoluce)*100).'% juste';
        echo $compteur.' réponse(s) juste sur '.$sizeArraySoluce;
    }else{
        echo 'trop de réponse';
    }
}



//Calcul du %
$lepourcentage=similaire($meschoix,$solutions);
$lepourcentage=intval($lepourcentage); //Cast en entier
if ((isset($_SESSION['id_user'])) && (!empty($_SESSION['id_user']))){
	//ok session
	$id_user=$_SESSION['id_user'];
	$score = $lepourcentage;
	insertFaitToBDDwithUpdate($score,$id_user,$ide); //Insertion en BDD
	//echo "passage en bdd";
}
compareTab($meschoix,$solutions);
echo '<br><br><br>Debug<br>';

if($meschoix == $solutions){
	echo '100% CORRECT <br> les réponses sont :'.$solutions."<br><br>info débug : ".$idm." ".$idc."  ".$tabide." -solution ".$solutions." -choix".$meschoix;
}else if($lepourcentage>70){ 
	//il a plus de 70% juste donc on affiche les bonnes réponses
	echo $lepourcentage.'% CORRECT <br> les réponses sont :'.$solutions."<br><br>info débug ".$idm." ".$idc."  ".$tabide." -solution ".$solutions." -choix".$meschoix;
}else{
	echo $lepourcentage.'% CORRECT <br> les réponses sont : (avoir un score plus élevé)<br><br>info débug '.$idm."  ".$idc." --- ".$tabide." -solution ".$solutions." -choix".$meschoix;
}
exit();
?>