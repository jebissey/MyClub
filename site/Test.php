<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

	<!-- BOOTSTRAP-->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" media='all' integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

</head>

<body>


<?php

echo '<h1>REQUEST_METHOD = ' . $_SERVER['REQUEST_METHOD'] . '</h1>'; 
if($_SERVER['REQUEST_METHOD'] == 'GET'){
    echo '<p>_GET = ' . json_encode($_GET ). '</p>'; 
    echo '<p>_POST = ' . json_encode($_POST ). '</p>'; 
} else if($_SERVER['REQUEST_METHOD'] == 'POST'){
    echo '<p>_POST = ' . json_encode($_POST ). '</p>'; 
    echo '<p>_GET = ' . json_encode($_GET ). '</p>'; 
}
echo '<p>REQUEST_URI = ' . $_SERVER['REQUEST_URI'] . '</p>'; 

require_once __DIR__ . '/lib/Backup.php';
$backup = new Backup();
if ($backup->save()) {
    echo '<p>Backup created successfully -> ' . $backup->getLastBackupFolder() . '</p>';
} else {
    echo "Backup failed";
}



require_once __DIR__ . '/lib/GravatarHandler.php';
$gravatar = new GravatarHandler();

$email = "je.bissey@gmail.com";
if ($gravatar->hasGravatar($email)) {
    echo "<p>L'utilisateur a un Gravatar : " . $gravatar->displayGravatar($email) . '</p>';
}
?>
<h1>form with method post</h1> 
<form method="post">
    <input type="hidden" name="step" value="import">

    <div class="mb-3">
        <label for="headerRow" class="form-label">Numéro de la ligne d'en-tête</label>
        <input type="number" class="form-control" id="headerRow" name="headerRow" value="1" min="1" required>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
</form>

<h1>form with method get</h1> 
<form method="get">
    <input type="hidden" name="step" value="import">

    <div class="mb-3">
        <label for="headerRow" class="form-label">Numéro de la ligne d'en-tête</label>
        <input type="number" class="form-control" id="headerRow" name="headerRow" value="1" min="1" required>
    </div>

    <button type="submit" class="btn btn-primary">Submit</button>
    <button type="reset" value="Reset">Reset</button>
</form>


<div class="row m-0 p-0">
	<div class="col-6 p-5">
		<div class="container mx-5 mt-3">
			<h2 class="display-4"> <small>Make your life <span class="text-primary">memorable</span></small> </h2>
			<p class="lead">Keep your life organized. We are the solution that you need.</p>
		</div>
		<div class="container mx-5 mr-5 mt-3 d-inline-block">
			<h4 class="text-primary pr-5"><i class="fas fa-rocket pr-3"></i>TAKE OFF YOUR BUSSINESS</h3>
			<p class="text-muted pr-5">Keep all your projects in one place. We offer you a simple Kanban board where you will be able to add as many projects and tasks as you want.</p>
			<h4 class="text-primary pr-5"><i class="far fa-calendar-check pr-3"></i>FORGET ABOUT FORGETTING</h3>
			<p class="text-muted pr-5">Always late? Let us take your agenda for you. We offer you a completely scalable calendar where you can schedule all your events and see them easily. </p>
				
		</div>
		<div class="container d-flex justify-content-center mt-4">
			<a href="register.php" class="btn btn-sign-up">GET STARTED <i class="fas fa-arrow-circle-right pl-2"></i></a>
		</div>
	</div>
	<div class="col-6">
		<img class="img-fluid" src="img/1.jpg" alt="project_management">		
	</div>	
</div>


</body>
</html>

<?php

/*
require_once __DIR__ . '/lib/Database/Tables/Debug.php';
(new Debug())->set("Test debug");

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




