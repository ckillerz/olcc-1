<?php
/**
 * Ce fichier permet de gérer un dépot de session.                         
 *
 * L'objectif est de donner la possibilité de sauvegarder les cookies
 * aux utilisateurs d'OLCC les cookies de manière relativemet
 * sécurisé.
 *
 * Ce service possède un formulaire simple, disponible en cas d'appel
 * en GET mais peut être intégré dans une page plus complexe si les
 * paramètres sont passés en POST.
 * 
 * Les paramètres de POST sont :
 * - name : pour le propriétaire des cookies
 * - pwd : pour le mot de passe associé
 * - $_COOKIES ;-)
 *
 * Les commandes disponibles sont :
 * - SAUV : pour sauvegarder
 * - LOAD : pour charger les cookies, les cookies existants seront supprimés.
 * - CLEAR : pour supprimer les cookies
 * 
 * @todo : un petit système anti-brute force serait le bienvenue.
 * @author : [:ckiller]
 */


//  =======> Moment DEBUT CONFIGURATION <====== //
/** le sel pour du chiffrement, à vos claviers. */
define('COOK_SALT', 'THISISASALTTOCUSTOMIZE');
/**  Le chemin de stockage des fichiers */
define('COOK_STORE_PATH', './attach/');
//  =======> Moment FIN CONFIGURATION  <======= //

/**
 * Balance sur la sortie le header du mode formulaire.
 *
 * @return void
 */
function print_header()
{
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link title="default" rel="stylesheet" href="default.css" type="text/css" />
    <link title="oldolcc" rel="alternate stylesheet" href="oldolcc.css" type="text/css" />
    <link title="lefttabs" rel="alternate stylesheet" href="lefttabs.css" type="text/css" />
    <link title="sfw" rel="alternate stylesheet" href="sfw.css" type="text/css" />
    <link title="golcc" rel="alternate stylesheet" href="golcc.css" type="text/css" />
    <title>OnlineCoinCoin</title>
  </head>

  <body>
    <form method="post" />
    <div  style="width:50%; margin: 50px 20% 20% 20%; border: 1px solid gray;background-color: #ddd">
      <div class="panel-header">&nbsp;<img src="img/ls32.png" alt="sauvegarde">Sauvegarde du profil
	  <div style="float:right"><a href="./"><img src="img/cancel.png" alt="back"></a></div></div>


	  <div style="margin: 10px">
	    <span style="display: inline-block;width:180px;text-align:right">
	      Login :</span>
	    <span>
	      <input type="text" pattern=".{3,}"   required title="3 characters minimum" value="nobody" name="name" /></span>
	  </div>

	  <div style="margin: 10px">
	    <span style="display: inline-block;width:180px;text-align:right">
	      Mot de passe :</span>
	    <span>
	      <input type="password" pattern=".{8,}"   required title="8 characters minimum" value="" name="pwd" /></span>
	  </div>
	  
	  <div style="margin: 10px; text-align: center;">
	    <input type="submit" value="Sauvegarder" name="SAUV"/>
	    <input type="submit" value="Chargement" name="LOAD"/>
	    <input type="submit" value="Effacer" name="CLEAR"/>
	  </div>
	</div>
      </div>
    </form>
  </body>
</html>
<?php
}

/**
 * Callback utilisée pendant la restauration des cookies
 * 
 * @return void
 */
function assign_cookies_walk($item, $key)
{
    setcookie($key, $item, strtotime( '+360 days' ));
}

/**
 * Détruit l'ensemble des cookies.
 * Sur Olcc, on peut considérer que cela s'apparente à une déconnexion
 */
function clear_cookies()
{
    if (isset($_SERVER['HTTP_COOKIE'])) {
	$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
	foreach($cookies as $cookie) {
	    $parts = explode('=', $cookie);
	    $name = trim($parts[0]);
	    setcookie($name, '', time()-1000);
	    setcookie($name, '', time()-1000, '/');
	}
    }
}

/**
 * Calcul un fichier de destination pour les cookies basés sur le  nom et le mot de passe.
 * 
 * Petite précaution, le fichier commencera par .ht
 * Je ne sais pas si cette méthode est très sécure, ni si cela peut causer des collision.
 *
 * @param string $user le nom du gars
 * @param string $pwd  le mot de passe du gars
 */ 
function compute_secret_filename($user, $pwd)
{
    echo $user.'</br>';
    $options = [
        'cost' => 12,
        'salt' => COOK_SALT,
    ];
    $pwd = password_hash($pwd, PASSWORD_BCRYPT, $options);
    $user = password_hash($user, PASSWORD_BCRYPT, $options);
    $res = '.ht' . md5($pwd  . $user) . '.db';
    echo COOK_STORE_PATH.$res;
    return COOK_STORE_PATH.$res;
}


if($_POST)
{
    if(isset($_POST["SAUV"]))
    {
        echo "<h1>Sauvegarde</h1>";
        $res = serialize($_COOKIE);
        file_put_contents(compute_secret_filename($_POST['name'], $_POST['pwd']), $res);
    }
    if(isset($_POST["CLEAR"]))
    {
        echo "<h1>Cookies effacés</h1>";
        clear_cookies();
    }
    if(isset($_POST["LOAD"]))
    {
        echo "<h1>Chargement effectué</h1>";
        clear_cookies();
        $file = compute_secret_filename($_POST['name'], $_POST['pwd']);
        if(file_exists($file)) {
            $res = file_get_contents($file);
            $res = unserialize($res);
            array_walk($res, 'assign_cookies_walk');
        } else {
            echo "<h2>Raté</h2>";
        }
    }
} else {
    print_header();
}?>
