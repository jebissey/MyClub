<?php

namespace app\helpers;

use flight\Engine;

class Application
{
    private Engine $flight;

    public function __construct(Engine $flight)
    {
        $this->flight = $flight;
    }


    public function message($message, $timeout = 3000)
    {
        $this->error(200, $message, $timeout);
    }


    public function error403($file, $line, $timeout = 1000)
    {
        $this->error(403, "Page not allowed in file $file at line $line", $timeout);
    }

    public function error404($timeout = 1000)
    {
        $this->error(404, 'Page not found', $timeout);
    }

    public function error470($requestMethod, $file, $line, $timeout = 1000)
    {
        $this->error(470, "Method $requestMethod invalid in file $file at line $line", $timeout);
    }

    public function error480($email, $timeout = 1000)
    {
        $this->error(480, "Unknown user with this email address: $email", $timeout);
    }

    public function error481($email, $timeout = 1000)
    {
        $this->error(481, "Invalid email address: $email", $timeout);
    }

    public function error482($message, $timeout = 1000)
    {
        $this->error(482, "Invalid password: $message", $timeout);
    }


    public function error497($token, $file, $line, $timeout = 1000)
    {
        $this->error(497, "Token $token is expired in file $file at line $line", $timeout);
    }

    public function error498($table, $token, $file, $line, $timeout = 1000)
    {
        $this->error(498, "Record with token $token not found in table $table in file $file at line $line", $timeout);
    }

    public function error499($table, $id, $file, $line, $timeout = 1000)
    {
        $this->error(499, "Record $id not found in table $table in file $file at line $line", $timeout);
    }


    private function error($code, $message, $timeout = 1000)
    {
        $this->flight->setData('code', $code);
        $this->flight->setData('message', $message);

        echo "<h1>$code</h1><h2>$message</h2>";
?>
        <script>
            setTimeout(function() {
                window.location.href = '/';
            }, <?php echo $timeout; ?>);
        </script>
<?php
    }
}
