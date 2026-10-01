<?php
// CSRF_Protect.php — identical to admin/inc/CSRF_Protect.php
// Included in auth/ for standalone auth scripts.

class CSRF_Protect
{
    private $namespace;

    public function __construct($namespace = '_csrf')
    {
        $this->namespace = $namespace;

        if (session_id() === '')
        {
            session_start();
        }

        $this->setToken();
    }

    public function getToken()
    {
        return $this->readTokenFromStorage();
    }

    public function isTokenValid($userToken)
    {
        return ($userToken === $this->readTokenFromStorage());
    }

    public function echoInputField()
    {
        $token = $this->getToken();
        echo "<input type=\"hidden\" name=\"{$this->namespace}\" value=\"{$token}\" />";
    }

    public function verifyRequest()
    {
        if (!$this->isTokenValid($_POST[$this->namespace]))
        {
            die("CSRF validation failed.");
        }
    }

    private function setToken()
    {
        $storedToken = $this->readTokenFromStorage();

        if ($storedToken === '')
        {
            $token = md5(uniqid(rand(), TRUE));
            $this->writeTokenToStorage($token);
        }
    }

    private function readTokenFromStorage()
    {
        if (isset($_SESSION[$this->namespace]))
        {
            return $_SESSION[$this->namespace];
        }
        else
        {
            return '';
        }
    }

    private function writeTokenToStorage($token)
    {
        $_SESSION[$this->namespace] = $token;
    }
}
?>