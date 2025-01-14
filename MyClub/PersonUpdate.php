<?php
require_once __DIR__ . '/includes/tinyHeader.php';

require_once __DIR__ . '/lib/Database/Tables/Person.php';
require_once __DIR__ . '/lib/PasswordManager.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $update = $_POST['u'];
    if($update =='profil'){
        $email = $_POST['email'];
        $password = $_POST['password'];
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $nickName = $_POST['nickName'];
        $avatar = $_POST['avatar'];
    
        $updateData = [
            'Email' => $email,
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'NickName' => $nickName,
            'Avatar' => $avatar,
        ];
        if (!empty($password)) {
            $updateData['Password'] = PasswordManager::signPassword($password);
        }
    } elseif($update =='availabilities'){
        $availabilities = $_POST['availabilities'];
        $updateData = ['Availabilities' => $availabilities];
    } elseif($update =='preferences'){
        $preferences = $_POST['preferences'];
        $updateData = ['Preferences' => $preferences];
    }
    else {
        die("Fatal error");
    }
    (new Person())->setById($id, $updateData);

} else {
    die("Fatal error");
}

require_once __DIR__ . '/includes/tinyFooter.php';

header('Location:Person.php?p='.$id);
exit();