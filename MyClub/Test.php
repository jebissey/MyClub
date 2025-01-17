
<?php

require_once __DIR__ . '/lib/Backup.php';
$backup = new Backup();
if ($backup->save()) {
    echo "Backup created successfully -> ";
    echo $backup->getLastBackupFolder();
} else {
    echo "Backup failed";
}



require_once __DIR__ . '/lib/GravatarHandler.php';
$gravatar = new GravatarHandler();

// Option 1 : Simplement vérifier si un Gravatar existe
$email = "je.bissey@gmail.com";
if ($gravatar->hasGravatar($email)) {
    echo "L'utilisateur a un Gravatar";
}

// Option 2 : Afficher le Gravatar avec les paramètres par défaut
echo $gravatar->displayGravatar($email);





require_once __DIR__ . '/lib/Database/Tables/Debug.php';
(new Debug())->set("Test debug");
/*
//envoi d'un rdv dans un agenda
?>

<div class="col-xs-12 text-center tracking-add-to-calendar">
    <figure   class="col-xs-4" >
        <a target="_blank" href="https://www.google.com/calendar/event?action=TEMPLATE&amp;text=RDV+Caisse+d%27Epargne+Agence+de+Dijon+Jaures+avec+M.+Tredez&amp;location=Les+Passages+Jean+Jaures%2C21000+Dijon&amp;dates=20250116T160000%2F20250116T170000&amp;details=Rendez-vous+en+agence%0A%0AListe+des+pi%C3%A8ces+%C3%A0+apporter+%3A%0A%2A+Un+justificatif+d%E2%80%99identit%C3%A9+en+cours+de+validit%C3%A9%0A%2A+Un+justificatif+de+domicile+de+moins+de+trois+mois%0A%2A+Un+justificatif+d%E2%80%99activit%C3%A9+%C3%A9conomique+%28ex.+%3A+3+derniers+bulletins+de+salaire+ou+dernier+avis+d%E2%80%99imposition+ou+autre+justificatif+de+revenus+r%C3%A9cent%29%0A%0ANum%C3%A9ro+de+t%C3%A9l%C3%A9phone+de+l%27agence+%3A+03+80+60+04+60+"> <img style="margin:10px;height:50px" align="center" alt="" src="/build/images/icones-cal/google.png" /></a>
        <figcaption style="font-size:12px">
            <a target="_blank" href="https://www.google.com/calendar/event?action=TEMPLATE&amp;text=RDV+Caisse+d%27Epargne+Agence+de+Dijon+Jaures+avec+M.+Tredez&amp;location=Les+Passages+Jean+Jaures%2C21000+Dijon&amp;dates=20250116T160000%2F20250116T170000&amp;details=Rendez-vous+en+agence%0A%0AListe+des+pi%C3%A8ces+%C3%A0+apporter+%3A%0A%2A+Un+justificatif+d%E2%80%99identit%C3%A9+en+cours+de+validit%C3%A9%0A%2A+Un+justificatif+de+domicile+de+moins+de+trois+mois%0A%2A+Un+justificatif+d%E2%80%99activit%C3%A9+%C3%A9conomique+%28ex.+%3A+3+derniers+bulletins+de+salaire+ou+dernier+avis+d%E2%80%99imposition+ou+autre+justificatif+de+revenus+r%C3%A9cent%29%0A%0ANum%C3%A9ro+de+t%C3%A9l%C3%A9phone+de+l%27agence+%3A+03+80+60+04+60+"> Google</a>
        </figcaption>
    </figure>
    <figure   class="col-xs-4" >
        <a target="_blank" href="/calendar/ics/a933e0d301d38a5e1fce7186b09446f37e2d3f9e1f8c63f86bd93465ba85dbc77e5d1a97e2fcca5d46d9f894ef99142720d33693724de1f04e9437bf80c352e74d77249202a5f80374e8c28ee91a5e45985b5910fcbacbca75ff62be957b73239beb118d8f67c5e9e10d8757c958fbc60e7479cadaf646f0ebb10f27ffc465d334040fe60776013ab61f970f09a86c3a7a8e80843fe1365a7cd920b4a00f12550819958972c2d97eaadac42eb177bbd62bab66658c0b7e2c69afe2de71e8b289b9acdafdd22679f269007eecccff7c6c1ba928b02beeac26f3a64436c790208cc3ed8854a9ae1a350faa67e656bc5475e27611184fa8cdec37e39b50f1b0ff911d208cb86a2e06f46d1e23a01d0fcdede2e45c2dad269dab78b5ed3eb690bc80"> <img style="margin:10px;height:50px" align="center" alt="" src="/build/images/icones-cal/icalendar.png" />  </a>
        <figcaption style="font-size:12px">
            <a target="_blank" href="/calendar/ics/a933e0d301d38a5e1fce7186b09446f37e2d3f9e1f8c63f86bd93465ba85dbc77e5d1a97e2fcca5d46d9f894ef99142720d33693724de1f04e9437bf80c352e74d77249202a5f80374e8c28ee91a5e45985b5910fcbacbca75ff62be957b73239beb118d8f67c5e9e10d8757c958fbc60e7479cadaf646f0ebb10f27ffc465d334040fe60776013ab61f970f09a86c3a7a8e80843fe1365a7cd920b4a00f12550819958972c2d97eaadac42eb177bbd62bab66658c0b7e2c69afe2de71e8b289b9acdafdd22679f269007eecccff7c6c1ba928b02beeac26f3a64436c790208cc3ed8854a9ae1a350faa67e656bc5475e27611184fa8cdec37e39b50f1b0ff911d208cb86a2e06f46d1e23a01d0fcdede2e45c2dad269dab78b5ed3eb690bc80"> iCal</a>
        </figcaption>
    </figure>
    <figure>
        <a target="_blank" href="/calendar/ics/a933e0d301d38a5e1fce7186b09446f37e2d3f9e1f8c63f86bd93465ba85dbc77e5d1a97e2fcca5d46d9f894ef99142720d33693724de1f04e9437bf80c352e74d77249202a5f80374e8c28ee91a5e45985b5910fcbacbca75ff62be957b73239beb118d8f67c5e9e10d8757c958fbc60e7479cadaf646f0ebb10f27ffc465d334040fe60776013ab61f970f09a86c3a7a8e80843fe1365a7cd920b4a00f12550819958972c2d97eaadac42eb177bbd62bab66658c0b7e2c69afe2de71e8b289b9acdafdd22679f269007eecccff7c6c1ba928b02beeac26f3a64436c790208cc3ed8854a9ae1a350faa67e656bc5475e27611184fa8cdec37e39b50f1b0ff911d208cb86a2e06f46d1e23a01d0fcdede2e45c2dad269dab78b5ed3eb690bc80"> <img style="margin:10px;height:50px" align="center" alt="" src="/build/images/icones-cal/outlook.png" /></a>
        <figcaption style="font-size:12px">
            <a target="_blank" href="/calendar/ics/a933e0d301d38a5e1fce7186b09446f37e2d3f9e1f8c63f86bd93465ba85dbc77e5d1a97e2fcca5d46d9f894ef99142720d33693724de1f04e9437bf80c352e74d77249202a5f80374e8c28ee91a5e45985b5910fcbacbca75ff62be957b73239beb118d8f67c5e9e10d8757c958fbc60e7479cadaf646f0ebb10f27ffc465d334040fe60776013ab61f970f09a86c3a7a8e80843fe1365a7cd920b4a00f12550819958972c2d97eaadac42eb177bbd62bab66658c0b7e2c69afe2de71e8b289b9acdafdd22679f269007eecccff7c6c1ba928b02beeac26f3a64436c790208cc3ed8854a9ae1a350faa67e656bc5475e27611184fa8cdec37e39b50f1b0ff911d208cb86a2e06f46d1e23a01d0fcdede2e45c2dad269dab78b5ed3eb690bc80"> Outlook</a>
        </figcaption>
    </figure>
</div>

*/




