    <footer "class="bg-dark text-white py-4 mt-auto">
        <div class="container">
            <p class="mb-0">&#169; JEB</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.addEventListener("DOMContentLoaded", function() {
    var width = screen.width;
    var height = screen.height;
    document.cookie = "screen_resolution=" + width + "x" + height;
});
</script>

</body>
</html>

<?php
require_once __DIR__ . '/../lib/Client.php';
require_once __DIR__ . '/../lib/Database/Tables/Log.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
} else {
    $email = '';
}
$client = new Client();
(new Log())->set($client->getIp(), $client->getOs(), $client->getBrowser(), $client->getScreenResolution(), $client->getType(), $client->getUri(), $client->getToken(), $email);
?>