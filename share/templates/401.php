<?php
class NoAuthView
{
    public function pageTitle()
    {
        return 'Not authorized';
    }
}

ob_start();
?>
<p>
    The credentials provided for the Web source, are invalid.
</p>
<?php
$content = ob_get_clean();

header('WWW-Authenticate: Basic realm="Pocs"');
http_response_code(401);

$view = new NoAuthView();

include __DIR__ . '/layout.php';
